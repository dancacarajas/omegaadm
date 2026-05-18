<?php

namespace App\Models;

use App\Support\TstColaboradorAcesso;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SsmaTstAtividade extends Model
{
    protected $table = 'ssma_tst_atividades';

    protected $fillable = [
        'nome',
        'ativo',
        'exibir_no_app',
        'ordem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'exibir_no_app' => 'boolean',
        'ordem' => 'integer',
    ];

    public function registros(): HasMany
    {
        return $this->hasMany(SsmaTstRegistro::class, 'ssma_tst_atividade_id');
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    /** Atividades visíveis no app /registro-tst para colaboradores sem perfil SSMA/Administrador. */
    public function scopeParaAppColaborador($query)
    {
        return $query->where('ativo', true)->where('exibir_no_app', true);
    }

    /** Lista do app conforme o colaborador: SSMA/Admin veem todas ativas; demais só com "exibir no app". */
    public function scopeParaAppDoColaborador($query, Colaborador $colaborador)
    {
        if (TstColaboradorAcesso::veTodasAtividadesNoApp($colaborador)) {
            return $query->ativas();
        }

        return $query->paraAppColaborador();
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    public function scopeFiltrar($query, ?string $busca, ?string $status)
    {
        return $query
            ->when($busca !== null && $busca !== '', function ($q) use ($busca) {
                $q->where('nome', 'like', '%'.$busca.'%');
            })
            ->when($status === 'ativas', fn ($q) => $q->where('ativo', true))
            ->when($status === 'inativas', fn ($q) => $q->where('ativo', false));
    }
}
