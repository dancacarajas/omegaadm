<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\HorarioEscalaDia;
use App\Models\HorarioEscalaExcecao;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class EscalaPontoRegras
{
    /**
     * @return array{permitido: bool, motivo: string|null, codigo: string|null}
     */
    public function avaliarMarcacao(Colaborador $colaborador, CarbonInterface|string $data, bool $estaInserindoHorarios): array
    {
        if (! $estaInserindoHorarios) {
            return ['permitido' => true, 'motivo' => null, 'codigo' => null];
        }

        $carbon = $data instanceof CarbonInterface
            ? Carbon::parse($data)->startOfDay()
            : Carbon::parse((string) $data)->startOfDay();

        $excecao = $this->excecaoVigente($colaborador, $carbon);
        if ($excecao !== null) {
            if ($excecao->colaborador_ausente_id === $colaborador->id) {
                return [
                    'permitido' => false,
                    'motivo' => 'Colaborador em período de ausência registrado na escala (exceção administrativa). Use justificativa se aplicável.',
                    'codigo' => 'excecao_ausencia',
                ];
            }
            if ($excecao->colaborador_cobertura_id === $colaborador->id) {
                return ['permitido' => true, 'motivo' => null, 'codigo' => 'excecao_cobertura'];
            }
        }

        if (app(\App\Support\FeriadoPontoService::class)->diaAbonadoPorFeriado($carbon)) {
            $feriado = app(\App\Support\FeriadoPontoService::class)->feriadoNaData($carbon);

            return [
                'permitido' => false,
                'motivo' => 'Não é permitido registrar ponto nesta data: feriado cadastrado ('.($feriado?->nome ?? 'Feriado').').',
                'codigo' => 'feriado',
            ];
        }

        if (! $colaborador->horario_escala_id) {
            return ['permitido' => true, 'motivo' => null, 'codigo' => null];
        }

        $dia = $colaborador->horarioEscalaDiaNaData($carbon);
        if (! $this->diaTemJornadaPrevista($dia)) {
            $escala = $colaborador->horarioEscala;
            $rotulo = match (true) {
                $escala?->isRotativaSemanal() => 'dia de folga na escala rotativa semanal',
                $escala?->isRotativa() => 'dia de folga no ciclo rotativo',
                default => 'dia sem jornada na escala',
            };

            return [
                'permitido' => false,
                'motivo' => 'Não é permitido registrar ponto nesta data: '.$rotulo.'.',
                'codigo' => 'folga_escala',
            ];
        }

        return ['permitido' => true, 'motivo' => null, 'codigo' => null];
    }

    public function deveTrabalharNoDia(Colaborador $colaborador, CarbonInterface|string $data): bool
    {
        return $this->avaliarMarcacao($colaborador, $data, true)['permitido'];
    }

    /** Dia de folga na escala (rotativa / sem jornada prevista) — não gera horas falta. */
    public function diaAbonadoPorFolgaEscala(Colaborador $colaborador, CarbonInterface|string $data): bool
    {
        $avaliacao = $this->avaliarMarcacao($colaborador, $data, true);

        return ! $avaliacao['permitido'] && $avaliacao['codigo'] === 'folga_escala';
    }

    private function excecaoVigente(Colaborador $colaborador, CarbonInterface $data): ?HorarioEscalaExcecao
    {
        if (! $colaborador->horario_escala_id) {
            return null;
        }

        $colaborador->loadMissing('horarioEscala.excecoes');

        foreach ($colaborador->horarioEscala?->excecoes ?? [] as $excecao) {
            if (! $excecao->cobreData($data)) {
                continue;
            }
            if ($excecao->colaborador_ausente_id === $colaborador->id
                || $excecao->colaborador_cobertura_id === $colaborador->id) {
                return $excecao;
            }
        }

        return null;
    }

    private function diaTemJornadaPrevista(?HorarioEscalaDia $dia): bool
    {
        if ($dia === null) {
            return false;
        }

        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($dia->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }
}
