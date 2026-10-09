<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Requisição de numeração de Notificação de Receita (Portaria 344/98 · RDC 1.000/2025).
 * Feita pela empresa dentro do processo de receituário; cada pedido é individual
 * e só vale depois de liberado pela Vigilância Sanitária.
 *
 * Com o SNCR a Vigilância distribui NUMERAÇÕES (não mais talonários/blocos):
 *  - física: o prescritor manda imprimir os talonários numa gráfica com os números liberados
 *    (modelo oficial da Anvisa; a quantidade de folhas por bloco é livre);
 *  - eletrônica: numeração própria, vinculada ao prescritor, usada pelo serviço de prescrição integrado ao SNCR.
 */
class ReceituarioRequisicao extends Model
{
    use SoftDeletes;

    protected $table = 'receituario_requisicoes';

    public const TIPOS_NOTIFICACAO = ['A', 'B', 'B2', 'C2', 'C3'];

    /** Nome de cada tipo de Notificação de Receita */
    public const NOMES_TIPO = [
        'A' => 'Notificação A (amarela)',
        'B' => 'Notificação B (azul)',
        'B2' => 'Notificação B2 (azul)',
        'C2' => 'Retinoides de uso sistêmico',
        'C3' => 'Talidomida',
    ];

    public const MODALIDADES = [
        'fisica' => 'Física',
        'eletronica' => 'Eletrônica',
    ];

    public const DESCRICAO_MODALIDADE = [
        'fisica' => 'Impressa em gráfica com os números liberados',
        'eletronica' => 'Saldo no SNCR para a prescrição eletrônica',
    ];

    /** Unidade da quantidade pedida: numerações (SNCR) · blocos (requisições antigas) */
    public const UNIDADES = [
        'numeracoes' => ['singular' => 'numeração', 'plural' => 'numerações'],
        'blocos' => ['singular' => 'bloco', 'plural' => 'blocos'],
    ];

    /** Quantidade máxima aceita no formulário por tipo/modalidade (o parâmetro real é avaliado pela Vigilância). */
    public const QUANTIDADE_MAXIMA = 2000;

    /** Referência para converter os parâmetros da DVISA (antes em blocos) em numerações */
    public const FOLHAS_POR_BLOCO_REFERENCIA = 20;

    public const EMAIL_RECEITUARIO = 'receituario.to@gmail.com';

    public const DECLARACOES = [
        'controle_especial' => 'Solicito numeração de notificações de receita para medicamentos sujeitos ao controle especial da Portaria 344/98.',
        'envio_confeccao' => 'Declaro estar ciente que, após a confecção das notificações físicas, devo enviar para o e-mail ' . self::EMAIL_RECEITUARIO . ' cópia de uma folha de cada tipo de receituário confeccionado e a autorização emitida devidamente carimbada e assinada pelo representante legal da gráfica no prazo de 30 dias.',
        'suspensao' => 'Declaro estar ciente que o não cumprimento das instruções acima acarretará a suspensão da liberação de novas numerações de notificação de receita até que regularize a pendência.',
        'veracidade' => 'Declaro para os devidos fins que todas as informações prestadas e documentos apresentados são verdadeiros, assumindo a responsabilidade administrativa, civil e criminal pelos mesmos.',
        'intransferivel' => 'Declaro estar ciente que, conforme o Art. 35 § 7º da Portaria 344/98, a "Notificação de Receita é personalizada e intransferível", sendo infração sanitária emprestar ou manter notificação de receita assinada para preenchimento de outrem.',
    ];

