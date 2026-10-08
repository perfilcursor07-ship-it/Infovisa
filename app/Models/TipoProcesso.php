<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProcesso extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'anual',
        'usuario_externo_pode_abrir',
        'exibir_aviso_abertura_empresa',
        'aviso_abertura_titulo',
        'aviso_abertura_mensagem',
        'aviso_abertura_confirmacao',
        'usuario_externo_pode_visualizar',
        'exibir_fila_publica',
        'prazo_fila_publica',
        'prazo_fila_publica_alto',
        'prazo_fila_publica_medio',
        'prazo_fila_publica_baixo',
        'exibir_aviso_prazo_fila',
        'unico_por_estabelecimento',
        'ativo',
        'ordem',
        'competencia',
        'municipios_descentralizados',
        'municipios_descentralizados_ids',
        'tipo_setor_id',
        'exclusivo_receituario',
    ];

    protected $casts = [
        'anual' => 'boolean',
        'usuario_externo_pode_abrir' => 'boolean',
        'exibir_aviso_abertura_empresa' => 'boolean',
        'usuario_externo_pode_visualizar' => 'boolean',
        'exibir_fila_publica' => 'boolean',
        'prazo_fila_publica' => 'integer',
        'prazo_fila_publica_alto' => 'integer',
        'prazo_fila_publica_medio' => 'integer',
        'prazo_fila_publica_baixo' => 'integer',
        'exibir_aviso_prazo_fila' => 'boolean',
        'unico_por_estabelecimento' => 'boolean',
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'municipios_descentralizados' => 'array',
        'municipios_descentralizados_ids' => 'array',
        'exclusivo_receituario' => 'boolean',
    ];

    /**
     * Retorna o prazo da fila pública baseado no grupo de risco do estabelecimento.
     * Se não houver prazo específico por risco, usa o prazo padrão.
     */
    public function getPrazoFilaPublicaPorRisco(?string $grupoRisco): ?int
    {
        if ($grupoRisco === 'alto' && $this->prazo_fila_publica_alto) {
            return $this->prazo_fila_publica_alto;
        }
        if (($grupoRisco === 'medio' || $grupoRisco === 'médio') && $this->prazo_fila_publica_medio) {
            return $this->prazo_fila_publica_medio;
        }
        if ($grupoRisco === 'baixo' && $this->prazo_fila_publica_baixo) {
            return $this->prazo_fila_publica_baixo;
        }
        // Fallback para o prazo padrão
        return $this->prazo_fila_publica;
    }

    /**
     * Relacionamento com o setor responsável pela análise inicial
     */
    public function tipoSetor()
    {
        return $this->belongsTo(TipoSetor::class);
    }

    public function setoresMunicipais()
    {
        return $this->hasMany(TipoProcessoSetorMunicipio::class, 'tipo_processo_id');
    }

    public function unidades()
    {
        return $this->belongsToMany(Unidade::class, 'tipo_processo_unidade');
    }

    /**
     * Scope para tipos ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para tipos que usuário externo pode abrir
     */
    public function scopeParaUsuarioExterno($query)
    {
        return $query->where('usuario_externo_pode_abrir', true)->where('ativo', true);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    public function isProcessoEspecial(): bool
    {
        return in_array($this->codigo, ['projeto_arquitetonico', 'analise_rotulagem'], true);
    }

    public function codigoAtividadeEspecial(): ?string
    {
        return match ($this->codigo) {
            'projeto_arquitetonico' => 'PROJ_ARQ',
            'analise_rotulagem' => 'ANAL_ROT',
            default => null,
        };
    }

    public function estabelecimentoPossuiAtividadeEspecial(Estabelecimento $estabelecimento): bool
    {
        $codigoEspecial = $this->codigoAtividadeEspecial();

        if (!$codigoEspecial) {
            return false;
        }

        return $estabelecimento->possuiAtividadeEspecial($codigoEspecial);
    }

    public function municipioDescentralizadoPara(Estabelecimento $estabelecimento): bool
    {
        $municipioId = $estabelecimento->municipio_id;
        $municipiosIds = $this->municipios_descentralizados_ids ?? [];

        if ($municipioId && !empty($municipiosIds)) {
            return in_array((int) $municipioId, array_map('intval', $municipiosIds), true);
        }

        $municipioNome = optional($estabelecimento->municipioRelacionado)->nome;

        if (!$municipioNome || empty($this->municipios_descentralizados)) {
            return false;
        }

        $municipioNorm = strtoupper(self::removerAcentosStatic(trim($municipioNome)));

        foreach ($this->municipios_descentralizados as $municipioDesc) {
            if (strtoupper(self::removerAcentosStatic(trim($municipioDesc))) === $municipioNorm) {
                return true;
            }
        }

        return false;
    }

    public function resolverEscopoCompetencia(Estabelecimento $estabelecimento): string
    {
        return match ($this->competencia) {
            'municipal' => 'municipal',
            'estadual_exclusivo' => 'estadual',
            'estadual' => $estabelecimento->isCompetenciaEstadual()
                ? 'estadual'
                : ($this->municipioDescentralizadoPara($estabelecimento) ? 'municipal' : 'estadual'),
            default => $estabelecimento->isCompetenciaEstadual() ? 'estadual' : 'municipal',
        };
    }

    public function disponivelParaEstabelecimento(Estabelecimento $estabelecimento): bool
    {
        // Processos de receituário só existem no cadastro interno do profissional (área de Receituários),
        // e esse cadastro só recebe processos de receituário
        if ($this->exclusivo_receituario || $estabelecimento->oculto_receituario) {
            return (bool) $this->exclusivo_receituario && (bool) $estabelecimento->oculto_receituario;
        }

        if ($this->isProcessoEspecial()) {
            if ($estabelecimento->possuiSomenteAtividadesEspeciais()) {
                return $this->estabelecimentoPossuiAtividadeEspecial($estabelecimento);
            }

            return true;
        }

        if ($estabelecimento->possuiSomenteAtividadesEspeciais()) {
            return false;
        }

        return true;
    }

    public function resolverSetorInicial(Estabelecimento $estabelecimento): ?TipoSetor
    {
        if ($this->resolverEscopoCompetencia($estabelecimento) === 'municipal' && $estabelecimento->municipio_id) {
            $setorMunicipal = $this->relationLoaded('setoresMunicipais')
                ? $this->setoresMunicipais->firstWhere('municipio_id', $estabelecimento->municipio_id)
                : $this->setoresMunicipais()->where('municipio_id', $estabelecimento->municipio_id)->with('tipoSetor')->first();

            if ($setorMunicipal?->tipoSetor) {
                return $setorMunicipal->tipoSetor;
            }
        }

        return $this->tipoSetor;
    }
    
    /**
     * Scope para tipos disponíveis para um usuário específico
     */
    public function scopeParaUsuario($query, $usuario)
    {
        // Administradores veem todos
        if ($usuario->isAdmin()) {
            return $query;
        }
        
        // Usuários estaduais veem todos (estaduais e municipais)
        if ($usuario->isEstadual()) {
            return $query;
        }
        
        // Usuários municipais veem apenas:
        // 1. Tipos municipais
        // 2. Tipos estaduais descentralizados para seu município
        if ($usuario->isMunicipal()) {
            $municipioUsuario = strtoupper(self::removerAcentosStatic(trim($usuario->municipio)));
            
            // Busca todos os tipos para filtrar
            $todosTipos = self::all();
            $idsPermitidos = [];
            
            foreach ($todosTipos as $tipo) {
                // Sempre inclui tipos municipais
                if ($tipo->competencia === 'municipal') {
                    $idsPermitidos[] = $tipo->id;
                    continue;
                }
                
                // Para tipos estaduais (estadual ou estadual_exclusivo), verifica descentralização
                if (in_array($tipo->competencia, ['estadual', 'estadual_exclusivo']) && $tipo->municipios_descentralizados) {
                    foreach ($tipo->municipios_descentralizados as $municipioDesc) {
                        $municipioDescNorm = strtoupper(self::removerAcentosStatic(trim($municipioDesc)));
                        
                        if ($municipioDescNorm === $municipioUsuario) {
                            $idsPermitidos[] = $tipo->id;
                            break;
                        }
                    }
                }
            }
            
            return $query->whereIn('id', $idsPermitidos);
        }
        
        return $query;
    }
    
    /**
     * Remove acentos de uma string (método estático)
     */
    private static function removerAcentosStatic($string)
    {
        $acentos = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        
        return strtr($string, $acentos);
    }
    
    /**
     * Remove acentos de uma string (método de instância)
     */
    private function removerAcentos($string)
    {
        return self::removerAcentosStatic($string);
    }
    
    /**
     * Verifica se um município tem acesso a este tipo de processo
     */
    public function municipioTemAcesso($municipio)
    {
        // Se for municipal, todos os municípios têm acesso
        if ($this->competencia === 'municipal') {
            return true;
        }
        
        // Se for estadual, verifica se o município está descentralizado
        if ($this->competencia === 'estadual') {
            if (!$this->municipios_descentralizados) {
                return false;
            }
            
            return in_array($municipio, $this->municipios_descentralizados);
        }
        
        return false;
    }
    
    /**
     * Adiciona um município à lista de descentralizados
     */
    public function adicionarMunicipioDescentralizado($municipio, $municipioId = null)
    {
        $municipios = $this->municipios_descentralizados ?? [];
        $municipiosIds = $this->municipios_descentralizados_ids ?? [];
        
        if (!in_array($municipio, $municipios)) {
            $municipios[] = $municipio;
            if ($municipioId) {
                $municipiosIds[] = $municipioId;
            }
            
            $this->municipios_descentralizados = $municipios;
            $this->municipios_descentralizados_ids = $municipiosIds;
            $this->save();
        }
        
        return $this;
    }
    
    /**
     * Remove um município da lista de descentralizados
     */
    public function removerMunicipioDescentralizado($municipio)
    {
        $municipios = $this->municipios_descentralizados ?? [];
        $municipios = array_values(array_filter($municipios, fn($m) => $m !== $municipio));
        
        $this->municipios_descentralizados = $municipios;
        $this->save();
        
        return $this;
    }
}
