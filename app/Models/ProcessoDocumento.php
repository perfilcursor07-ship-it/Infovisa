<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessoDocumento extends Model
{
    protected $fillable = [
        'processo_id',
        'os_id',
        'atividade_index',
        'documento_substituido_id',
        'pasta_id',
        'usuario_id',
        'usuario_externo_id',
        'tipo_usuario',
        'nome_arquivo',
        'nome_original',
        'caminho',
        'extensao',
        'tamanho',
        'tipo_documento',
        'tipo_documento_obrigatorio_id',
        'observacoes',
        'status_aprovacao',
        'status',
        'motivo_rejeicao',
        'tentativas_envio',
        'aprovado_por',
        'aprovado_em',
        'codigo_validacao',
        'caminho_carimbado',
        'hash_arquivo',
        'historico_rejeicao',
        'reenviado_em',
    ];

    protected $casts = [
        'atividade_index' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'aprovado_em' => 'datetime',
        'reenviado_em' => 'datetime',
        'historico_rejeicao' => 'array',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Unidade::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'os_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioInterno::class, 'usuario_id');
    }

    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuario_externo_id');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioInterno::class, 'aprovado_por');
    }

    /**
     * Tipo de documento obrigatório associado
     */
    public function tipoDocumentoObrigatorio(): BelongsTo
    {
        return $this->belongsTo(TipoDocumentoObrigatorio::class, 'tipo_documento_obrigatorio_id');
    }

    /**
     * Documento que este substitui (quando for correção de rejeitado)
     */
    public function documentoSubstituido(): BelongsTo
    {
        return $this->belongsTo(ProcessoDocumento::class, 'documento_substituido_id');
    }

    /**
     * Documentos que substituíram este (histórico de tentativas)
     */
    public function substituicoes(): HasMany
    {
        return $this->hasMany(ProcessoDocumento::class, 'documento_substituido_id');
    }

    /**
     * Verifica se este documento é uma substituição de outro rejeitado
     * ou se tem histórico de rejeições (novo formato)
     */
    public function isSubstituicao(): bool
    {
        return $this->documento_substituido_id !== null ||
               ($this->historico_rejeicao && count($this->historico_rejeicao) > 0);
    }

    /**
     * Verifica se o arquivo já foi reenviado após uma rejeição
     */
    public function foiReenviado(): bool
    {
        return $this->reenviado_em !== null;
    }

    /**
     * Data a partir da qual o prazo de análise deve ser contado.
     *
     * Cada reenvio reinicia o prazo: a vigilância passa a ter o prazo cheio para analisar
     * o arquivo novo, e não o que restava do envio anterior que ela própria rejeitou.
     */
    public function dataReferenciaAnalise(): ?\Carbon\Carbon
    {
        return $this->reenviado_em ?? $this->created_at;
    }

    /**
     * Dias decorridos desde o envio que está em análise (conta do reenvio, quando houver)
     */
    public function diasEmAnalise(): int
    {
        $referencia = $this->dataReferenciaAnalise();

        return $referencia ? (int) $referencia->diffInDays(now()) : 0;
    }

    /**
     * Retorna o histórico completo de rejeições
     * Primeiro verifica o campo historico_rejeicao (novo formato)
     * Se não existir, usa a cadeia de substituições (formato antigo)
     */
    public function getHistoricoRejeicoes(): \Illuminate\Support\Collection
    {
        // Se tiver histórico no novo formato, retorna como collection de objetos
        if ($this->historico_rejeicao && count($this->historico_rejeicao) > 0) {
            return collect($this->historico_rejeicao)->map(function ($item) {
                return (object) [
                    'nome_original' => $item['arquivo_anterior'] ?? 'Arquivo',
                    'motivo_rejeicao' => $item['motivo'] ?? null,
                    'created_at' => isset($item['rejeitado_em']) ? \Carbon\Carbon::parse($item['rejeitado_em']) : null,
                ];
            });
        }
        
        // Fallback: percorre a cadeia de substituições para trás (formato antigo)
        $historico = collect();
        $atual = $this->documentoSubstituido;
        
        while ($atual) {
            if ($atual->status_aprovacao === 'rejeitado') {
                $historico->push($atual);
            }
            $atual = $atual->documentoSubstituido;
        }

        return $historico->sortBy('created_at')->values();
    }

    public function isPendente(): bool
    {
        return $this->status_aprovacao === 'pendente';
    }

    public function isAprovado(): bool
    {
        return $this->status_aprovacao === 'aprovado';
    }

    public function isRejeitado(): bool
    {
        return $this->status_aprovacao === 'rejeitado';
    }

    /**
     * Verifica se possui versão carimbada com validação (QR Code)
     */
    public function temVersaoCarimbada(): bool
    {
        return !empty($this->caminho_carimbado) && !empty($this->codigo_validacao);
    }

    /**
     * Gera código de validação único para o documento aprovado
     */
    public static function gerarCodigoValidacao(): string
    {
        do {
            $codigo = md5(uniqid(rand(), true));
            $existe = self::where('codigo_validacao', $codigo)->exists();
        } while ($existe);

        return $codigo;
    }

    public function pasta(): BelongsTo
    {
        return $this->belongsTo(ProcessoPasta::class, 'pasta_id');
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(ProcessoDocumentoAnotacao::class, 'processo_documento_id');
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    public function getIconeAttribute(): string
    {
        return match($this->extensao) {
            'pdf' => '📄',
            'doc', 'docx' => '📝',
            'xls', 'xlsx' => '📊',
            'jpg', 'jpeg', 'png', 'gif' => '🖼️',
            'zip', 'rar' => '📦',
            default => '📎'
        };
    }
}