    /**
     * Parâmetros de entrega da DVISA, em numerações (antes: blocos × 20). Limite por tipo somando física e eletrônica.
     * 'especialidades': trechos do nome da especialidade que dão direito ao limite de especialista.
     */
    public const PARAMETROS_ENTREGA = [
        'A' => ['especialista' => 200, 'especialidades' => ['NEUROLOGIA', 'NEUROCIRURGIA', 'PSIQUIATRIA', 'PSICOGERIATRIA'], 'rotulo' => 'neurologista, psiquiatra', 'outras' => 60],
        'B' => ['especialista' => 600, 'especialidades' => ['NEUROLOGIA', 'NEUROCIRURGIA', 'PSIQUIATRIA', 'PSICOGERIATRIA'], 'rotulo' => 'neurologista, psiquiatra', 'outras' => 100],
        'B2' => ['especialista' => 200, 'especialidades' => ['NUTROLOGIA', 'ENDOCRINOLOGIA'], 'rotulo' => 'nutrólogo, endocrinologista', 'outras' => 20],
        'C2' => ['especialista' => 200, 'especialidades' => ['DERMATOLOGIA'], 'rotulo' => 'dermatologista', 'outras' => 20, 'outras_justificativa' => true],
        'C3' => ['especialista' => 20, 'especialidades' => [], 'rotulo' => 'todas as especialidades', 'outras' => 20],
    ];

    /** Cirurgião-dentista e médico veterinário: só os tipos listados (demais mediante justificativa) */
    public const PARAMETROS_OUTROS = [
        'ODONTOLOGIA' => ['rotulo' => 'Cirurgião Dentista', 'limites' => ['A' => 20, 'B' => 20]],
        'MEDICINA VETERINÁRIA' => ['rotulo' => 'Médico Veterinário', 'limites' => ['B' => 20]],
    ];

