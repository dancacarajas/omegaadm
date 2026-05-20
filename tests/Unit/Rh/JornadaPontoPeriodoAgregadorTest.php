<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Support\Rh\JornadaPontoPeriodoAgregador;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JornadaPontoPeriodoAgregadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_horas_extras_da_apuracao(): void
    {
        $escala = HorarioEscala::query()->create(['nome' => 'Comercial', 'tipo' => 'fixa']);
        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00',
            'saida_1' => '12:00',
            'entrada_2' => '13:00',
            'saida_2' => '17:00',
        ]);

        $colab = Colaborador::query()->create([
            'nome' => 'Extra',
            'status' => 'ativo',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'status' => 'presente',
            'entrada_1' => '07:00',
            'saida_1' => '12:00',
            'entrada_2' => '13:00',
            'saida_2' => '18:00',
            'origem' => 'manual',
        ]);

        $ag = JornadaPontoPeriodoAgregador::agregar(
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30'),
            ['286']
        );

        $this->assertGreaterThan(0, $ag['previstas_minutos']);
        $this->assertGreaterThan(0, $ag['trabalhadas_minutos']);
        $this->assertGreaterThanOrEqual(0, $ag['extras_minutos']);
    }
}
