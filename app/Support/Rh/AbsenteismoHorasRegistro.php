<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use App\Support\FrequenciaCalculo;
use App\Support\FeriadoPontoService;
use Carbon\CarbonInterface;

/**
 * Horas de ausência e jornada prevista por registro de frequência (metodologia RH gerencial).
 *
 * Inclui atestados e demais ausências justificadas no absenteísmo operacional.
 * Exclui folgas, feriados e dias sem jornada prevista na escala.
 */
final class AbsenteismoHorasRegistro
{
    /**
     * @return array{
     *     previstas_minutos: int,
     *     ausencia_geral_minutos: int,
     *     ausencia_justificada_minutos: int,
     *     ausencia_injustificada_minutos: int
     * }
     */
    public static function calcular(FrequenciaRegistro $registro): array
    {
        $zeros = self::zeros();

        $registro->loadMissing(['colaborador', 'justificativaTipoCatalogo']);
        $colaborador = $registro->colaborador;
        if ($colaborador === null || ! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $registro->data)) {
            return $zeros;
        }

        if ((string) ($registro->status ?? '') === 'folga') {
            return $zeros;
        }

        if (self::justificativaFolgaOuFeriadoSemImpacto($registro)) {
            return $zeros;
        }

        $resumo = FrequenciaCalculo::resumo($registro);
        $previstas = (int) ($resumo['jornada_esperada_minutos'] ?? 0);
        if ($previstas <= 0) {
            return $zeros;
        }

        $trabalhadas = (int) ($resumo['trabalhadas'] ?? 0);
        $status = (string) ($registro->status ?? 'falta');

        if ($status === 'justificado') {
            $ausencia = self::minutosAusenciaDia($previstas, $trabalhadas, true);

            return [
                'previstas_minutos' => $previstas,
                'ausencia_geral_minutos' => $ausencia,
                'ausencia_justificada_minutos' => $ausencia,
                'ausencia_injustificada_minutos' => 0,
            ];
        }

        if ($status === 'falta') {
            $ausencia = self::minutosAusenciaDia($previstas, $trabalhadas, true);

            return [
                'previstas_minutos' => $previstas,
                'ausencia_geral_minutos' => $ausencia,
                'ausencia_justificada_minutos' => 0,
                'ausencia_injustificada_minutos' => $ausencia,
            ];
        }

        if (in_array($status, ['presente', 'incompleto'], true)) {
            $falta = FrequenciaCalculo::faltaEfetivaMinutos(max(0, $previstas - $trabalhadas));
            $atraso = FrequenciaCalculo::faltaEfetivaMinutos(FrequenciaCalculo::minutosAtrasoRegistro($registro));
            $injustificada = $falta + $atraso;

            return [
                'previstas_minutos' => $previstas,
                'ausencia_geral_minutos' => $injustificada,
                'ausencia_justificada_minutos' => 0,
                'ausencia_injustificada_minutos' => $injustificada,
            ];
        }

        return $zeros;
    }

    private static function minutosAusenciaDia(int $previstas, int $trabalhadas, bool $diaInteiroSeSemBatida): int
    {
        $saldo = max(0, $previstas - $trabalhadas);
        $falta = FrequenciaCalculo::faltaEfetivaMinutos($saldo > 0 ? $saldo : null);

        if ($falta > 0) {
            return $falta;
        }

        if ($diaInteiroSeSemBatida && $trabalhadas === 0) {
            return $previstas;
        }

        return $saldo > 0 ? $saldo : 0;
    }

    private static function justificativaFolgaOuFeriadoSemImpacto(FrequenciaRegistro $registro): bool
    {
        if ($registro->justificativaTipoCatalogo?->categoria === 'folga') {
            return true;
        }

        $feriadoDia = app(FeriadoPontoService::class)->diaAbonadoPorFeriado($registro->data);
        $feriadoRegistro = ($registro->origem ?? '') === FeriadoPontoService::ORIGEM;

        return ($feriadoDia || $feriadoRegistro)
            && (string) ($registro->status ?? '') === 'justificado';
    }

    /**
     * @return array{
     *     previstas_minutos: int,
     *     ausencia_geral_minutos: int,
     *     ausencia_justificada_minutos: int,
     *     ausencia_injustificada_minutos: int
     * }
     */
    private static function zeros(): array
    {
        return [
            'previstas_minutos' => 0,
            'ausencia_geral_minutos' => 0,
            'ausencia_justificada_minutos' => 0,
            'ausencia_injustificada_minutos' => 0,
        ];
    }
}
