<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SsmaAmbientalRegistro extends Model
{
    protected $table = 'ssma_ambiental_registros';

    protected $fillable = [
        'competencia',
        'residuos_gerados',
        'residuos_destinados',
        'quantidade_residuos_destinados_corretamente',
        'evidencia_destinacao_path',
        'coleta_seletiva',
        'vazamentos_derramamentos',
        'produtos_quimicos',
        'armazenamento_residuos',
        'consumo_agua_m3',
        'consumo_energia_kwh',
        'ocorrencias_ambientais',
        'licencas_condicionantes',
        'acoes_ambientais_realizadas',
        'acoes_ambientais_concluidas',
        'campanhas_ambientais',
        'nao_conformidades_ambientais',
        'observacoes',
    ];

    protected $casts = [
        'competencia' => 'date',
        'quantidade_residuos_destinados_corretamente' => 'decimal:3',
        'consumo_agua_m3' => 'decimal:3',
        'consumo_energia_kwh' => 'decimal:3',
    ];

    public function scopeFiltrar(Builder $q, ?string $competenciaYm = null, ?string $busca = null): Builder
    {
        if ($competenciaYm !== null && $competenciaYm !== '') {
            try {
                $d = \Carbon\Carbon::createFromFormat('Y-m', $competenciaYm)->startOfMonth()->toDateString();
                $q->whereDate('competencia', $d);
            } catch (\Throwable) {
                // ignora filtro inválido
            }
        }
        if ($busca !== null && $busca !== '') {
            $b = "%{$busca}%";
            $q->where(function (Builder $w) use ($b) {
                $w->where('residuos_gerados', 'like', $b)
                    ->orWhere('residuos_destinados', 'like', $b)
                    ->orWhere('coleta_seletiva', 'like', $b)
                    ->orWhere('licencas_condicionantes', 'like', $b)
                    ->orWhere('observacoes', 'like', $b);
            });
        }

        return $q;
    }
}
