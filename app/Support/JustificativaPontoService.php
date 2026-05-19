<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\FrequenciaJustificativaTipo;
use App\Models\FrequenciaRegistro;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class JustificativaPontoService
{
    /**
     * Aplica um tipo de justificativa do catálogo em cada dia do intervalo (inclusive).
     *
     * @return int Quantidade de dias atualizados
     */
    public function aplicarPeriodo(
        Colaborador $colaborador,
        string $dataInicio,
        string $dataFim,
        FrequenciaJustificativaTipo $tipo,
        ?string $observacao = null,
        ?string $anexoPath = null
    ): int {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->startOfDay();
        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $atualizados = 0;

        foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
            if ($this->aplicarDia($colaborador, $dia->toDateString(), $tipo, $observacao, $anexoPath)) {
                $atualizados++;
            }
        }

        return $atualizados;
    }

    public function aplicarDia(
        Colaborador $colaborador,
        string $dataYmd,
        FrequenciaJustificativaTipo $tipo,
        ?string $observacao = null,
        ?string $anexoPath = null
    ): bool {
        if (! $tipo->ativo) {
            return false;
        }

        $registro = FrequenciaRegistro::query()->firstOrCreate(
            [
                'colaborador_id' => $colaborador->id,
                'data' => $dataYmd,
            ],
            [
                'status' => 'falta',
                'origem' => 'manual',
            ]
        );

        if ($tipo->categoria === 'folga') {
            $registro->update([
                'entrada_1' => null,
                'saida_1' => null,
                'entrada_2' => null,
                'saida_2' => null,
                'status' => 'folga',
                'origem' => 'manual',
                'justificativa_tipo' => null,
                'justificativa_tipo_id' => null,
                'justificativa_texto' => $tipo->rotuloCompleto($observacao),
                'anexo_path' => $anexoPath ?? $registro->anexo_path,
            ]);

            return true;
        }

        if ($tipo->limpa_batidas && $this->registroTemBatidas($registro)) {
            // Mantém batidas se o RH não quiser limpar — tipos com limpa_batidas=false
        }

        $payload = [
            'status' => 'justificado',
            'origem' => 'manual',
            'justificativa_tipo' => $tipo->categoriaLegado(),
            'justificativa_tipo_id' => $tipo->id,
            'justificativa_texto' => $tipo->rotuloCompleto($observacao),
        ];

        if ($tipo->limpa_batidas) {
            $payload['entrada_1'] = null;
            $payload['saida_1'] = null;
            $payload['entrada_2'] = null;
            $payload['saida_2'] = null;
        }

        if ($anexoPath !== null) {
            $payload['anexo_path'] = $anexoPath;
        }

        $registro->update($payload);

        return true;
    }

    public function removerJustificativa(FrequenciaRegistro $registro): void
    {
        $registro->update([
            'status' => 'falta',
            'justificativa_tipo' => null,
            'justificativa_tipo_id' => null,
            'justificativa_texto' => null,
            'anexo_path' => null,
            'entrada_1' => null,
            'saida_1' => null,
            'entrada_2' => null,
            'saida_2' => null,
            'origem' => 'manual',
        ]);
    }

    private function registroTemBatidas(FrequenciaRegistro $registro): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }
}
