<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\FrequenciaFeriado;
use App\Models\FrequenciaRegistro;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FeriadoPontoService
{
    public const ORIGEM = 'feriado';

    /** @var Collection<int, FrequenciaFeriado>|null */
    private static ?Collection $cacheAtivos = null;

    public function feriadoNaData(CarbonInterface|string $data): ?FrequenciaFeriado
    {
        foreach ($this->feriadosAtivos() as $feriado) {
            if ($feriado->ocorreNaData($data)) {
                return $feriado;
            }
        }

        return null;
    }

    public function diaAbonadoPorFeriado(CarbonInterface|string $data): bool
    {
        return $this->feriadoNaData($data) !== null;
    }

    /**
     * Aplica feriado nos registros de ponto dos colaboradores ativos na data.
     */
    public function sincronizarData(string $dataYmd): void
    {
        $feriado = $this->feriadoNaData($dataYmd);
        if (! $feriado) {
            return;
        }

        $colaboradores = Colaborador::query()->where('status', 'ativo')->pluck('id');

        foreach ($colaboradores as $colaboradorId) {
            $this->aplicarFeriadoNoColaborador((int) $colaboradorId, $dataYmd, $feriado);
        }
    }

    public function aplicarFeriadoNoColaborador(int $colaboradorId, string $dataYmd, ?FrequenciaFeriado $feriado = null): void
    {
        $feriado ??= $this->feriadoNaData($dataYmd);
        if (! $feriado) {
            return;
        }

        $registro = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaboradorId)
            ->whereDate('data', $dataYmd)
            ->first();

        if ($registro && $this->registroTemBatidas($registro)) {
            return;
        }

        if ($registro && $registro->status === 'presente' && $this->registroTemBatidas($registro)) {
            return;
        }

        $payload = [
            'entrada_1' => null,
            'saida_1' => null,
            'entrada_2' => null,
            'saida_2' => null,
            'status' => 'justificado',
            'origem' => self::ORIGEM,
            'justificativa_tipo' => 'abono',
            'justificativa_texto' => $feriado->rotuloPonto(),
        ];

        if ($registro) {
            $registro->update($payload);

            return;
        }

        FrequenciaRegistro::query()->create(array_merge($payload, [
            'colaborador_id' => $colaboradorId,
            'data' => $dataYmd,
        ]));
    }

    public function deveAplicarFeriadoNoRegistro(?FrequenciaRegistro $registro): bool
    {
        if (! $registro) {
            return true;
        }

        if ($this->registroTemBatidas($registro)) {
            return false;
        }

        return ! in_array($registro->status, ['presente'], true);
    }

    /**
     * @return Collection<int, FrequenciaFeriado>
     */
    private function feriadosAtivos(): Collection
    {
        if (self::$cacheAtivos === null) {
            self::$cacheAtivos = FrequenciaFeriado::query()
                ->where('ativo', true)
                ->orderBy('data')
                ->get();
        }

        return self::$cacheAtivos;
    }

    public static function limparCache(): void
    {
        self::$cacheAtivos = null;
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
