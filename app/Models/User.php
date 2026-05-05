<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Contrato;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'perfil_id',
        'todos_contratos',
        'name',
        'email',
        'telefone',
        'cargo',
        'password',
        'status',
        'ultimo_acesso_em',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ultimo_acesso_em' => 'datetime',
            'todos_contratos' => 'boolean',
        ];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    /**
     * Usuário sem perfil: mantém acesso total (compatibilidade). Com perfil ativo, aplica matriz de permissões.
     */
    public function temQualquerPermissaoNoModulo(string $modulo): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        $matriz = $perfil->permissoes[$modulo] ?? null;
        if (! is_array($matriz)) {
            return false;
        }

        foreach ($matriz as $permitido) {
            if ((bool) $permitido) {
                return true;
            }
        }

        return false;
    }

    public function podeAcaoNoModulo(string $modulo, string $acao): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        return (bool) data_get($perfil->permissoes, "{$modulo}.{$acao}", false);
    }

    /** Primeira rota permitida após login ou quando o painel não é permitido. */
    public function urlInicialAposLogin(): ?string
    {
        $mapa = [
            'dashboard' => fn () => route('dashboard'),
            'rh' => fn () => route('rh.dashboard'),
            'veiculos' => fn () => route('veiculos.index'),
            'sesmt' => fn () => route('sesmt.index'),
            'contratos' => fn () => route('contratos.index'),
            'patrimonial' => fn () => route('patrimonial.index'),
            'rdo' => fn () => route('rdo.index'),
            'acessos' => fn () => route('usuarios.index'),
        ];

        foreach ($mapa as $modulo => $resolver) {
            if ($this->temQualquerPermissaoNoModulo($modulo)) {
                return $resolver();
            }
        }

        return null;
    }

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class)->withTimestamps();
    }
}
