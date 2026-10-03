<?php

namespace App\Services;

use App\Models\DocumentoDigital;
use App\Models\Processo;
use App\Models\ProcessoDocumento;
use App\Models\ProcessoEvento;
use App\Models\TipoDocumentoSubcategoria;
use App\Models\TipoSetor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Linha do tempo de um processo: quanto tempo levou cada etapa (abertura → envio da empresa →
 * documentação completa → alvará definitivo → arquivamento) e quanto tempo ficou em cada setor.
 *
 * Fontes: data de criação do processo, documentos enviados pela empresa (processo_documentos),
 * aprovação dos documentos obrigatórios, Alvará Sanitário assinado (documentos_digitais) e os eventos
 * de tramitação/arquivamento (processo_eventos).
 *
 * Alvará definitivo = Alvará Sanitário assinado com subcategoria "Definitivo" ou sem subcategoria
 * (alvarás emitidos antes de existirem subcategorias). Provisório, Administrativo etc. não contam.
 */
class ProcessoLinhaTempoService
{
    public const MARCOS = [
        'abertura' => ['titulo' => 'Processo aberto', 'curto' => 'Abertura', 'icone' => '📂'],
        'primeiro_envio' => ['titulo' => 'Empresa enviou os primeiros documentos', 'curto' => '1º envio da empresa', 'icone' => '📤'],
        'todos_enviados' => ['titulo' => 'Empresa enviou todos os documentos obrigatórios', 'curto' => 'Todos os obrigatórios enviados', 'icone' => '📦'],
        'doc_completa' => ['titulo' => 'Documentação obrigatória completa (aprovada)', 'curto' => 'Documentação completa', 'icone' => '✅'],
        'alvara_provisorio' => ['titulo' => 'Alvará não definitivo emitido (provisório)', 'curto' => 'Alvará provisório', 'icone' => '📄'],
        'alvara' => ['titulo' => 'Alvará Sanitário definitivo emitido', 'curto' => 'Alvará definitivo', 'icone' => '🏅'],
        'arquivamento' => ['titulo' => 'Processo arquivado', 'curto' => 'Arquivamento', 'icone' => '🗄️'],
    ];

    private const EVENTOS_TRAMITACAO = ['processo_atribuido', 'processo_arquivado', 'processo_desarquivado'];

    private ?Collection $nomesSetores = null;