    public const STATUS = [
        'enviada' => ['label' => 'Aguardando análise', 'classe' => 'bg-blue-50 text-blue-700 ring-blue-200', 'dot' => 'bg-blue-500'],
        'em_analise' => ['label' => 'Em análise', 'classe' => 'bg-amber-50 text-amber-800 ring-amber-200', 'dot' => 'bg-amber-500'],
        'liberada' => ['label' => 'Liberada', 'classe' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'indeferida' => ['label' => 'Indeferida', 'classe' => 'bg-red-50 text-red-700 ring-red-200', 'dot' => 'bg-red-500'],
        'cancelada' => ['label' => 'Cancelada', 'classe' => 'bg-slate-100 text-slate-600 ring-slate-200', 'dot' => 'bg-slate-400'],
    ];

    protected $fillable = [
        'processo_id',
        'receituario_id',
        'ano',
        'numero_sequencial',
        'numero',
        'quantidades',
        'unidade',
        'justificativa',
        'requisitante',
        'declaracoes',
        'usuario_externo_id',
        'assinado_em',
        'ip_address',
        'user_agent',
        'status',
        'quantidades_liberadas',
        'documentos_liberacao',
        'observacao_vigilancia',
        'analisado_por',
        'analisado_em',
        'cancelado_em',
    ];

    protected $casts = [
        'quantidades' => 'array',
        'requisitante' => 'array',
        'declaracoes' => 'array',
        'quantidades_liberadas' => 'array',
        'documentos_liberacao' => 'array',
        'assinado_em' => 'datetime',
        'analisado_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    public function processo()
    {
        return $this->belongsTo(Processo::class);
    }

    public function receituario()
    {
        return $this->belongsTo(Receituario::class);
    }

    public function usuarioExterno()
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuario_externo_id');
    }

    public function analisadoPor()
    {
        return $this->belongsTo(UsuarioInterno::class, 'analisado_por');
    }

    public function getSituacaoAttribute(): array
    {
        return self::STATUS[$this->status] ?? ['label' => ucfirst((string) $this->status), 'classe' => 'bg-slate-100 text-slate-600 ring-slate-200', 'dot' => 'bg-slate-400'];
    }

    public function aguardandoAnalise(): bool
    {
        return in_array($this->status, ['enviada', 'em_analise'], true);
    }

    /**
     * Requisição do profissional ainda aguardando a Vigilância (no processo ou em qualquer processo do mesmo cadastro).
     * Enquanto existir, ele não pode pedir outra.
     */
    public static function emAndamentoPara(Processo $processo): ?self
    {
        $receituarioId = $processo->estabelecimento?->receituario?->id;

        return self::query()
            ->whereIn('status', ['enviada', 'em_analise'])
            ->where(fn ($q) => $q->where('processo_id', $processo->id)
                ->when($receituarioId, fn ($q2) => $q2->orWhere('receituario_id', $receituarioId)))
            ->latest('id')
            ->first();
    }

    /**
     * Linhas pedidas (modalidade × tipo com quantidade > 0), com o que foi liberado e o documento do SNCR.
     */
    public function linhasPedidas(): array
    {
        $linhas = [];
        foreach (self::MODALIDADES as $modalidade => $rotulo) {
            foreach (self::TIPOS_NOTIFICACAO as $tipo) {
                $pedido = (int) ($this->quantidades[$modalidade][$tipo] ?? 0);
                if ($pedido <= 0) {
                    continue;
                }
                $linhas[] = [
                    'modalidade' => $modalidade,
                    'rotulo_modalidade' => $rotulo,
                    'tipo' => $tipo,
                    'nome_tipo' => self::NOMES_TIPO[$tipo],
                    'pedido' => $pedido,
                    'liberado' => $this->quantidades_liberadas !== null ? (int) ($this->quantidades_liberadas[$modalidade][$tipo] ?? 0) : null,
                    'documento_id' => $this->documentos_liberacao[$modalidade][$tipo] ?? null,
                ];
            }
        }

        return $linhas;
    }

    /**
     * Documentos de numeração (SNCR) anexados na liberação, indexados por id
     */
    public function documentosLiberados()
    {
        $ids = collect($this->documentos_liberacao ?? [])->flatten()->filter()->all();

        return $ids ? ProcessoDocumento::whereIn('id', $ids)->get()->keyBy('id') : collect();
    }

    public function podeSerCancelada(): bool
    {
        return $this->status === 'enviada';
    }

    public function totalQuantidade(): int
    {
        return collect($this->quantidades ?? [])->flatten()->sum();
    }

    /**
     * "200 numerações" · "1 numeração" · "4 blocos" (requisições antigas)
     */
    public function rotuloQuantidade(?int $quantidade = null): string
    {
        $quantidade ??= $this->totalQuantidade();
        $unidade = self::UNIDADES[$this->unidade ?? 'numeracoes'] ?? self::UNIDADES['numeracoes'];

        return number_format($quantidade, 0, ',', '.') . ' ' . ($quantidade === 1 ? $unidade['singular'] : $unidade['plural']);
    }

    public function emBlocos(): bool
    {
        return ($this->unidade ?? 'numeracoes') === 'blocos';
    }

    /**
     * Limite de numerações por tipo (física + eletrônica) para a especialidade do profissional.
     * Devolve [tipo => ['limite' => int, 'justificar' => bool, 'motivo' => string]].
     */
    public static function limitesPara(?string $especialidade): array
    {
        $especialidade = mb_strtoupper(trim((string) $especialidade), 'UTF-8');
        $contem = fn (array $trechos) => collect($trechos)->contains(fn ($t) => $especialidade !== '' && str_contains($especialidade, $t));

        foreach (self::PARAMETROS_OUTROS as $chave => $outro) {
            if ($especialidade === $chave) {
                return collect(self::TIPOS_NOTIFICACAO)->mapWithKeys(fn ($tipo) => [$tipo => [
                    'limite' => $outro['limites'][$tipo] ?? 0,
                    'justificar' => !isset($outro['limites'][$tipo]),
                    'motivo' => $outro['rotulo'],
                ]])->all();
            }
        }

        return collect(self::PARAMETROS_ENTREGA)->map(function ($p) use ($contem) {
            $especialista = $contem($p['especialidades']);

            return [
                'limite' => $especialista ? $p['especialista'] : $p['outras'],
                'justificar' => !$especialista && !empty($p['outras_justificativa']),
                'motivo' => $especialista ? 'especialista' : 'outras especialidades',
            ];
        })->all();
    }

    /**
     * Tipos pedidos acima do limite (ou que sempre exigem justificativa) para a especialidade.
     */
    public static function tiposQuePedemJustificativa(array $quantidades, ?string $especialidade): array
    {
        $limites = self::limitesPara($especialidade);

        return collect(self::TIPOS_NOTIFICACAO)->filter(function ($tipo) use ($quantidades, $limites) {
            $total = collect(array_keys(self::MODALIDADES))->sum(fn ($m) => (int) ($quantidades[$m][$tipo] ?? 0));

            return $total > 0 && ($limites[$tipo]['justificar'] || $total > $limites[$tipo]['limite']);
        })->values()->all();
    }

    /**
     * Resumo legível: "Física: A 2 · B 5 | Eletrônica: B 3"
     */
    public function resumoQuantidades(?array $quantidades = null): array
    {
        $quantidades ??= $this->quantidades ?? [];
        $resumo = [];

        foreach (self::MODALIDADES as $chave => $rotulo) {
            $itens = collect(self::TIPOS_NOTIFICACAO)
                ->filter(fn ($tipo) => (int) ($quantidades[$chave][$tipo] ?? 0) > 0)
                ->map(fn ($tipo) => ['tipo' => $tipo, 'quantidade' => (int) $quantidades[$chave][$tipo]])
                ->values()
                ->all();

            if ($itens) {
                $resumo[$chave] = ['rotulo' => $rotulo, 'itens' => $itens];
            }
        }

        return $resumo;
    }

    /**
     * Retrato dos dados do requisitante a partir do cadastro de receituário.
     */
    public static function dadosRequisitante(Receituario $receituario): array
    {
        $isPessoaJuridica = in_array($receituario->tipo, ['instituicao', 'secretaria'], true);
        $municipio = $receituario->relationLoaded('municipio') ? $receituario->getRelation('municipio') : $receituario->municipio()->first();

        return [
            'tipo' => $receituario->tipo,
            'tipo_nome' => $receituario->tipo_nome,
            'nome' => $isPessoaJuridica ? ($receituario->razao_social ?: $receituario->nome) : ($receituario->nome ?: $receituario->razao_social),
            'documento' => $isPessoaJuridica ? ($receituario->cnpj_formatado ?: $receituario->cpf_formatado) : ($receituario->cpf_formatado ?: $receituario->cnpj_formatado),
            'conselho' => $isPessoaJuridica
                ? trim(($receituario->responsavel_nome ? $receituario->responsavel_nome . ' · ' : '') . ($receituario->responsavel_crm ?? ''), ' ·')
                : ($receituario->numero_conselho_classe ?: $receituario->numero_crm),
            'especialidade' => $isPessoaJuridica ? $receituario->responsavel_especialidade : $receituario->especialidade,
            'endereco' => $receituario->endereco,
            'municipio' => $municipio?->nome ?? $receituario->getAttribute('municipio'),
            'cep' => $receituario->cep,
            'email' => $receituario->email,
        ];
    }

    /**
     * Próximo número da requisição no ano (2026/0001), com trava para evitar duplicidade.
     */
    public static function gerarNumero(int $ano): array
    {
        DB::statement('SELECT pg_advisory_xact_lock(?)', [hexdec(substr(md5('receituario_requisicoes_' . $ano), 0, 7))]);

        $sequencial = (int) self::withTrashed()->where('ano', $ano)->max('numero_sequencial') + 1;

        return [
            'ano' => $ano,
            'numero_sequencial' => $sequencial,
            'numero' => $ano . '/' . str_pad((string) $sequencial, 4, '0', STR_PAD_LEFT),
        ];
    }
}
