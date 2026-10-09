<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receituario extends Model
{
    use SoftDeletes;

    /** Especialidades e áreas de atuação usadas no formulário e na leitura da carteira. */
    public const ESPECIALIDADES = [
        'ACUPUNTURA', 'ALERGIA E IMUNOLOGIA', 'ANESTESIOLOGIA', 'ANGIOLOGIA', 'CARDIOLOGIA',
        'CIRURGIA CARDIOVASCULAR', 'CIRURGIA DA MÃO', 'CIRURGIA DE CABEÇA E PESCOÇO', 'CIRURGIA DO APARELHO DIGESTIVO',
        'CIRURGIA GERAL', 'CIRURGIA ONCOLÓGICA', 'CIRURGIA PEDIÁTRICA', 'CIRURGIA PLÁSTICA', 'CIRURGIA TORÁCICA',
        'CIRURGIA VASCULAR', 'CLÍNICA MÉDICA', 'COLOPROCTOLOGIA', 'DERMATOLOGIA', 'ENDOCRINOLOGIA E METABOLOGIA',
        'ENDOSCOPIA', 'GASTROENTEROLOGIA', 'GENÉTICA MÉDICA', 'GERIATRIA', 'GINECOLOGIA E OBSTETRÍCIA',
        'HEMATOLOGIA E HEMOTERAPIA', 'HOMEOPATIA', 'INFECTOLOGIA', 'MASTOLOGIA', 'MEDICINA DE EMERGÊNCIA',
        'MEDICINA DE FAMÍLIA E COMUNIDADE', 'MEDICINA DO TRABALHO', 'MEDICINA DE TRÁFEGO', 'MEDICINA ESPORTIVA',
        'MEDICINA FÍSICA E REABILITAÇÃO', 'MEDICINA INTENSIVA', 'MEDICINA LEGAL E PERÍCIA MÉDICA', 'MEDICINA NUCLEAR',
        'MEDICINA PREVENTIVA E SOCIAL', 'NEFROLOGIA', 'NEUROCIRURGIA', 'NEUROLOGIA', 'NUTROLOGIA', 'OFTALMOLOGIA',
        'ONCOLOGIA CLÍNICA', 'ORTOPEDIA E TRAUMATOLOGIA', 'OTORRINOLARINGOLOGIA', 'PATOLOGIA',
        'PATOLOGIA CLÍNICA/MEDICINA LABORATORIAL', 'PEDIATRIA', 'PNEUMOLOGIA', 'PSIQUIATRIA',
        'RADIOLOGIA E DIAGNÓSTICO POR IMAGEM', 'RADIOTERAPIA', 'REUMATOLOGIA', 'UROLOGIA', 'ODONTOLOGIA',
        'MEDICINA VETERINÁRIA',
        // Áreas de atuação reconhecidas pela Comissão Mista de Especialidades.
        'ADMINISTRAÇÃO EM SAÚDE', 'ALERGIA E IMUNOLOGIA PEDIÁTRICA', 'ANGIORRADIOLOGIA E CIRURGIA ENDOVASCULAR',
        'ATENDIMENTO AO QUEIMADO', 'AUDITORIA MÉDICA', 'CARDIOINTENSIVISMO', 'CARDIOLOGIA PEDIÁTRICA',
        'CIRURGIA BARIÁTRICA', 'CIRURGIA CRÂNIO-MAXILO-FACIAL', 'CIRURGIA DO TRAUMA',
        'CIRURGIA VIDEOLAPAROSCÓPICA', 'CITOPATOLOGIA', 'DENSITOMETRIA ÓSSEA', 'DOR', 'ECOCARDIOGRAFIA',
        'ECOGRAFIA VASCULAR COM DOPPLER', 'ELETROFISIOLOGIA CLÍNICA INVASIVA', 'EMERGÊNCIA PEDIÁTRICA',
        'ENDOCRINOLOGIA PEDIÁTRICA', 'ENDOSCOPIA DIGESTIVA', 'ENDOSCOPIA GINECOLÓGICA',
        'ENDOSCOPIA RESPIRATÓRIA', 'ERGOMETRIA', 'ESTIMULAÇÃO CARDÍACA ELETRÔNICA IMPLANTÁVEL',
        'FONIATRIA', 'GASTROENTEROLOGIA PEDIÁTRICA', 'HANSENOLOGIA', 'HEMATOLOGIA E HEMOTERAPIA PEDIÁTRICA',
        'HEMODINÂMICA E CARDIOLOGIA INTERVENCIONISTA', 'HEPATOLOGIA', 'INFECTOLOGIA HOSPITALAR',
        'INFECTOLOGIA PEDIÁTRICA', 'MAMOGRAFIA', 'MEDICINA AEROESPACIAL', 'MEDICINA DO ADOLESCENTE',
        'MEDICINA DO SONO', 'MEDICINA FETAL', 'MEDICINA INTENSIVA PEDIÁTRICA',
        'MEDICINA MARÍTIMA E HIPERBÁRICA', 'MEDICINA PALIATIVA', 'MEDICINA TROPICAL', 'NEFROLOGIA PEDIÁTRICA',
        'NEONATOLOGIA', 'NEUROFISIOLOGIA CLÍNICA', 'NEUROLOGIA PEDIÁTRICA', 'NEURORRADIOLOGIA',
        'NUTRIÇÃO PARENTERAL E ENTERAL', 'NUTRIÇÃO PARENTERAL E ENTERAL PEDIÁTRICA', 'NUTROLOGIA PEDIÁTRICA',
        'ONCOGENÉTICA', 'ONCOLOGIA PEDIÁTRICA', 'PNEUMOLOGIA PEDIÁTRICA', 'PSICOGERIATRIA', 'PSICOTERAPIA',
        'PSIQUIATRIA DA INFÂNCIA E ADOLESCÊNCIA', 'PSIQUIATRIA FORENSE',
        'RADIOLOGIA INTERVENCIONISTA E ANGIORRADIOLOGIA', 'REPRODUÇÃO ASSISTIDA', 'REUMATOLOGIA PEDIÁTRICA',
        'SEXOLOGIA', 'TOXICOLOGIA MÉDICA', 'TRANSPLANTE DE MEDULA ÓSSEA',
        'ULTRASSONOGRAFIA EM GINECOLOGIA E OBSTETRÍCIA', 'ULTRASSONOGRAFIA GERAL', 'OUTRAS',
    ];

    protected $fillable = [
        'tipo',
        'nome',
        'cpf',
        'especialidade',
        'telefone',
        'telefone2',
        'numero_conselho_classe',
        'numero_crm',
        'carteira_conselho_path',
        'carteira_conselho_nome',
        'carteira_conselho_verso_path',
        'carteira_conselho_verso_nome',
        'carteira_conselho_leitura',
        'comprovante_endereco_path',
        'comprovante_endereco_nome',
        'comprovante_endereco_leitura',
        'declaracao_endereco',
        'documento_assinado_path',
        'documento_assinado_nome',
        'documento_assinado_enviado_em',
        'analisado_por',
        'analisado_em',
        'motivo_rejeicao',
        'analise_documentos',
        'estabelecimento_id',
        'endereco',
        'endereco_residencial',
        'cep',
        'municipio',
        'municipio_id',
        'email',
        'razao_social',
        'cnpj',
        'responsavel_nome',
        'responsavel_cpf',
        'responsavel_crm',
        'responsavel_especialidade',
        'responsavel_telefone',
        'responsavel_telefone2',
        'locais_trabalho',
        'status',
        'observacoes',
        'processo_id',
        'usuario_criacao_id',
        'usuario_atualizacao_id',
        'usuario_externo_id',
        'solicitante_proprio',
    ];

    protected $casts = [
        'locais_trabalho' => 'array',
        'solicitante_proprio' => 'boolean',
        'carteira_conselho_leitura' => 'array',
        'comprovante_endereco_leitura' => 'array',
        'declaracao_endereco' => 'array',
        'documento_assinado_enviado_em' => 'datetime',
        'analisado_em' => 'datetime',
        'analise_documentos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com Município
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Relacionamento com Processo
     */
    public function processo()
    {
        return $this->belongsTo(Processo::class);
    }

    /**
     * Usuário que criou
     */
    public function usuarioCriacao()
    {
        return $this->belongsTo(UsuarioInterno::class, 'usuario_criacao_id');
    }

    /**
     * Usuário que atualizou
     */
    public function usuarioAtualizacao()
    {
        return $this->belongsTo(UsuarioInterno::class, 'usuario_atualizacao_id');
    }

    /**
     * Usuário externo (área da empresa) que fez a solicitação
     */
    public function usuarioExterno()
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuario_externo_id');
    }

    /** Tipos de vínculo de usuários externos ao cadastro (todos com nível gestor). */
    public const TIPOS_VINCULO = [
        'profissional' => 'Profissional',
        'funcionario' => 'Funcionário',
    ];

    /**
     * Usuários externos vinculados ao cadastro, além de quem cadastrou
     */
    public function usuariosVinculados()
    {
        return $this->belongsToMany(UsuarioExterno::class, 'receituario_usuario_externo')
            ->withPivot('tipo_vinculo', 'nivel_acesso', 'vinculado_por_interno_id', 'vinculado_por_externo_id')
            ->withTimestamps();
    }

    /**
     * Cadastros que o usuário externo pode acessar: os que cadastrou e os que está vinculado
     */
    public function scopeAcessivelPor($query, ?int $usuarioExternoId)
    {
        return $query->where(function ($q) use ($usuarioExternoId) {
            $q->where('usuario_externo_id', $usuarioExternoId)
                ->orWhereHas('usuariosVinculados', fn ($v) => $v->where('usuarios_externos.id', $usuarioExternoId));
        });
    }

    /**
     * Cadastro interno (estabelecimento oculto) onde ficam os processos de receituário do profissional
     */
    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    /**
     * Usuário da Vigilância que aprovou ou rejeitou o cadastro
     */
    public function analisadoPor()
    {
        return $this->belongsTo(UsuarioInterno::class, 'analisado_por');
    }

    /**
     * Situações do cadastro: rótulo e cores (badge)
     */
    public const SITUACOES = [
        'aguardando_assinatura' => ['label' => 'Aguardando documento assinado', 'classe' => 'bg-amber-50 text-amber-800 ring-amber-200'],
        'pendente' => ['label' => 'Cadastro em análise', 'classe' => 'bg-blue-50 text-blue-800 ring-blue-200'],
        'ativo' => ['label' => 'Cadastro aprovado', 'classe' => 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'rejeitado' => ['label' => 'Correção solicitada', 'classe' => 'bg-red-50 text-red-800 ring-red-200'],
        'inativo' => ['label' => 'Inativo', 'classe' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];

    public function getSituacaoAttribute(): array
    {
        return self::SITUACOES[$this->status] ?? ['label' => ucfirst((string) $this->status), 'classe' => 'bg-slate-100 text-slate-600 ring-slate-200'];
    }

    public function isAprovado(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Solicitação feita pela área da empresa (passa por documento assinado + análise da Vigilância)
     */
    public function isSolicitacaoExterna(): bool
    {
        return (bool) $this->usuario_externo_id;
    }

    // ===================== Análise documento a documento =====================

    /**
     * Documentos do CADASTRO do profissional, analisados um a um pela Vigilância (como os de um processo).
     * A ficha cadastral assinada é gerada no passo 4 do cadastro. A requisição de receituário não faz parte
     * do cadastro: ela pertence ao processo de receituário.
     */
    public const DOCUMENTOS = [
        'carteira' => ['nome' => 'Carteira do conselho', 'detalhe' => 'CRM, CRO ou CRMV · frente e verso', 'a' => 'a'],
        'comprovante' => ['nome' => 'Comprovante de endereço', 'detalhe' => 'Água, energia ou telefone fixo', 'a' => 'o'],
        'assinado' => ['nome' => 'Ficha cadastral assinada', 'detalhe' => 'Assinada pelo gov.br ou à mão, com carimbo', 'a' => 'a'],
    ];

    /**
     * "Carteira do conselho aprovada" / "Comprovante de endereço aprovado"
     */
    public static function frase(string $documento, string $participio): string
    {
        $doc = self::DOCUMENTOS[$documento];

        return $doc['nome'] . ' ' . $participio . $doc['a'];
    }

    public const STATUS_DOCUMENTO = [
        'pendente' => ['label' => 'Em análise', 'classe' => 'bg-blue-50 text-blue-700 ring-blue-200'],
        'aprovado' => ['label' => 'Aprovado', 'classe' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'rejeitado' => ['label' => 'Rejeitado', 'classe' => 'bg-red-50 text-red-700 ring-red-200'],
        'nao_enviado' => ['label' => 'Não enviado', 'classe' => 'bg-amber-50 text-amber-800 ring-amber-200'],
    ];

    public function temDocumento(string $documento): bool
    {
        return (bool) match ($documento) {
            'carteira' => $this->carteira_conselho_path,
            'comprovante' => $this->comprovante_endereco_path,
            'assinado' => $this->documento_assinado_path,
            default => false,
        };
    }

    /**
     * Documentos que fazem parte da análise deste cadastro (os que foram anexados)
     */
    public function documentosDaAnalise(): array
    {
        return array_values(array_filter(array_keys(self::DOCUMENTOS), fn ($doc) => $this->temDocumento($doc)));
    }

    public function analiseDocumento(string $documento): array
    {
        return ($this->analise_documentos ?? [])[$documento] ?? [];
    }

    public function statusDocumento(string $documento): string
    {
        if (!$this->temDocumento($documento)) {
            return 'nao_enviado';
        }

        // Cadastros aprovados antes da análise por documento: todos contam como aprovados
        return $this->analiseDocumento($documento)['status'] ?? ($this->status === 'ativo' ? 'aprovado' : 'pendente');
    }

    /**
     * Registra a decisão da Vigilância sobre um documento e atualiza a situação do cadastro
     */
    public function analisarDocumento(string $documento, string $status, ?string $motivo, ?int $usuarioId): void
    {
        $analises = $this->analise_documentos ?? [];
        $analises[$documento] = array_merge($analises[$documento] ?? [], [
            'status' => $status,
            'motivo' => $status === 'rejeitado' ? $motivo : null,
            'analisado_por' => $usuarioId,
            'analisado_em' => now()->toIso8601String(),
        ]);
        $this->analise_documentos = $analises;
        $this->recalcularStatus($usuarioId);
    }

    /**
     * A Vigilância revalida um documento já analisado: ele volta para pendente (nova análise).
     * Uma rejeição desfeita fica no histórico; o cadastro aprovado volta para "em análise".
     */
    public function revalidarDocumento(string $documento, ?int $usuarioId): void
    {
        $analises = $this->analise_documentos ?? [];
        $atual = $analises[$documento] ?? [];
        $historico = $atual['historico'] ?? [];
        if (($atual['status'] ?? null) === 'rejeitado') {
            $historico[] = [
                'motivo' => $atual['motivo'] ?? null,
                'rejeitado_em' => $atual['analisado_em'] ?? null,
                'revalidado_em' => now()->toIso8601String(),
                'revalidado_por' => $usuarioId,
            ];
        }
        $analises[$documento] = ['status' => 'pendente', 'historico' => $historico];
        $this->analise_documentos = $analises;
        $this->recalcularStatus($usuarioId);
    }

    /**
     * A empresa reenviou um documento rejeitado: guarda o histórico e volta para análise
     */
    public function documentoReenviado(string $documento, ?string $arquivoAnterior): void
    {
        $analises = $this->analise_documentos ?? [];
        $atual = $analises[$documento] ?? [];
        $historico = $atual['historico'] ?? [];
        if (($atual['status'] ?? null) === 'rejeitado') {
            $historico[] = [
                'motivo' => $atual['motivo'] ?? null,
                'rejeitado_em' => $atual['analisado_em'] ?? null,
                'arquivo_anterior' => $arquivoAnterior,
                'reenviado_em' => now()->toIso8601String(),
            ];
        }
        $analises[$documento] = ['status' => 'pendente', 'historico' => $historico];
        $this->analise_documentos = $analises;
        $this->recalcularStatus(null);
    }

    /**
     * Situação do cadastro a partir dos documentos:
     * algum rejeitado → rejeitado (correção solicitada) · todos aprovados → ativo (cadastro aprovado)
     * demais casos → pendente (cadastro em análise)
     */
    public function recalcularStatus(?int $usuarioId): void
    {
        $situacoes = collect($this->documentosDaAnalise())->map(fn ($doc) => $this->statusDocumento($doc));
        $eraAprovado = $this->status === 'ativo';

        if ($situacoes->contains('rejeitado')) {
            $this->status = 'rejeitado';
        } elseif ($situacoes->isNotEmpty() && $situacoes->every(fn ($s) => $s === 'aprovado')) {
            $this->status = 'ativo';
        } else {
            $this->status = 'pendente';
        }

        if ($this->status === 'ativo' && !$eraAprovado) {
            $this->analisado_por = $usuarioId;
            $this->analisado_em = now();
            $this->motivo_rejeicao = null;
        }
        $this->save();
    }

    /**
     * Abre no navegador um documento enviado: carteira do conselho (frente/verso), comprovante de endereço
     * ou o documento assinado pelo profissional
     */
    public function respostaDocumento(string $documento = 'frente')
    {
        [$caminho, $nome] = match ($documento) {
            'verso' => [$this->carteira_conselho_verso_path, $this->carteira_conselho_verso_nome],
            'comprovante' => [$this->comprovante_endereco_path, $this->comprovante_endereco_nome],
            'assinado' => [$this->documento_assinado_path, $this->documento_assinado_nome],
            default => [$this->carteira_conselho_path, $this->carteira_conselho_nome],
        };

        abort_unless($caminho && \Illuminate\Support\Facades\Storage::disk('local')->exists($caminho), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($caminho, $nome ?: 'documento-' . $documento, [], 'inline');
    }

    public function getSolicitanteDescricaoAttribute(): ?string
    {
        if (!$this->usuario_externo_id) {
            return null;
        }

        $nome = $this->usuarioExterno?->nome ?? 'Usuário externo';

        return match ($this->solicitante_proprio) {
            true => "Solicitado pelo próprio profissional ({$nome})",
            false => "Solicitado em nome do profissional por {$nome}",
            default => "Solicitado por {$nome}",
        };
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para pendentes
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Retorna o nome formatado do tipo
     */
    public function getTipoNomeAttribute()
    {
        $tipos = [
            'medico' => 'Médico, Cirurgião Dentista e Médico Veterinário',
            'instituicao' => 'Instituição (Hospital, Clínica e Similares)',
            'secretaria' => 'Secretaria de Saúde e Vigilância Sanitária',
            'talidomida' => 'Prescritor de Talidomida',
        ];

        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * Retorna o CPF formatado
     */
    public function getCpfFormatadoAttribute()
    {
        if (!$this->cpf) return null;
        
        $cpf = preg_replace('/[^0-9]/', '', $this->cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Retorna o CNPJ formatado
     */
    public function getCnpjFormatadoAttribute()
    {
        if (!$this->cnpj) return null;
        
        $cnpj = preg_replace('/[^0-9]/', '', $this->cnpj);
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    /**
     * Retorna o identificador principal (nome ou razão social)
     */
    public function getIdentificadorAttribute()
    {
        return $this->nome ?? $this->razao_social;
    }
}
