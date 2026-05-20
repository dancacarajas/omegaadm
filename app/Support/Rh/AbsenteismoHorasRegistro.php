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

        $status = (string) ($registro->status ?? 'falta');
        $resumo = self::resumoParaCalculo($registro, $status);
        $previstas = (int) ($resumo['jornada_esperada_minutos'] ?? 0);
        if ($previstas <= 0) {
            return $zeros;
        }

        $trabalhadas = (int) ($resumo['trabalhadas'] ?? 0);

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
            $injustificada = self::minutosInjustificadosPresente($registro, $previstas, $trabalhadas, $status);

            return [
                'previstas_minutos' => $previstas,
                'ausencia_geral_minutos' => $injustificada,
                'ausencia_justificada_minutos' => 0,
                'ausencia_injustificada_minutos' => $injustificada,
            ];
        }

        return $zeros;
    }

    /**
     * Mesma base da apuração: CSV com só entrada/saída final usa intervalo da escala para horas trabalhadas.
     *
     * @return array<string, mixed>
     */
    private static function resumoParaCalculo(FrequenciaRegistro $registro, string $status): array
    {
        $resumo = FrequenciaCalculo::resumo($registro);

        if (! in_array($status, ['presente', 'incompleto'], true)) {
            return $resumo;
        }

        if (! self::deveCompletarBatidasComEscala($registro, (int) ($resumo['trabalhadas'] ?? 0))) {
            return $resumo;
        }

        return FrequenciaCalculo::resumoComFallbackEscala($registro);
    }

    private static function deveCompletarBatidasComEscala(FrequenciaRegistro $registro, int $trabalhadas): bool
    {
        if ($registro->colaborador?->horarioEscalaDiaNaData($registro->data) === null) {
            return false;
        }

        if ($trabalhadas === 0 && self::registroTemAlgumaBatida($registro)) {
            return true;
        }

        $diaEscala = $registro->colaborador->horarioEscalaDiaNaData($registro->data);
        if ($diaEscala === null) {
            return false;
        }

        $escalaTemIntervalo = ! FrequenciaCalculo::horarioArmazenadoVazio($diaEscala->saida_1)
            && ! FrequenciaCalculo::horarioArmazenadoVazio($diaEscala->entrada_2);

        if (! $escalaTemIntervalo) {
            return false;
        }

        $intervaloVazioNoRegistro = FrequenciaCalculo::horarioArmazenadoVazio($registro->saida_1)
            || FrequenciaCalculo::horarioArmazenadoVazio($registro->entrada_2);

        return $intervaloVazioNoRegistro
            && ! FrequenciaCalculo::horarioArmazenadoVazio($registro->entrada_1);
    }

    private static function registroTemAlgumaBatida(FrequenciaRegistro $registro): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Absenteísmo gerencial: só horas não trabalhadas (não soma atraso de entrada se o dia foi cumprido).
     */
    private static function minutosInjustificadosPresente(
        FrequenciaRegistro $registro,
        int $previstas,
        int $trabalhadas,
        string $status
    ): int {
        if ($status === 'incompleto' && $trabalhadas === 0) {
            return FrequenciaCalculo::faltaEfetivaMinutos($previstas);
        }

        $saldo = max(0, $previstas - $trabalhadas);
        if ($saldo <= 0) {
            return 0;
        }

        if ($status === 'presente') {
            $atraso = FrequenciaCalculo::minutosAtrasoRegistro($registro);
            if ($saldo <= $atraso + FrequenciaCalculo::toleranciaMinutosFalta()) {
                return 0;
            }
        }

        return FrequenciaCalculo::faltaEfetivaMinutos($saldo);
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
