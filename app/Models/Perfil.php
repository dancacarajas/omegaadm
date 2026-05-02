<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    protected $table = 'perfis';

    protected $fillable = [
        'nome',
        'descricao',
        'permissoes',
        'ativo',
    ];

    protected $casts = [
        'permissoes' => 'array',
        'ativo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
