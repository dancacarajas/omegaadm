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

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class)->withTimestamps();
    }
}
