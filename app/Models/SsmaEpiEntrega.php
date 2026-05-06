<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SsmaEpiEntrega extends Model
{
    protected $table = 'ssma_epi_entregas';

    public const STATUS = [
        'pendente' => 'Pendente',
        'entregue' => 'Entregue',
        'substituido' => 'Substituído',
        'vencido' => 'Vencido',
        'cancelado' => 'Cancelado',
    ];

    protected $fillable = [
        'colaborador',
        'cargo',
        'epi_obrigatorio',
        'ca_numero',
        'validade_ca',
        'data_entrega',
        'data_substituicao',
        'status',
        'evidencia_path',
        'observacao',
    ];

    protected $casts = [
        'validade_ca' => 'date',
        'data_entrega' => 'date',
        'data_substituicao' => 'date',
    ];

    public function rotuloStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function scopeFiltrarEpi(Builder $q, ?string $busca = null, ?string $status = null): Builder
    {
        if ($busca !== null && $busca !== '') {
            $b = "%{$busca}%";
            $q->where(function (Builder $w) use ($b) {
                $w->where('colaborador', 'like', $b)
                    ->orWhere('cargo', 'like', $b)
                    ->orWhere('epi_obrigatorio', 'like', $b)
                    ->orWhere('ca_numero', 'like', $b);
            });
        }
        if ($status !== null && $status !== '') {
            $q->where('status', $status);
        }

        return $q;
    }

    public function caEstaVencido(): bool
    {
        if ($this->validade_ca === null) {
            return false;
        }

        return $this->validade_ca->copy()->startOfDay()->lt(now()->startOfDay());
    }
}
