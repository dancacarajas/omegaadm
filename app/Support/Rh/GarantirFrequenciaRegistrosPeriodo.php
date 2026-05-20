<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Support\EscalaPontoRegras;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Cria registros de falta (origem grade) em dias sem linha na frequência, como na apuração de ponto.
 */
final class GarantirFrequenciaRegistrosPeriodo
{
    /**
     * @param  list<string>|null  $identificadoresContrato
     * @param  bool  $somenteDiasComJornadaPrevista  Quando true, só dias em que a escala prevê trabalho (regularização).
     */
    public static function gerarFaltasEmDiasSemRegistro(
        Carbon $periodoInicio,
        Carbon $periodoFim,
        ?array $identificadoresContrato = null,
        bool $somenteDiasComJornadaPrevista = true
    ): int {
        [$ini, $fim] = self::normalizarIntervalo($periodoInicio, $periodoFim);
        $colaboradores = self::colaboradoresEscopo($identificadoresContrato);

        if ($colaboradores->isEmpty()) {
            return 0;
        }

        $ids = $colaboradores->pluck('id')->all();
        $existentes = FrequenciaRegistro::query()
            ->whereDate('data', '>=', $ini->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->whereIn('colaborador_id', $ids)
            ->get(['colaborador_id', 'data'])
            ->keyBy(fn (FrequenciaRegistro $r) => $r->colaborador_id.'|'.$r->data->format('Y-m-d'));

        $regras = app(EscalaPontoRegras::class);
        $agora = now();
        $linhas = [];

        foreach ($colaboradores as $colab) {
            foreach (CarbonPeriod::create($ini, $fim) as $dia) {
                $dataYmd = $dia->toDateString();

                if (! ColaboradorVinculoPonto::contaPontoNaData($colab, $dataYmd)) {
                    continue;
                }

                if ($somenteDiasComJornadaPrevista && ! $regras->deveTrabalharNoDia($colab, $dataYmd)) {
                    continue;
                }

                if ($existentes->has($colab->id.'|'.$dataYmd)) {
                    continue;
                }

                $linhas[] = [
                    'colaborador_id' => $colab->id,
                    'data' => $dataYmd,
                    'status' => 'falta',
                    'origem' => 'grade',
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
                $existentes->put($colab->id.'|'.$dataYmd, true);
            }
        }

        if ($linhas === []) {
            return 0;
        }

        foreach (array_chunk($linhas, 250) as $chunk) {
            FrequenciaRegistro::query()->insert($chunk);
        }

        return count($linhas);
    }

    /**
     * Gera faltas apenas para o colaborador informado (escopo unitário da apuração).
     */
    public static function gerarFaltasColaboradorNoPeriodo(
        Colaborador $colaborador,
        string $dataInicio,
        string $dataFim,
        bool $somenteDiasComJornadaPrevista = false
    ): int {
        [$ini, $fim] = self::normalizarIntervalo(Carbon::parse($dataInicio), Carbon::parse($dataFim));
        $regras = app(EscalaPontoRegras::class);
        $criados = 0;

        foreach (CarbonPeriod::create($ini, $fim) as $dia) {
            $dataYmd = $dia->toDateString();

            if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $dataYmd)) {
                continue;
            }

            if ($somenteDiasComJornadaPrevista && ! $regras->deveTrabalharNoDia($colaborador, $dataYmd)) {
                continue;
            }

            $existe = FrequenciaRegistro::query()
                ->where('colaborador_id', $colaborador->id)
                ->whereDate('data', $dataYmd)
                ->exists();

            if ($existe) {
                continue;
            }

            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colaborador->id,
                'data' => $dataYmd,
                'status' => 'falta',
                'origem' => 'grade',
            ]);
            $criados++;
        }

        return $criados;
    }

    /**
     * @param  list<string>|null  $identificadoresContrato
     * @return Collection<int, Colaborador>
     */
    public static function colaboradoresEscopo(?array $identificadoresContrato): Collection
    {
        $q = Colaborador::query()->whereIn('status', ['ativo', 'afastado']);

        if ($identificadoresContrato !== null && $identificadoresContrato !== []) {
            ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresContrato);
        }

        return $q->get(['id', 'data_admissao', 'data_demissao', 'horario_escala_id', 'status']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function normalizarIntervalo(Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $ini = $periodoInicio->copy()->startOfDay();
        $fim = $periodoFim->copy()->startOfDay();
        $hoje = today()->startOfDay();
        if ($fim->gt($hoje)) {
            $fim = $hoje->copy();
        }
        if ($fim->lt($ini)) {
            $fim = $ini->copy();
        }

        return [$ini, $fim];
    }
}
