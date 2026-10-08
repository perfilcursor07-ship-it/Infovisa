<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Requisição de notificação e/ou numeração de receituário (Portaria 344/98).
 * Feita pela empresa dentro do processo de receituário; cada pedido é individual
 * e só vale depois de liberado pela Vigilância Sanitária.
 */
class ReceituarioRequisicao extends Model
{
    use SoftDeletes;

    protected $table = 'receituario_requisicoes';

    public const TIPOS_NOTIFICACAO = ['A', 'B', 'B2', 'C2', 'C3'];

    public const MODALIDADES = [
        'fisica' => 'Física',
        'eletronica' => 'Eletrônica',
    ];

    /** Quantidade máxima aceita no formulário por tipo/modalidade (o parâmetro real é avaliado pela Vigilância). */
    public const QUANTIDADE_MAXIMA = 99;

    public const EMAIL_RECEITUARIO = 'receituario.to@gmail.com';

    public const DECLARACOES = [
        'controle_especial' => 'Solicito notificações de receitas para medicamentos sujeitos ao controle especial da Portaria 344/98.',
        'envio_confeccao' => 'Declaro estar ciente que, após a confecção de notificações físicas das receitas B, B2 e C2, devo enviar para o e-mail ' . self::EMAIL_RECEITUARIO . ' cópia de uma folha de cada tipo de receituário confeccionado e a autorização emitida devidamente carimbada e assinada pelo representante legal da gráfica no prazo de 30 dias.',
        'suspensao' => 'Declaro estar ciente que o não cumprimento das instruções acima acarretará a suspensão da retirada de notificações de receitas (A e C3) e/ou numeração (B, B2 e C2) até que regularize a pendência.',
        'veracidade' => 'Declaro para os devidos fins que todas as informações prestadas e documentos apresentados são verdadeiros, assumindo a responsabilidade administrativa, civil e criminal pelos mesmos.',
        'intransferivel' => 'Declaro estar ciente que, conforme o Art. 35 § 7º da Portaria 344/98, a "Notificação de Receita é personalizada e intransferível", sendo infração sanitária emprestar ou manter notificação de receita assinada para preenchimento de outrem.',
    ];

    /** Parâmetros de entrega definidos pela DVISA (página de orientação). */
    public const PARAMETROS_ENTREGA = [
        ['tipo' => 'A', 'especialista' => 'Até 10 blocos – neurologista, psiquiatra', 'outras' => 'Até 3 blocos'],
        ['tipo' => 'B', 'especialista' => 'Até 30 blocos – neurologista, psiquiatra', 'outras' => 'Até 5 blocos'],
        ['tipo' => 'B2', 'especialista' => 'Até 10 blocos – nutrólogo, endocrinologista', 'outras' => '1 bloco'],
        ['tipo' => 'C2', 'especialista' => 'Até 10 blocos – dermatologista', 'outras' => '1 bloco mediante justificativa'],
        ['tipo' => 'C3', 'especialista' => '1 bloco', 'outras' => '1 bloco'],
    ];

    public const PARAMETROS_OUTROS = [
        'Cirurgião Dentista' => '1 bloco A e 1 bloco B',
        'Médico Veterinário' => '1 bloco B',
        'Secretaria de Saúde / Vigilância Sanitária' => '3 A e 6 B por médico da rede pública municipal',
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
        'justificativa',
        'requisitante',
        'declaracoes',
        'usuario_externo_id',
        'assinado_em',
        'ip_address',
        'user_agent',
        'status',
        'quantidades_liberadas',
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

    public function podeSerCancelada(): bool
    {
        return $this->status === 'enviada';
    }

    public function totalBlocos(): int
    {
        return collect($this->quantidades ?? [])->flatten()->sum();
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