    public function calcular(Processo $processo): array
    {
        $agora = now();
        $documentos = ProcessoDocumento::where('processo_id', $processo->id)
            ->get(['id', 'processo_id', 'tipo_usuario', 'created_at', 'updated_at', 'status_aprovacao', 'aprovado_em', 'tipo_documento_obrigatorio_id', 'documento_substituido_id', 'historico_rejeicao']);
        $eventos = ProcessoEvento::where('processo_id', $processo->id)
            ->whereIn('tipo_evento', self::EVENTOS_TRAMITACAO)
            ->orderBy('created_at')
            ->get();

        // ---- Marcos ----
        $marcos = ['abertura' => $processo->created_at->copy()];

        $primeiroEnvio = $documentos->where('tipo_usuario', 'externo')->min('created_at');
        if ($primeiroEnvio) {
            $marcos['primeiro_envio'] = Carbon::parse($primeiroEnvio);
        }

        // ---- Documentos obrigatórios (tempo de envio e de aprovação de cada um) ----
        $docsObrigatorios = $this->documentosObrigatorios($processo, $documentos, $marcos['abertura'], $agora);
        $todosEnviadosEm = $docsObrigatorios && collect($docsObrigatorios)->every(fn ($d) => $d['primeiro_envio'])
            ? collect($docsObrigatorios)->max('primeiro_envio')
            : null;
        // Só vira marco próprio quando é diferente do 1º envio (senão é o mesmo momento)
        if ($todosEnviadosEm && (!isset($marcos['primeiro_envio']) || $todosEnviadosEm->greaterThan($marcos['primeiro_envio']))) {
            $marcos['todos_enviados'] = $todosEnviadosEm->copy();
        }

        if ($this->documentacaoCompleta($processo)) {
            $data = $this->dataDocumentacaoCompleta($documentos);
            if ($data) {
                $marcos['doc_completa'] = $data;
            }
        }

        $alvara = $this->alvarasPorProcesso(collect([$processo->id]))->get($processo->id);
        if ($alvara['definitivo'] ?? null) {
            $marcos['alvara'] = $alvara['definitivo'];
        }
        // Provisório só aparece se veio antes do definitivo (ou se ainda não há definitivo)
        if (($alvara['provisorio'] ?? null) && (!isset($marcos['alvara']) || $alvara['provisorio']->lessThan($marcos['alvara']))) {
            $marcos['alvara_provisorio'] = $alvara['provisorio'];
        }

        $arquivado = $processo->status === 'arquivado';
        if ($arquivado && $processo->data_arquivamento) {
            $marcos['arquivamento'] = $processo->data_arquivamento->copy();
        }

        $fim = $marcos['arquivamento'] ?? $agora;
        asort($marcos); // ordem cronológica (em dados reais a ordem pode variar)

        // ---- Etapas (entre marcos consecutivos) ----
        $chaves = array_keys($marcos);
        $etapas = [];
        foreach ($chaves as $i => $chave) {
            $proxima = $chaves[$i + 1] ?? null;
            if (!$proxima && $chave === 'arquivamento') {
                break;
            }
            $inicio = $marcos[$chave];
            $termino = $proxima ? $marcos[$proxima] : $fim;
            $etapas[] = [
                'de' => $chave,
                'ate' => $proxima,
                'titulo' => self::MARCOS[$chave]['curto'] . ' → ' . ($proxima ? self::MARCOS[$proxima]['curto'] : 'hoje'),
                'descricao' => $this->descricaoEtapa($chave, $proxima),
                'inicio' => $inicio,
                'fim' => $termino,
                'segundos' => max(0, $termino->getTimestamp() - $inicio->getTimestamp()),
                'em_andamento' => !$proxima,
            ];
        }

        // ---- Trajeto pelos setores ----
        $trajeto = $this->trajeto($processo, $eventos, $fim);

        $setores = collect($trajeto)
            ->reject(fn ($t) => $t['arquivado'])
            ->groupBy('setor')
            ->map(fn ($itens, $setor) => [
                'setor' => $setor,
                'nome' => $itens->first()['nome'],
                'segundos' => $itens->sum('segundos'),
                'passagens' => $itens->count(),
                'atual' => $itens->contains('atual', true),
            ])
            ->sortByDesc('segundos')
            ->values()
            ->all();

        return [
            'marcos' => collect($marcos)->map(fn ($data, $chave) => self::MARCOS[$chave] + ['chave' => $chave, 'data' => $data])->values()->all(),
            'etapas' => $etapas,
            'trajeto' => $trajeto,
            'setores' => $setores,
            'inicio' => $marcos['abertura'],
            'fim' => $fim,
            'em_andamento' => !$arquivado,
            'segundos_total' => max(0, $fim->getTimestamp() - $marcos['abertura']->getTimestamp()),
            'segundos_parado' => (int) $processo->getTempoTotalParadoConsiderandoParadaAtual(),
            'documentos' => $docsObrigatorios,
            'todos_enviados_em' => $todosEnviadosEm,
            // Alvará só existe no licenciamento
            'faltando' => array_values(array_diff(
                array_merge(
                    ['primeiro_envio'],
                    // "todos enviados" pode coincidir com o 1º envio (não vira marco), então só falta se ainda não aconteceu
                    count($docsObrigatorios) > 1 && !$todosEnviadosEm ? ['todos_enviados'] : [],
                    ['doc_completa'],
                    $processo->tipo === 'licenciamento' ? ['alvara'] : []
                ),
                array_keys($marcos)
            )),
        ];
    }

