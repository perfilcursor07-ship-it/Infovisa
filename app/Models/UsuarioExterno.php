<?php

namespace App\Models;

use App\Enums\VinculoEstabelecimento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuarioExterno extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /** Módulos da área do usuário externo. */
    public const MODULOS = [
        'processos' => 'Licenciamento e processos',
        'receituario' => 'Receituário',
    ];

    /** Cache por requisição de temModulo(). */
    protected array $modulosAcessiveis = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'usuarios_externos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nome',
        'cpf',
        'email',
        'telefone',
        'vinculo_estabelecimento',
        'modulos',
        'password',
        'aceite_termos_em',
        'ip_aceite_termos',
        'ativo',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'aceite_termos_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'vinculo_estabelecimento' => VinculoEstabelecimento::class,
            'modulos' => 'array',
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     * 
     * Este método define qual campo é usado como identificador único
     * para autenticação (login), mas o ID do usuário continua sendo 'id'
     */
    public function getAuthIdentifierName()
    {
        return 'id'; // Mantém 'id' como identificador
    }

    /**
     * Get the name of the password field for authentication.
     * 
     * Define que o campo 'cpf' será usado como username no login
     */
    public function username()
    {
        return 'cpf';
    }

    /**
     * Acessor para formatar o CPF
     */
    public function getCpfFormatadoAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Acessor para formatar o telefone
     */
    public function getTelefoneFormatadoAttribute(): string
    {
        $telefone = preg_replace('/\D/', '', $this->telefone);
        
        if (strlen($telefone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        }
        
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }

    /**
     * Verifica se o usuário aceitou os termos
     */
    public function aceitouTermos(): bool
    {
        return !is_null($this->aceite_termos_em);
    }

    /**
     * Registra o aceite dos termos
     */
    public function registrarAceiteTermos(string $ip): void
    {
        $this->update([
            'aceite_termos_em' => now(),
            'ip_aceite_termos' => $ip,
        ]);
    }

    /**
     * Relacionamento com estabelecimentos vinculados
     */
    public function estabelecimentosVinculados()
    {
        return $this->belongsToMany(Estabelecimento::class, 'estabelecimento_usuario_externo')
            ->withPivot(['tipo_vinculo', 'observacao', 'created_at'])
            ->withTimestamps();
    }

    /**
     * Indica se o usuário acessa o módulo. Além do que foi escolhido no cadastro
     * (ou liberado pela Vigilância), quem está vinculado a um estabelecimento ou a um
     * cadastro de receituário acessa o módulo correspondente.
     */
    public function temModulo(string $modulo): bool
    {
        if (array_key_exists($modulo, $this->modulosAcessiveis)) {
            return $this->modulosAcessiveis[$modulo];
        }

        // Sem registro (contas anteriores aos módulos): acesso a tudo
        $acesso = in_array($modulo, $this->modulos ?? array_keys(self::MODULOS), true);

        if (!$acesso && $modulo === 'processos') {
            // O estabelecimento interno do receituário (oculto) não libera processos
            $acesso = Estabelecimento::where('oculto_receituario', false)
                ->where(fn ($q) => $q->where('usuario_externo_id', $this->id)
                    ->orWhereHas('usuariosVinculados', fn ($v) => $v->where('usuario_externo_id', $this->id)))
                ->exists();
        }

        if (!$acesso && $modulo === 'receituario') {
            $acesso = Receituario::acessivelPor($this->id)->exists();
        }

        return $this->modulosAcessiveis[$modulo] = $acesso;
    }

    /**
     * Página inicial conforme os módulos do usuário.
     */
    public function rotaInicial(): string
    {
        if ($this->temModulo('processos')) {
            return 'company.dashboard';
        }

        return $this->temModulo('receituario') ? 'company.receituarios.index' : 'company.perfil.index';
    }
}
