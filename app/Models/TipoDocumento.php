<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'ativo',
        'visibilidade',
        'ordem',
        'tem_prazo',
        'prazo_padrao_dias',
        'prazo_notificacao',
        'permite_resposta',
        'exige_itens_atendimento',
        'prazo_analise_dias',
        'tipo_prazo_analise',
        'abrir_processo_automaticamente',
        'tipo_processo_codigo',
        'escopo_processos',
        'tipos_processo_permitidos',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'tem_prazo' => 'boolean',
        'prazo_notificacao' => 'boolean',
        'permite_resposta' => 'boolean',
        'exige_itens_atendimento' => 'boolean',
        'abrir_processo_automaticamente' => 'boolean',
        'tipos_processo_permitidos' => 'array',
        'prazo_padrao_dias' => 'integer',
        'prazo_analise_dias' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com modelos de documentos
     */
    public function modelosDocumento(): HasMany
    {
        return $this->hasMany(ModeloDocumento::class);
    }

    /**
     * Subcategorias do tipo de documento.
     * Ex.: tipo "Alvará Sanitário" -> subcategorias "Provisório", "Administrativo", "Definitivo".
     */
    public function subcategorias(): HasMany
    {
        return $this->hasMany(TipoDocumentoSubcategoria::class)
            ->orderBy('ordem')
            ->orderBy('nome');
    }

    /**
     * Apenas subcategorias ativas.
     */
    public function subcategoriasAtivas(): HasMany
    {
        return $this->subcategorias()->where('ativo', true);
    }

    /**
     * Tipos de documento de resposta vinculados
     */
    public function tiposDocumentoResposta(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(TipoDocumentoResposta::class, 'tipo_documento_tipo_resposta')
            ->withPivot('obrigatorio', 'ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    /**
     * Indica se o tipo pode ser usado ao criar documento dentro de um processo do tipo informado.
     * Sem processo ($codigoTipoProcesso nulo), o tipo está sempre disponível.
     */
    public function disponivelParaTipoProcesso(?string $codigoTipoProcesso): bool
    {
        if ($codigoTipoProcesso === null || $codigoTipoProcesso === '') {
            return true;
        }

        return match ($this->escopo_processos ?? 'todos') {
            'nenhum' => false,
            'especificos' => in_array($codigoTipoProcesso, $this->tipos_processo_permitidos ?? [], true),
            default => true,
        };
    }

    /**
     * Disponível para TODOS os tipos de processo informados (criação em lote em vários processos)
     */
    public function disponivelParaTiposProcesso(iterable $codigosTipoProcesso): bool
    {
        foreach ($codigosTipoProcesso as $codigo) {
            if (!$this->disponivelParaTipoProcesso($codigo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Texto curto do escopo, para listagens
     */
    public function getEscopoProcessosDescricaoAttribute(): string
    {
        return match ($this->escopo_processos ?? 'todos') {
            'nenhum' => 'Nenhum processo',
            'especificos' => TipoProcesso::whereIn('codigo', $this->tipos_processo_permitidos ?? [])->orderBy('nome')->pluck('nome')->implode(', ') ?: 'Nenhum tipo selecionado',
            default => 'Todos os processos',
        };
    }

    /**
     * Scope para buscar apenas tipos ativos
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para ordenar por ordem
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    /**
     * Scope para filtrar por visibilidade do usuário logado
     */
    public function scopeVisivelParaUsuario($query, $usuario = null)
    {
        if (!$usuario) {
            $usuario = auth('interno')->user();
        }
        if (!$usuario || $usuario->isAdmin()) {
            return $query; // Admin vê todos
        }
        if ($usuario->isEstadual()) {
            return $query->whereIn('visibilidade', ['todos', 'estadual']);
        }
        if ($usuario->isMunicipal()) {
            return $query->whereIn('visibilidade', ['todos', 'municipal']);
        }
        return $query;
    }
}