    /**
     * O que acontece em cada etapa (entre um marco e o próximo).
     */
    private function descricaoEtapa(string $de, ?string $ate): string
    {
        return match ($de) {
            'abertura' => 'aguardando a empresa enviar os documentos',
            'primeiro_envio' => $ate === 'todos_enviados'
                ? 'empresa completando o envio dos documentos obrigatórios'
                : 'envio, análise e correção dos documentos',
            'todos_enviados' => 'análise da vigilância e correções da empresa',
            'doc_completa' => 'análise técnica / inspeção da vigilância',
            'alvara_provisorio' => 'funcionando com alvará provisório',
            'alvara' => 'depois do alvará definitivo',
            default => '',
        };
    }

    /**
     * Tempo de cada documento obrigatório do checklist: quando foi enviado pela 1ª vez, quantas vezes
     * foi rejeitado, quando foi aprovado e há quanto tempo está parado na situação atual.
     *
     * Rejeições: histórico gravado no próprio documento (formato atual) + documentos antigos rejeitados
     * que foram substituídos por um novo envio (formato antigo).
     */
    private function documentosObrigatorios(Processo $processo, Collection $documentos, Carbon $abertura, Carbon $agora): array
    {
        $checklist = $processo->getDocumentosObrigatoriosChecklist()->where('obrigatorio', true)->values();
        if ($checklist->isEmpty()) {
            return [];
        }

        $porTipo = $documentos
            ->whereNotNull('tipo_documento_obrigatorio_id')
            ->where('tipo_usuario', 'externo')
            ->groupBy('tipo_documento_obrigatorio_id');

        return $checklist->map(function ($item) use ($porTipo, $abertura, $agora) {
            $envios = $porTipo->get($item['id'], collect());
            $recente = $envios->sortByDesc('created_at')->first();
            $primeiroEnvio = $envios->min('created_at');
            $primeiroEnvio = $primeiroEnvio ? Carbon::parse($primeiroEnvio) : null;

            $rejeicoes = $envios->sum(fn ($d) => count($d->historico_rejeicao ?? []))
                + $envios->where('status_aprovacao', 'rejeitado')->filter(fn ($d) => $d->id !== $recente?->id)->count();

            $status = $recente?->status_aprovacao;
            $aprovadoEm = $status === 'aprovado' ? Carbon::parse($recente->aprovado_em ?? $recente->updated_at) : null;
            // Pendente/rejeitado: a última alteração do documento é o (re)envio ou a rejeição
            $desde = $recente && in_array($status, ['pendente', 'rejeitado'], true) ? Carbon::parse($recente->updated_at) : null;

            return [
                'nome' => $item['nome'],
                'status' => $status,
                'primeiro_envio' => $primeiroEnvio,
                'aprovado_em' => $aprovadoEm,
                'rejeicoes' => $rejeicoes,
                'segundos_ate_envio' => $primeiroEnvio ? max(0, $primeiroEnvio->getTimestamp() - $abertura->getTimestamp()) : null,
                'segundos_ate_aprovar' => $primeiroEnvio && $aprovadoEm ? max(0, $aprovadoEm->getTimestamp() - $primeiroEnvio->getTimestamp()) : null,
                'segundos_na_situacao' => $desde ? max(0, $agora->getTimestamp() - $desde->getTimestamp()) : null,
                'segundos_sem_envio' => !$primeiroEnvio ? max(0, $agora->getTimestamp() - $abertura->getTimestamp()) : null,
            ];
        })->all();
    }

