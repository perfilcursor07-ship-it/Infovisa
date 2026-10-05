<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoDigital extends Model
{
    use SoftDeletes;

    public const MOTIVO_PRAZO_FINALIZADO_ITENS_ATENDIDOS = 'Encerrado automaticamente: todos os itens de atendimento foram aprovados.';

    protected $table = 'documentos_digitais';

    protected $fillable = [
        'tipo_documento_id',
        'subcategoria_id',
        'processo_id',
        'pasta_id',
        'usuario_criador_id',
        'ultimo_editor_id',
        'ultima_edicao_em',
        'versao_atual',
        'numero_documento',
        'nome',
        'conteudo',
        'sigiloso',
        'status',
        'arquivo_pdf',
        'codigo_autenticidade',
        'finalizado_em',
        'prazo_dias',
        'tipo_prazo',
        'prazo_notificacao',
        'data_vencimento',
        'prazo_iniciado_em',
        'prazo_iniciado_por',
        'prazo_finalizado_em',
        'prazo_finalizado_por',
        'prazo_finalizado_motivo',
        'prazo_prorrogado_dias',
        'prazo_prorrogado_em',
        'prazo_prorrogado_por',
        'prazo_prorrogado_motivo',
        'processos_ids',
        'os_id',
        'atividade_index',
        // Documento físico
        'tipo_origem',
        'arquivo_fisico_pdf',
        'data_entrega_fisica',
    ];

    protected $casts = [
        'sigiloso' => 'boolean',
        'prazo_notificacao' => 'boolean',
        'finalizado_em' => 'datetime',
        'ultima_edicao_em' => 'datetime',
        'prazo_dias' => 'integer',
        'data_vencimento' => 'date',
        'data_entrega_fisica' => 'date',
        'prazo_iniciado_em' => 'datetime',
        'prazo_finalizado_em' => 'datetime',
        'prazo_prorrogado_dias' => 'integer',
        'prazo_prorrogado_em' => 'datetime',
        'processos_ids' => 'array',
        'atividade_index' => 'integer',
    ];

    /**
     * Gera código único de autenticidade
     */
    public static function gerarCodigoAutenticidade(): string
    {
        do {
            $codigo = md5(uniqid(rand(), true));
            $existe = self::where('codigo_autenticidade', $codigo)->exists();
        } while ($existe);

        return $codigo;
    }

    /**
     * Gera o próximo número de documento no formato 000001.2025
     */
    public static function gerarNumeroDocumento(): string
    {
        $ano = date('Y');
        
        // Busca o último número de documento do ano (incluindo soft deleted)
        $ultimo = self::withTrashed()
            ->where('numero_documento', 'like', "%.{$ano}")
            ->orderByRaw("CAST(SUBSTRING(numero_documento FROM 1 FOR 6) AS INTEGER) DESC")
            ->first();

        // Extrai o número sequencial (primeiros 6 dígitos)
        $sequencial = 1;
        if ($ultimo) {
            $partes = explode('.', $ultimo->numero_documento);
            $sequencial = (int) $partes[0] + 1;
        }

        // Garante que o número seja único tentando até encontrar um disponível
        $tentativas = 0;
        do {
            $numeroDocumento = sprintf('%06d.%s', $sequencial + $tentativas, $ano);
            $existe = self::withTrashed()->where('numero_documento', $numeroDocumento)->exists();
            $tentativas++;
        } while ($existe && $tentativas < 100);

        return $numeroDocumento;
    }

    /**
     * Relacionamento com tipo de documento
     */
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * Relacionamento com a subcategoria escolhida (opcional).
     */
    public function subcategoria()
    {
        return $this->belongsTo(TipoDocumentoSubcategoria::class, 'subcategoria_id');
    }

    /**
     * Nome para listas: nome do documento (ou do tipo) + subcategoria, ex.: "Relatório (Supervisão)".
     */
    public function getNomeExibicaoAttribute(): string
    {
        $nome = $this->nome ?: ($this->tipoDocumento->nome ?? 'Documento');
        $subcategoria = $this->subcategoria_id ? $this->subcategoria?->nome : null;

        if ($subcategoria && !str_contains(mb_strtolower($nome), mb_strtolower($subcategoria))) {
            $nome .= " ({$subcategoria})";
        }

        return $nome;
    }

    /**
     * Relacionamento com processo
     */
    public function processo()
    {
        return $this->belongsTo(Processo::class);
    }

    /**
     * Inclui documentos individuais e documentos em lote vinculados ao processo.
     */
    public function scopeVinculadoAoProcesso($query, int $processoId)
    {
        return $query->where(function ($q) use ($processoId) {
            $q->where('processo_id', $processoId)
                ->orWhereJsonContains('processos_ids', $processoId)
                ->orWhereJsonContains('processos_ids', (string) $processoId);
        });
    }

    /**
     * Relacionamento com OS de origem
     */
    public function ordemServico()
    {
        return $this->belongsTo(\App\Models\OrdemServico::class, 'os_id');
    }

    /**
     * Verifica se é um documento em lote (multi-processo)
     */
    public function isLote(): bool
    {
        return !empty($this->processos_ids) && count($this->processos_ids) > 1;
    }

    /**
     * Verifica se é um documento físico (auto de infração entregue em loco)
     */
    public function isFisico(): bool
    {
        return $this->tipo_origem === 'fisico';
    }

    /**
     * Retorna os processos vinculados ao lote
     */
    public function processosLote()
    {
        if (!$this->isLote()) {
            return collect();
        }

        return Processo::with('estabelecimento')->whereIn('id', $this->processos_ids)->get();
    }

    /**
     * Relacionamento com usuário criador
     */
    public function usuarioCriador()
    {
        return $this->belongsTo(UsuarioInterno::class, 'usuario_criador_id');
    }

    /**
     * Relacionamento com assinaturas
     */
    public function assinaturas()
    {
        return $this->hasMany(DocumentoAssinatura::class);
    }

    /**
     * Relacionamento com visualizações
     */
    public function visualizacoes()
    {
        return $this->hasMany(DocumentoVisualizacao::class);
    }

    /**
     * Primeira visualização do documento (para mostrar quem visualizou primeiro)
     */
    public function primeiraVisualizacao()
    {
        return $this->hasOne(DocumentoVisualizacao::class)->oldestOfMany();
    }

    /**
     * Relacionamento com pasta
     */
    public function pasta()
    {
        return $this->belongsTo(ProcessoPasta::class, 'pasta_id');
    }

    /**
     * Relacionamento com usuário que finalizou o prazo
     */
    public function usuarioFinalizouPrazo()
    {
        return $this->belongsTo(UsuarioInterno::class, 'prazo_finalizado_por');
    }

    /**
     * Relacionamento com usuário que prorrogou o prazo
     */
    public function usuarioProrrogouPrazo()
    {
        return $this->belongsTo(UsuarioInterno::class, 'prazo_prorrogado_por');
    }

    /**
     * Finaliza o prazo do documento (marca como respondido/resolvido)
     */
    public function finalizarPrazo($usuarioInternoId, $motivo = null): void
    {
        $this->update([
            'prazo_finalizado_em' => now(),
            'prazo_finalizado_por' => $usuarioInternoId,
            'prazo_finalizado_motivo' => $motivo,
        ]);
    }

    /**
     * Verifica se todos os itens individualizados possuem resposta aprovada.
     */
    public function todosItensAtendimentoAprovados(): bool
    {
        $itens = $this->itensAtendimento()
            ->with('respostaAtual')
            ->get();

        if ($itens->isEmpty()) {
            return false;
        }

        return $itens->every(fn ($item) => $item->respostaAtual?->status === 'aprovado');
    }

    /**
     * Encerra automaticamente o prazo quando todas as exigências foram atendidas.
     */
    public function finalizarPrazoAutomaticamenteSeItensAtendidos(?int $usuarioInternoId = null): bool
    {
        if (!$this->temPrazo() || $this->isPrazoFinalizado()) {
            return false;
        }

        if (!$this->todosItensAtendimentoAprovados()) {
            return false;
        }

        $this->finalizarPrazo($usuarioInternoId, self::MOTIVO_PRAZO_FINALIZADO_ITENS_ATENDIDOS);

        return true;
    }

    public function foiFinalizadoAutomaticamentePorItensAtendidos(): bool
    {
        return $this->prazo_finalizado_motivo === self::MOTIVO_PRAZO_FINALIZADO_ITENS_ATENDIDOS;
    }

    public function reabrirPrazoAutomaticoSeItensNaoAtendidos(): bool
    {
        if (!$this->foiFinalizadoAutomaticamentePorItensAtendidos()) {
            return false;
        }

        if ($this->todosItensAtendimentoAprovados()) {
            return false;
        }

        $this->reabrirPrazo();

        return true;
    }

    /**
     * Reabre o prazo do documento
     */
    public function reabrirPrazo(): void
    {
        $this->update([
            'prazo_finalizado_em' => null,
            'prazo_finalizado_por' => null,
            'prazo_finalizado_motivo' => null,
        ]);
    }

    /**
     * Verifica se o prazo foi finalizado
     */
    public function isPrazoFinalizado(): bool
    {
        return $this->prazo_finalizado_em !== null;
    }

    /**
     * Quantidade de dias ainda disponíveis para prorrogação
     */
    public function getDiasProrrogacaoDisponiveisAttribute(): int
    {
        return max(0, 30 - ((int) ($this->prazo_prorrogado_dias ?? 0)));
    }

    /**
     * Verifica se o prazo ainda pode ser prorrogado
     */
    public function podeProrrogarPrazo(): bool
    {
        return $this->temPrazo()
            && $this->data_vencimento !== null
            && !$this->isPrazoFinalizado()
            && $this->todasAssinaturasCompletas()
            && $this->status === 'assinado'
            && $this->dias_prorrogacao_disponiveis > 0;
    }

    /**
     * Prorroga o prazo do documento respeitando o limite total de 30 dias
     */
    public function prorrogarPrazo(int $dias, int $usuarioInternoId, string $motivo): array
    {
        if ($dias < 1) {
            throw new \InvalidArgumentException('A prorrogação deve ser de pelo menos 1 dia.');
        }

        if (!$this->podeProrrogarPrazo()) {
            throw new \RuntimeException('Este documento não pode mais ter o prazo prorrogado.');
        }

        if ($dias > $this->dias_prorrogacao_disponiveis) {
            throw new \RuntimeException('A prorrogação excede o limite máximo de 30 dias para esta notificação.');
        }

        $dataAnterior = $this->data_vencimento->copy();
        $novaData = $dataAnterior->copy()->addDays($dias);

        $this->update([
            'data_vencimento' => $novaData,
            'prazo_prorrogado_dias' => ((int) ($this->prazo_prorrogado_dias ?? 0)) + $dias,
            'prazo_prorrogado_em' => now(),
            'prazo_prorrogado_por' => $usuarioInternoId,
            'prazo_prorrogado_motivo' => trim($motivo),
        ]);

        return [
            'data_anterior' => $dataAnterior,
            'data_nova' => $novaData,
            'dias' => $dias,
            'dias_total' => (int) $this->prazo_prorrogado_dias,
        ];
    }

    /**
     * Verifica se o documento tem prazo (por dias definidos ou por ser notificação)
     */
    public function temPrazo(): bool
    {
        return $this->prazo_dias || $this->prazo_notificacao;
    }

    /**
     * Relacionamento com respostas do estabelecimento
     */
    public function respostas()
    {
        return $this->hasMany(DocumentoResposta::class);
    }

    /**
     * Providências individualizadas que o estabelecimento deve atender.
     */
    public function itensAtendimento()
    {
        return $this->hasMany(DocumentoItemAtendimento::class)->orderBy('ordem');
    }

    public function exigenciaColaboradores()
    {
        return $this->hasMany(DocumentoExigenciaColaborador::class);
    }

    public function exigeAtendimentoPorItens(): bool
    {
        return (bool) ($this->tipoDocumento?->exige_itens_atendimento)
            && $this->itensAtendimento()->exists();
    }

    /**
     * Verifica se o tipo de documento permite resposta
     * Só permite resposta se o tipo permite E o prazo não foi finalizado
     */
    public function permiteResposta(): bool
    {
        // Verifica se o tipo de documento permite resposta
        if (!$this->tipoDocumento || !$this->tipoDocumento->permite_resposta) {
            return false;
        }

        if ($this->status !== 'assinado' || !$this->todasAssinaturasCompletas()) {
            return false;
        }
        
        // Se tem prazo e foi finalizado, não permite mais resposta
        if ($this->temPrazo() && $this->isPrazoFinalizado()) {
            return false;
        }
        
        return true;
    }

    /**
     * Registra uma visualização do documento por usuário externo
     * e inicia a contagem do prazo se for documento de notificação
     */
    public function registrarVisualizacao($usuarioExternoId, $ip = null, $userAgent = null): void
    {
        // Registra a visualização
        $this->visualizacoes()->create([
            'usuario_externo_id' => $usuarioExternoId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        // Se for documento de notificação e o prazo ainda não foi iniciado, inicia agora
        if ($this->prazo_notificacao && !$this->prazo_iniciado_em && $this->todasAssinaturasCompletas()) {
            $this->iniciarPrazoPorVisualizacao();
        }
    }

    /**
     * Inicia o prazo por visualização do estabelecimento
     */
    public function iniciarPrazoPorVisualizacao(): void
    {
        if ($this->prazo_iniciado_em) {
            return; // Prazo já foi iniciado
        }

        $this->prazo_iniciado_em = now();
        $this->prazo_iniciado_por = 'visualizacao';
        
        // Recalcula a data de vencimento baseada na data de início do prazo
        if ($this->prazo_dias) {
            $this->data_vencimento = $this->calcularDataVencimento($this->prazo_iniciado_em, $this->prazo_dias, $this->tipo_prazo);
        }
        
        $this->save();
    }

    /**
     * Inicia o prazo por tempo de disponibilidade (5 dias)
     * Deve ser chamado por um job/scheduler
     */
    public function iniciarPrazoPorDisponibilidade(): void
    {
        if ($this->prazo_iniciado_em) {
            return; // Prazo já foi iniciado
        }

        $this->prazo_iniciado_em = now();
        $this->prazo_iniciado_por = 'tempo_disponibilidade';
        
        // Recalcula a data de vencimento baseada na data de início do prazo
        if ($this->prazo_dias) {
            $this->data_vencimento = $this->calcularDataVencimento($this->prazo_iniciado_em, $this->prazo_dias, $this->tipo_prazo);
        }
        
        $this->save();
    }

    /**
     * Define manualmente o prazo e inicia a contagem imediatamente.
     */
    public function definirPrazoManualmente(int $prazoDias, string $tipoPrazo, bool $prazoNotificacao = false): void
    {
        if ($prazoDias < 1) {
            throw new \InvalidArgumentException('O prazo deve ser de pelo menos 1 dia.');
        }

        if ($this->status !== 'assinado' || !$this->todasAssinaturasCompletas()) {
            throw new \RuntimeException('Só é possível definir prazo manualmente em documentos totalmente assinados.');
        }

        if ($this->prazo_dias || $this->data_vencimento) {
            throw new \RuntimeException('Este documento já possui prazo configurado.');
        }

        $inicioPrazo = now();

        $this->update([
            'prazo_dias' => $prazoDias,
            'tipo_prazo' => $tipoPrazo,
            'prazo_notificacao' => $prazoNotificacao,
            'prazo_iniciado_em' => $inicioPrazo,
            'prazo_iniciado_por' => 'definicao_manual_admin',
            'data_vencimento' => $this->calcularDataVencimento($inicioPrazo, $prazoDias, $tipoPrazo),
            'prazo_finalizado_em' => null,
            'prazo_finalizado_por' => null,
            'prazo_finalizado_motivo' => null,
        ]);
    }

    /**
     * Calcula a data de vencimento baseada no tipo de prazo
     */
    private function calcularDataVencimento($dataInicio, $dias, $tipoPrazo): \Carbon\Carbon
    {
        $data = \Carbon\Carbon::parse($dataInicio);
        
        if ($tipoPrazo === 'uteis') {
            // Dias úteis - exclui finais de semana
            $diasAdicionados = 0;
            while ($diasAdicionados < $dias) {
                $data->addDay();
                if (!$data->isWeekend()) {
                    $diasAdicionados++;
                }
            }
        } else {
            // Dias corridos
            $data->addDays($dias);
        }
        
        return $data;
    }

    /**
     * Retorna a data da última assinatura obrigatória do documento
     */
    public function getDataUltimaAssinaturaAttribute(): ?\Carbon\Carbon
    {
        $ultimaAssinatura = $this->assinaturas()
            ->where('obrigatoria', true)
            ->where('status', 'assinado')
            ->whereNotNull('assinado_em')
            ->orderBy('assinado_em', 'desc')
            ->first();

        return $ultimaAssinatura?->assinado_em;
    }

    /**
     * Data de liberação no portal, distinta da criação do rascunho.
     * Nunca usa created_at como substituto da assinatura de um documento digital.
     */
    public function getDataDisponibilizacaoAttribute(): ?\Carbon\Carbon
    {
        if ($this->status !== 'assinado') {
            return null;
        }

        if ($this->isFisico()) {
            return $this->finalizado_em ?? $this->created_at;
        }

        $assinaturas = $this->assinaturas;
        if ($assinaturas->where('obrigatoria', true)->where('status', '!=', 'assinado')->isNotEmpty()) {
            return null;
        }

        $ultimaAssinatura = $assinaturas
            ->where('obrigatoria', true)
            ->where('status', 'assinado')
            ->whereNotNull('assinado_em')
            ->sortByDesc('assinado_em')
            ->first()?->assinado_em;

        // A liberação não pode anteceder a última assinatura obrigatória.
        if ($ultimaAssinatura && (!$this->finalizado_em || $ultimaAssinatura->gt($this->finalizado_em))) {
            return $ultimaAssinatura;
        }

        return $this->finalizado_em;
    }

    /**
     * Verifica se o documento está disponível há mais de 5 dias úteis
     * e o prazo ainda não foi iniciado.
     * 
     * O prazo de 5 dias conta a partir da ÚLTIMA ASSINATURA do documento,
     * conforme §1º da portaria.
     */
    public function verificarInicioAutomaticoPrazo(): bool
    {
        // Só aplica para documentos de notificação com prazo não iniciado
        if (!$this->prazo_notificacao || $this->prazo_iniciado_em || !$this->todasAssinaturasCompletas()) {
            return false;
        }

        // Pega a data da última assinatura (quando o documento ficou disponível)
        $dataUltimaAssinatura = $this->data_ultima_assinatura;
        
        // Se não tem data de assinatura, usa finalizado_em ou created_at como fallback
        if (!$dataUltimaAssinatura) {
            $dataUltimaAssinatura = $this->finalizado_em ?? $this->created_at;
        }

        // Calcula 5 dias úteis após a última assinatura
        $diasUteis = 0;
        $dataLimite = \Carbon\Carbon::parse($dataUltimaAssinatura)->copy();
        
        while ($diasUteis < 5) {
            $dataLimite->addDay();
            if (!$dataLimite->isWeekend()) {
                $diasUteis++;
            }
        }

        // Se já passou os 5 dias úteis, inicia o prazo automaticamente
        if (now()->startOfDay()->gte($dataLimite->startOfDay())) {
            $this->iniciarPrazoPorDisponibilidade();
            return true;
        }

        return false;
    }

    /**
     * Relacionamento com versões
     */
    public function versoes()
    {
        return $this->hasMany(DocumentoDigitalVersao::class);
    }

    /**
     * Relacionamento com último editor
     */
    public function ultimoEditor()
    {
        return $this->belongsTo(UsuarioInterno::class, 'ultimo_editor_id');
    }

    /**
     * Verifica se todas assinaturas obrigatórias foram feitas
     */
    public function todasAssinaturasCompletas(): bool
    {
        return !$this->assinaturas()
            ->where('obrigatoria', true)
            ->where('status', '!=', 'assinado')
            ->exists();
    }

    /**
     * Verifica se o documento possui pelo menos uma assinatura realizada
     */
    public function possuiAssinaturaRealizada(): bool
    {
        return $this->assinaturas()
            ->where('status', 'assinado')
            ->exists();
    }

    /**
     * Verifica se o documento pode ser editado.
     * Rascunhos sempre podem ser editados.
     * Documentos finalizados (aguardando_assinatura) podem ser editados
     * se nenhuma assinatura foi realizada ainda.
     */
    public function podeEditar(): bool
    {
        if ($this->status === 'rascunho') {
            return true;
        }

        if ($this->status === 'aguardando_assinatura' && !$this->possuiAssinaturaRealizada()) {
            return true;
        }

        return false;
    }

    /**
     * Salva uma nova versão do documento
     */
    public function salvarVersao($usuarioId, $conteudo, $alteracoes = null)
    {
        $ultimaVersao = $this->versoes()->max('versao') ?? 0;
        
        $versao = $this->versoes()->create([
            'usuario_interno_id' => $usuarioId,
            'versao' => $ultimaVersao + 1,
            'conteudo' => $conteudo,
            'alteracoes' => $alteracoes,
        ]);

        $this->podarVersoesAntigas();

        return $versao;
    }

    /**
     * Mantém apenas as versões mais recentes do histórico
     */
    public function podarVersoesAntigas(int $limite = 10): int
    {
        $idsManter = $this->versoes()
            ->orderByDesc('versao')
            ->limit($limite)
            ->pluck('id');

        if ($idsManter->isEmpty()) {
            return 0;
        }

        return $this->versoes()
            ->whereNotIn('id', $idsManter)
            ->delete();
    }

    /**
     * Calcula quantos dias faltam para o vencimento
     */
    public function getDiasFaltandoAttribute(): ?int
    {
        if (!$this->data_vencimento) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->data_vencimento, false);
    }

    /**
     * Verifica se o documento está vencido
     */
    public function getVencidoAttribute(): bool
    {
        if (!$this->data_vencimento) {
            return false;
        }

        return now()->startOfDay()->gt($this->data_vencimento);
    }

    /**
     * Verifica se o documento está próximo do vencimento (7 dias ou menos)
     */
    public function getProximoVencimentoAttribute(): bool
    {
        $diasFaltando = $this->dias_faltando;
        
        if ($diasFaltando === null) {
            return false;
        }

        return $diasFaltando >= 0 && $diasFaltando <= 7;
    }

    /**
     * Retorna o texto do status do prazo
     * Só mostra os dias restantes após todas as assinaturas serem concluídas
     */
    public function getTextoStatusPrazoAttribute(): string
    {
        if (!$this->data_vencimento && !$this->prazo_dias) {
            return 'Sem prazo';
        }

        // Se o prazo foi finalizado, mostra como resolvido
        if ($this->isPrazoFinalizado()) {
            return 'Respondido';
        }

        // Verifica se todas as assinaturas obrigatórias foram feitas
        if (!$this->todasAssinaturasCompletas()) {
            $pendentes = $this->assinaturas()
                ->where('obrigatoria', true)
                ->where('status', '!=', 'assinado')
                ->count();
            $total = $this->assinaturas()->where('obrigatoria', true)->count();
            return "Aguardando {$pendentes}/{$total} assinatura(s)";
        }

        // Para documentos de notificação, verifica se o prazo já foi iniciado
        if ($this->prazo_notificacao && !$this->prazo_iniciado_em) {
            return "Clique para visualizar";
        }

        $diasFaltando = $this->dias_faltando;

        if ($diasFaltando === null) {
            return 'Sem prazo';
        }

        if ($diasFaltando < 0) {
            $diasVencidos = abs($diasFaltando);
            return "Vencido há {$diasVencidos} " . ($diasVencidos === 1 ? 'dia' : 'dias');
        }

        if ($diasFaltando === 0) {
            return 'Vence hoje';
        }

        if ($diasFaltando === 1) {
            return 'Vence amanhã';
        }

        return "Faltam {$diasFaltando} dias";
    }

    /**
     * Retorna a cor do badge de status do prazo
     * Considera se as assinaturas estão pendentes
     */
    public function getCorStatusPrazoAttribute(): string
    {
        if (!$this->data_vencimento && !$this->prazo_dias) {
            return 'gray';
        }

        // Se o prazo foi finalizado, mostra em azul (resolvido)
        if ($this->isPrazoFinalizado()) {
            return 'blue';
        }

        // Se ainda tem assinaturas pendentes, mostra em cinza/neutro
        if (!$this->todasAssinaturasCompletas()) {
            return 'gray';
        }

        // Para documentos de notificação sem prazo iniciado
        if ($this->prazo_notificacao && !$this->prazo_iniciado_em) {
            return 'yellow'; // Amarelo para indicar ação necessária
        }

        if ($this->vencido) {
            return 'red';
        }

        if ($this->proximo_vencimento) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Extrai imagens base64 do HTML e salva em disco, substituindo por URL pública.
     * Evita estourar post_max_size ao salvar documentos com muitas fotos.
     */
    public static function externalizarImagensBase64(?string $html): string
    {
        if ($html === null || $html === '' || !str_contains($html, 'data:image')) {
            return $html ?? '';
        }

        $backtrackAnterior = ini_get('pcre.backtrack_limit');
        @ini_set('pcre.backtrack_limit', '5000000');

        try {
            return preg_replace_callback(
                '/src=(["\'])(data:image\/([a-zA-Z0-9.+-]+);base64,([A-Za-z0-9+\/=\s]+))\1/i',
                static function (array $matches): string {
                    try {
                        $mime = strtolower($matches[3]);
                        // Remove whitespace do base64 antes de decodificar
                        $base64Limpo = preg_replace('/\s+/', '', $matches[4]);
                        $binario = base64_decode($base64Limpo, true);

                        if ($binario === false || strlen($binario) < 32) {
                            return $matches[0];
                        }

                        // Limite por imagem (~8MB decodificado)
                        if (strlen($binario) > 8 * 1024 * 1024) {
                            return $matches[0];
                        }

                        $ext = match (true) {
                            str_contains($mime, 'png') => 'png',
                            str_contains($mime, 'gif') => 'gif',
                            str_contains($mime, 'webp') => 'webp',
                            str_contains($mime, 'svg') => 'svg',
                            default => 'jpg',
                        };

                        $caminho = 'documentos/imagens/' . date('Y/m') . '/' . uniqid('img_', true) . '.' . $ext;
                        \Storage::disk('public')->put($caminho, $binario);

                        return 'src=' . $matches[1] . \Storage::disk('public')->url($caminho) . $matches[1];
                    } catch (\Throwable $e) {
                        \Log::warning('Falha ao externalizar imagem base64 do documento', [
                            'erro' => $e->getMessage(),
                        ]);

                        return $matches[0];
                    }
                },
                $html
            ) ?? $html;
        } finally {
            if ($backtrackAnterior !== false) {
                @ini_set('pcre.backtrack_limit', (string) $backtrackAnterior);
            }
        }
    }

    /**
     * Converte URLs de imagens em data-URI para DomPDF carregar imagens corretamente.
     * Trata imagens do storage local e qualquer imagem HTTP acessível.
     */
    public static function embutirImagensStorageNoHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return $html ?? '';
        }

        // Captura TODAS as imagens com src HTTP/HTTPS, /storage/, ou caminho relativo com storage/
        $html = preg_replace_callback(
            '/src=(["\'])((?:https?:\/\/[^"\']+|(?:\.\.\/)*\/?storage\/[^"\']+))\1/i',
            static function (array $matches): string {
                $aspas = $matches[1];
                $url = $matches[2];

                // Se já é data-URI, ignora
                if (str_starts_with($url, 'data:')) {
                    return $matches[0];
                }

                // Tenta resolver o arquivo localmente primeiro
                $arquivo = self::resolverCaminhoLocalImagem($url);

                if ($arquivo && is_file($arquivo)) {
                    $mime = mime_content_type($arquivo) ?: 'image/jpeg';
                    $data = base64_encode((string) file_get_contents($arquivo));

                    return 'src=' . $aspas . 'data:' . $mime . ';base64,' . $data . $aspas;
                }

                // Fallback: tenta baixar via HTTP (para quando filesystem não resolve)
                $conteudo = self::baixarImagemViaHttp($url);
                if ($conteudo !== null) {
                    // Detecta MIME pelo conteúdo
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($conteudo) ?: 'image/jpeg';
                    $data = base64_encode($conteudo);

                    return 'src=' . $aspas . 'data:' . $mime . ';base64,' . $data . $aspas;
                }

                // Último recurso: tenta baixar substituindo domínio por localhost
                $appUrl = rtrim((string) config('app.url'), '/');
                if ($appUrl && str_starts_with($url, $appUrl)) {
                    $caminhoRelativo = substr($url, strlen($appUrl));
                    $urlLocal = 'http://127.0.0.1' . $caminhoRelativo;
                    $conteudo = self::baixarImagemViaHttp($urlLocal);
                    if ($conteudo !== null) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($conteudo) ?: 'image/jpeg';
                        $data = base64_encode($conteudo);

                        return 'src=' . $aspas . 'data:' . $mime . ';base64,' . $data . $aspas;
                    }
                }

                \Log::warning('embutirImagensStorageNoHtml: imagem não resolvida', [
                    'url' => $url,
                    'arquivo_local_tentado' => $arquivo,
                ]);

                return $matches[0];
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Resolve uma URL de imagem para um caminho local no filesystem.
     */
    private static function resolverCaminhoLocalImagem(string $url): ?string
    {
        // URL relativa com ../ (ex: ../../storage/documentos/...)
        if (str_starts_with($url, '../') || str_starts_with($url, './')) {
            if (str_contains($url, '/storage/')) {
                $posStorage = strrpos($url, '/storage/');
                $relativo = substr($url, $posStorage + 9); // Após '/storage/'
                $caminhos = [
                    storage_path('app/public/' . $relativo),
                    public_path('storage/' . $relativo),
                ];
                foreach ($caminhos as $c) {
                    if (is_file($c)) return $c;
                }
            }
            return null;
        }

        // URL relativa: /storage/...
        if (str_starts_with($url, '/storage/')) {
            $relativo = substr($url, 9); // Remove '/storage/'
            $caminhos = [
                storage_path('app/public/' . $relativo),
                public_path('storage/' . $relativo),
            ];
            foreach ($caminhos as $c) {
                if (is_file($c)) return $c;
            }
            return null;
        }

        // URL relativa sem barra: storage/...
        if (str_starts_with($url, 'storage/')) {
            $relativo = substr($url, 8); // Remove 'storage/'
            $caminhos = [
                storage_path('app/public/' . $relativo),
                public_path('storage/' . $relativo),
            ];
            foreach ($caminhos as $c) {
                if (is_file($c)) return $c;
            }
            return null;
        }

        // URL absoluta com storage/
        if (str_contains($url, '/storage/')) {
            $posStorage = strrpos($url, '/storage/');
            $relativo = substr($url, $posStorage + 9); // Após '/storage/'
            $caminhos = [
                storage_path('app/public/' . $relativo),
                public_path('storage/' . $relativo),
            ];
            foreach ($caminhos as $c) {
                if (is_file($c)) return $c;
            }
        }

        // URL absoluta do próprio APP_URL (sem storage)
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl && str_starts_with($url, $appUrl)) {
            $caminhoRelativo = ltrim(substr($url, strlen($appUrl)), '/');
            $arquivo = public_path($caminhoRelativo);
            if (is_file($arquivo)) return $arquivo;
        }

        return null;
    }

    /**
     * Tenta baixar imagem via HTTP como fallback.
     */
    private static function baixarImagemViaHttp(string $url): ?string
    {
        // Só tenta URLs HTTP/HTTPS válidas
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return null;
        }

        try {
            // Tenta com cURL primeiro (mais confiável em servidores)
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_USERAGENT => 'InfoVISA-PDF-Generator/1.0',
                ]);

                $conteudo = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $erro = curl_error($ch);
                curl_close($ch);

                if ($conteudo === false || $httpCode !== 200 || strlen($conteudo) < 32) {
                    \Log::warning('baixarImagemViaHttp: cURL falhou', [
                        'url' => $url,
                        'http_code' => $httpCode,
                        'erro' => $erro,
                    ]);
                    // Não retorna null ainda, tenta file_get_contents
                } else {
                    // Verifica se é realmente uma imagem
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($conteudo);
                    if ($mime && str_starts_with($mime, 'image/')) {
                        return $conteudo;
                    }
                }
            }

            // Fallback com file_get_contents
            if (ini_get('allow_url_fopen')) {
                $contexto = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'follow_location' => true,
                        'max_redirects' => 3,
                        'ignore_errors' => false,
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);

                $conteudo = @file_get_contents($url, false, $contexto);

                if ($conteudo !== false && strlen($conteudo) >= 32) {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($conteudo);
                    if ($mime && str_starts_with($mime, 'image/')) {
                        return $conteudo;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            \Log::warning('embutirImagensStorageNoHtml: falha ao baixar imagem', [
                'url' => $url,
                'erro' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mantém alinhamento de imagem e bordas de tabela no HTML salvo, na visualização e no PDF.
     * DomPDF não respeita margin:auto em img; o alinhamento precisa ir no parágrafo/célula.
     */
    public static function preservarLayoutTabelasComImagens(?string $html): string
    {
        if ($html === null || $html === '' || (!str_contains($html, '<img') && !str_contains(strtolower($html), '<table'))) {
            return $html ?? '';
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapperId = 'documento-tabela-imagem';
        $flags = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
        $carregado = $dom->loadHTML('<?xml encoding="UTF-8"><div id="' . $wrapperId . '">' . $html . '</div>', $flags);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (!$carregado) {
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        $wrapper = $xpath->query('//*[@id="' . $wrapperId . '"]')->item(0);
        if (!$wrapper) {
            return $html;
        }

        $replaceStyle = static function (string $style, string $property, string $value): string {
            $style = preg_replace('/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:\s*[^;]+;?/i', ';', $style) ?? $style;
            return trim($style . '; ' . $property . ': ' . $value, " \t\n\r;");
        };

        foreach ($xpath->query('.//table', $wrapper) as $table) {
            $style = (string) $table->getAttribute('style');
            if (!preg_match('/border-collapse\s*:/i', $style)) {
                $style = $replaceStyle($style, 'border-collapse', 'collapse');
            }
            if (!preg_match('/table-layout\s*:/i', $style)) {
                $style = $replaceStyle($style, 'table-layout', 'fixed');
            }
            if (!preg_match('/width\s*:/i', $style)) {
                $style = $replaceStyle($style, 'width', '100%');
            }
            $table->setAttribute('style', $style);
            $table->setAttribute('border', '1');
            $table->setAttribute('cellpadding', '6');
            $table->setAttribute('cellspacing', '0');

            $lerLargura = static function (\DOMElement $node): ?string {
                $style = (string) $node->getAttribute('style');
                if (preg_match('/(?:^|;)\s*width\s*:\s*([^;]+)/i', $style, $match)) {
                    return trim($match[1]);
                }
                if ($node->hasAttribute('data-col-width')) {
                    return trim((string) $node->getAttribute('data-col-width'));
                }
                if ($node->hasAttribute('width')) {
                    return trim((string) $node->getAttribute('width'));
                }
                return null;
            };

            $larguras = [];
            foreach ($xpath->query('./colgroup/col', $table) as $index => $col) {
                $largura = $lerLargura($col);
                if ($largura !== null && $largura !== '') {
                    $larguras[$index] = $largura;
                }
            }
            $primeiraLinha = $xpath->query('.//tr[1]', $table)->item(0);
            if ($primeiraLinha instanceof \DOMElement) {
                foreach ($xpath->query('./td | ./th', $primeiraLinha) as $index => $cell) {
                    if (!isset($larguras[$index])) {
                        $largura = $lerLargura($cell);
                        if ($largura !== null && $largura !== '') {
                            $larguras[$index] = $largura;
                        }
                    }
                }

                if ($larguras !== []) {
                    foreach ($xpath->query('./colgroup', $table) as $grupo) {
                        $table->removeChild($grupo);
                    }
                    $colgroup = $dom->createElement('colgroup');
                    $totalColunas = $xpath->query('./td | ./th', $primeiraLinha)->length;
                    for ($index = 0; $index < $totalColunas; $index++) {
                        $col = $dom->createElement('col');
                        if (isset($larguras[$index])) {
                            $col->setAttribute('style', 'width: ' . $larguras[$index]);
                            $col->setAttribute('width', $larguras[$index]);
                        }
                        $colgroup->appendChild($col);
                    }
                    $table->insertBefore($colgroup, $table->firstChild);

                    foreach ($xpath->query('.//tr', $table) as $row) {
                        foreach ($xpath->query('./td | ./th', $row) as $index => $cell) {
                            if (!isset($larguras[$index])) {
                                continue;
                            }
                            $cellStyle = $replaceStyle((string) $cell->getAttribute('style'), 'width', $larguras[$index]);
                            $cell->setAttribute('style', $cellStyle);
                            $cell->setAttribute('width', $larguras[$index]);
                            $cell->setAttribute('data-col-width', $larguras[$index]);
                        }
                    }
                }
            }
        }

        foreach ($xpath->query('.//td | .//th', $wrapper) as $cell) {
            $style = (string) $cell->getAttribute('style');
            if (!preg_match('/\bborder(?:-width|-style|-color)?\s*:/i', $style)) {
                $style = $replaceStyle($style, 'border', '1px solid #9ca3af');
            }
            $cell->setAttribute('style', $style);
        }

        foreach ($xpath->query('.//img', $wrapper) as $img) {
            $imgStyle = (string) $img->getAttribute('style');
            $block = $img->parentNode instanceof \DOMElement ? $img->parentNode : null;
            $cell = null;
            $cursor = $block;
            while ($cursor instanceof \DOMElement) {
                $tag = strtolower($cursor->nodeName);
                if ($tag === 'td' || $tag === 'th') {
                    $cell = $cursor;
                    break;
                }
                $cursor = $cursor->parentNode;
            }

            $align = null;
            if ($block && !$cell) {
                $class = (string) $block->getAttribute('class');
                $textAlign = strtolower((string) $block->getAttribute('style'));
                if (str_contains($class, 'ql-align-center') || str_contains($textAlign, 'text-align: center')) {
                    $align = 'center';
                } elseif (str_contains($class, 'ql-align-right') || str_contains($textAlign, 'text-align: right')) {
                    $align = 'right';
                }
            }
            if ($align === null) {
                $align = strtolower(trim((string) $img->getAttribute('data-align')));
            }
            if (!in_array($align, ['left', 'center', 'right'], true)) {
                if (str_contains($imgStyle, 'margin-left: auto') && str_contains($imgStyle, 'margin-right: auto')) {
                    $align = 'center';
                } elseif (str_contains($imgStyle, 'margin-left: auto')) {
                    $align = 'right';
                } else {
                    $align = 'left';
                }
            }

            $img->setAttribute('data-align', $align);
            $imgStyle = $replaceStyle($imgStyle, 'display', 'inline-block');
            $imgStyle = $replaceStyle($imgStyle, 'margin-left', '0');
            $imgStyle = $replaceStyle($imgStyle, 'margin-right', '0');
            $imgStyle = $replaceStyle($imgStyle, 'max-width', '100%');
            $imgStyle = $replaceStyle($imgStyle, 'height', 'auto');
            $img->setAttribute('style', $imgStyle);

            if ($cell) {
                $cellStyle = $replaceStyle((string) $cell->getAttribute('style'), 'text-align', $align);
                $cell->setAttribute('style', $cellStyle);
                continue;
            }

            if ($block instanceof \DOMElement) {
                $class = preg_replace('/\bql-align-(?:center|right|left|justify)\b/', '', (string) $block->getAttribute('class')) ?? '';
                if ($align === 'center' || $align === 'right') {
                    $class = trim($class . ' ql-align-' . $align);
                }
                if ($class !== '') {
                    $block->setAttribute('class', $class);
                } else {
                    $block->removeAttribute('class');
                }
                $block->setAttribute('style', $replaceStyle((string) $block->getAttribute('style'), 'text-align', $align));
            }
        }

        $saida = '';
        foreach ($wrapper->childNodes as $child) {
            $saida .= $dom->saveHTML($child);
        }

        return $saida !== '' ? $saida : $html;
    }

    public function conteudoParaExibicao(): string
    {
        return $this->conteudoComItensAtendimento(
            self::preservarLayoutTabelasComImagens($this->conteudo ?? '')
        );
    }

    /**
     * Conteúdo preparado para geração de PDF (imagens do storage embutidas).
     */
    public function conteudoParaPdf(): string
    {
        return self::embutirImagensStorageNoHtml(
            $this->conteudoComItensAtendimento(
                self::preservarLayoutTabelasComImagens($this->conteudo ?? '')
            )
        );
    }

    private function conteudoComItensAtendimento(string $conteudo): string
    {
        $itensHtml = $this->htmlItensAtendimentoDocumento();

        if ($itensHtml === '') {
            return $conteudo;
        }

        return trim($conteudo) . $itensHtml;
    }

    private function htmlItensAtendimentoDocumento(): string
    {
        $itens = $this->relationLoaded('itensAtendimento')
            ? $this->itensAtendimento
            : $this->itensAtendimento()->get();

        if ($itens->isEmpty()) {
            return '';
        }

        $html = '<div style="margin-top: 14px; padding-top: 7px; border-top: 1px solid #d1d5db; page-break-inside: avoid;">';
        $html .= '<p style="margin: 0; font-size: 10pt; font-weight: bold; color: #374151;">Exigências para atendimento</p>';
        $html .= '<p style="margin: 2px 0 6px; font-size: 7.5pt; color: #6b7280;">A empresa deverá atender os itens abaixo e anexar os comprovantes no sistema InfoVISA.</p>';
        $html .= '<ol style="margin: 0 0 0 15px; padding: 0; font-size: 8.5pt; color: #111827;">';

        $areaAnterior = null;

        foreach ($itens as $item) {
            $areaAtual = trim((string) ($item->area ?? ''));
            $html .= '<li style="margin: 0 0 5px 0; padding-left: 2px; line-height: 1.25; page-break-inside: avoid;">';
            if ($areaAtual !== '' && $areaAtual !== $areaAnterior) {
                $html .= '<span style="display: block; margin-bottom: 2px; font-size: 7.5pt; line-height: 1.15; color: #4338ca; font-weight: bold; text-transform: uppercase;">' . e($areaAtual) . '</span>';
            }
            $html .= '<span style="font-weight: bold;">' . nl2br(e($item->descricao)) . '</span>';

            if (!empty($item->embasamento_legal)) {
                $html .= '<span style="display: block; margin-top: 2px; font-size: 8pt; line-height: 1.2; color: #6b7280;"><strong>Base legal:</strong> ' . nl2br(e($item->embasamento_legal)) . '</span>';
            }

            $html .= '</li>';
            $areaAnterior = $areaAtual !== '' ? $areaAtual : null;
        }

        $html .= '</ol>';
        $html .= '</div>';

        return $html;
    }
}
