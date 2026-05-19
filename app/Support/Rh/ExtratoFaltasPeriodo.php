<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExtratoFaltasPeriodo
{
    /**
     * @return array{
     *     inicio: string,
     *     fim: string,
     *     dias: int,
     *     total_faltas: int,
     *     colaboradores: list<array{
     *         colaborador: Colaborador,
     *         total_faltas: int,
     *         faltas: list<array{data: string, data_fmt: string, origem: string|null}>
     *     }>
     * }
     */
    public function montar(string $inicio, string $fim, ?int $colaboradorId = null): array
    {
        $inicioCarbon = Carbon::parse($inicio)->startOfDay();
        $fimCarbon = Carbon::parse($fim)->startOfDay();
        if ($fimCarbon->lt($inicioCarbon)) {
            [$inicioCarbon, $fimCarbon] = [$fimCarbon, $inicioCarbon];
        }

        $inicioStr = $inicioCarbon->toDateString();
        $fimStr = $fimCarbon->toDateString();

        $registros = FrequenciaRegistro::query()
            ->with('colaborador:id,nome,matricula,cargo,departamento,centro_custo')
            ->whereDate('data', '>=', $inicioStr)
            ->whereDate('data', '<=', $fimStr)
            ->where('status', 'falta')
            ->whereHas('colaborador', function ($q) use ($colaboradorId) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
                if ($colaboradorId !== null) {
                    $q->where('id', $colaboradorId);
                }
            })
            ->orderBy('data')
            ->get(['id', 'colaborador_id', 'data', 'origem']);

        /** @var Collection<int, Collection<int, FrequenciaRegistro>> $porColaborador */
        $porColaborador = $registros->groupBy('colaborador_id');

        $colaboradores = [];
        foreach ($porColaborador as $grupo) {
            /** @var FrequenciaRegistro $primeiro */
            $primeiro = $grupo->first();
            $colab = $primeiro->colaborador;
            if ($colab === null) {
                continue;
            }

            $faltas = $grupo->map(function (FrequenciaRegistro $r) {
                $data = $r->data instanceof Carbon ? $r->data : Carbon::parse($r->data);

                return [
                    'data' => $data->toDateString(),
                    'data_fmt' => $data->format('d/m/Y'),
                    'dia_semana' => $this->diaSemanaCurto($data),
                    'origem' => $this->rotuloOrigem($r->origem),
                    'registro_id' => $r->id,
                ];
            })->values()->all();

            $colaboradores[] = [
                'colaborador' => $colab,
                'total_faltas' => count($faltas),
                'faltas' => $faltas,
            ];
        }

        usort($colaboradores, fn ($a, $b) => $b['total_faltas'] <=> $a['total_faltas']
            ?: strcasecmp($a['colaborador']->nome, $b['colaborador']->nome));

        return [
            'inicio' => $inicioStr,
            'fim' => $fimStr,
            'dias' => max(1, $inicioCarbon->diffInDays($fimCarbon, false) + 1),
            'total_faltas' => $registros->count(),
            'colaborador_id' => $colaboradorId,
            'colaboradores' => $colaboradores,
        ];
    }

    private function diaSemanaCurto(Carbon $data): string
    {
        return match ((int) $data->isoWeekday()) {
            1 => 'SEG',
            2 => 'TER',
            3 => 'QUA',
            4 => 'QUI',
            5 => 'SEX',
            6 => 'SAB',
            default => 'DOM',
        };
    }

    private function rotuloOrigem(?string $origem): string
    {
        return match ($origem) {
            'csv_ponto' => 'Importação CSV',
            'afd' => 'Importação AFD',
            'grade' => 'Grade automática',
            'manual' => 'Manual',
            'app_colaborador' => 'App colaborador',
            default => $origem ? ucfirst(str_replace('_', ' ', $origem)) : '—',
        };
    }
}