    /**
     * Alvarás Sanitários assinados de cada processo (uma consulta para todos):
     * processo_id => ['definitivo' => ?Carbon (primeiro definitivo), 'provisorio' => ?Carbon (primeiro não definitivo)].
     */
    public function alvarasPorProcesso(Collection $processoIds): Collection
    {
        if ($processoIds->isEmpty()) {
            return collect();
        }

        $definitivas = TipoDocumentoSubcategoria::query()
            ->where(fn ($q) => $q->where('nome', 'ilike', '%definitiv%')->orWhere('codigo', 'ilike', '%definitiv%'))
            ->pluck('id')
            ->flip();

        return DocumentoDigital::query()
            ->whereIn('processo_id', $processoIds)
            ->where('status', 'assinado')
            ->whereHas('tipoDocumento', fn ($q) => $q->where('codigo', 'alvara_sanitario'))
            ->withMax('assinaturas', 'assinado_em')
            ->get(['id', 'processo_id', 'subcategoria_id', 'finalizado_em', 'updated_at'])
            ->groupBy('processo_id')
            ->map(function ($docs) use ($definitivas) {
                $datas = $docs->map(fn ($d) => [
                    'definitivo' => $d->subcategoria_id === null || $definitivas->has($d->subcategoria_id),
                    'data' => Carbon::parse($d->finalizado_em ?? $d->assinaturas_max_assinado_em ?? $d->updated_at),
                ]);

                return [
                    'definitivo' => $datas->where('definitivo', true)->min('data'),
                    'provisorio' => $datas->where('definitivo', false)->min('data'),
                ];
            });
    }

    /**
     * Formata segundos de forma legível: "35 min", "5 h", "12 dias", "3 meses e 4 dias".
     */
    public static function formatarDuracao(?int $segundos): string
    {
        if ($segundos === null) {
            return '—';
        }
        if ($segundos < 3600) {
            return max(1, (int) round($segundos / 60)) . ' min';
        }
        if ($segundos < 86400) {
            return (int) round($segundos / 3600) . ' h';
        }

        $dias = (int) floor($segundos / 86400);
        if ($dias < 60) {
            return $dias . ($dias === 1 ? ' dia' : ' dias');
        }

        $meses = intdiv($dias, 30);
        $resto = $dias % 30;

        return $meses . ' meses' . ($resto ? " e {$resto} " . ($resto === 1 ? 'dia' : 'dias') : '');
    }

    /**
     * Segmentos por setor a partir dos eventos de tramitação e arquivamento.
     */
    private function trajeto(Processo $processo, Collection $eventos, Carbon $fim): array
    {
        $primeiraAtribuicao = $eventos->firstWhere('tipo_evento', 'processo_atribuido');
        $setor = $primeiraAtribuicao
            ? data_get($primeiraAtribuicao->dados_adicionais, 'setor_anterior')
            : ($processo->setor_atual ?? $processo->setor_antes_arquivar);
        $responsavel = $primeiraAtribuicao
            ? data_get($primeiraAtribuicao->dados_adicionais, 'responsavel_anterior')
            : ($processo->relationLoaded('responsavelAtual') ? $processo->responsavelAtual?->nome : null);

        $segmentos = [];
        $cursor = $processo->created_at->copy();
        $arquivado = false;
        $setorAntesArquivar = $setor;

        $fechar = function (Carbon $ate) use (&$segmentos, &$cursor, &$setor, &$responsavel, &$arquivado) {
            if ($ate->lessThanOrEqualTo($cursor)) {
                return;
            }
            $chave = $arquivado ? '__arquivado' : ($setor ?: '__sem_setor');
            $ultimo = end($segmentos);

            // Mudança só de responsável no mesmo setor: continua o mesmo trecho
            if ($ultimo && $ultimo['setor'] === $chave) {
                $segmentos[key($segmentos)]['fim'] = $ate->copy();
                $segmentos[key($segmentos)]['segundos'] += $ate->getTimestamp() - $cursor->getTimestamp();
                if ($responsavel && !in_array($responsavel, $segmentos[key($segmentos)]['responsaveis'], true)) {
                    $segmentos[key($segmentos)]['responsaveis'][] = $responsavel;
                }
            } else {
                $segmentos[] = [
                    'setor' => $chave,
                    'nome' => $this->nomeSetor($chave),
                    'inicio' => $cursor->copy(),
                    'fim' => $ate->copy(),
                    'segundos' => $ate->getTimestamp() - $cursor->getTimestamp(),
                    'responsaveis' => $responsavel ? [$responsavel] : [],
                    'arquivado' => $arquivado,
                    'atual' => false,
                ];
            }
            $cursor = $ate->copy();
        };

        foreach ($eventos as $evento) {
            $quando = $evento->created_at->copy();
            if ($quando->greaterThan($fim)) {
                break;
            }
            $fechar($quando);

            switch ($evento->tipo_evento) {
                case 'processo_atribuido':
                    $dados = $evento->dados_adicionais ?? [];
                    $setor = array_key_exists('setor_novo', $dados) ? $dados['setor_novo'] : $setor;
                    $responsavel = $dados['responsavel_novo'] ?? null;
                    break;
                case 'processo_arquivado':
                    $setorAntesArquivar = $setor;
                    $arquivado = true;
                    break;
                case 'processo_desarquivado':
                    $arquivado = false;
                    $setor = $setorAntesArquivar;
                    break;
            }
        }

        // Trecho final (até hoje ou até o arquivamento definitivo)
        if (!$arquivado) {
            $fechar($fim);
            if ($processo->status !== 'arquivado' && $segmentos) {
                $segmentos[array_key_last($segmentos)]['atual'] = true;
            }
        }

        // Descarta trechos de menos de 1 minuto (ex.: atribuição corrigida logo em seguida) e junta
        // os trechos vizinhos do mesmo setor que ficaram separados por eles
        $filtrados = count($segmentos) > 1 ? array_filter($segmentos, fn ($s) => $s['segundos'] >= 60) : $segmentos;
        $resultado = [];
        foreach ($filtrados as $segmento) {
            $ultimo = $resultado ? $resultado[array_key_last($resultado)] : null;
            if ($ultimo && $ultimo['setor'] === $segmento['setor']) {
                $indice = array_key_last($resultado);
                $resultado[$indice]['fim'] = $segmento['fim'];
                $resultado[$indice]['segundos'] += $segmento['segundos'];
                $resultado[$indice]['responsaveis'] = array_values(array_unique(array_merge($ultimo['responsaveis'], $segmento['responsaveis'])));
                $resultado[$indice]['atual'] = $ultimo['atual'] || $segmento['atual'];
            } else {
                $resultado[] = $segmento;
            }
        }

        return $resultado;
    }

    private function nomeSetor(string $codigo): string
    {
        if ($codigo === '__arquivado') {
            return 'Arquivado (temporariamente)';
        }
        if ($codigo === '__sem_setor') {
            return 'Sem setor definido';
        }

        $this->nomesSetores ??= TipoSetor::pluck('nome', 'codigo');

        return $this->nomesSetores[$codigo] ?? ucwords(str_replace('_', ' ', $codigo));
    }

    /**
     * Documentação obrigatória completa segundo o checklist (mesma regra da tela do processo).
     */
    private function documentacaoCompleta(Processo $processo): bool
    {
        $obrigatorios = $processo->getDocumentosObrigatoriosChecklist()->where('obrigatorio', true);

        return $obrigatorios->isNotEmpty() && $obrigatorios->every(fn ($d) => $d['status'] === 'aprovado');
    }

    /**
     * Data em que a documentação ficou completa: aprovação mais recente entre os documentos obrigatórios aprovados.
     */
    private function dataDocumentacaoCompleta(Collection $documentos): ?Carbon
    {
        $data = $documentos
            ->whereNotNull('tipo_documento_obrigatorio_id')
            ->where('status_aprovacao', 'aprovado')
            ->map(fn ($d) => $d->aprovado_em ?? $d->updated_at)
            ->filter()
            ->max();

        return $data ? Carbon::parse($data) : null;
    }
}
