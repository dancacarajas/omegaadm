<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Support\Rh\RegularizacaoPontoPeriodo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegularizacaoPontoPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_conta_dia_tratado_com_falta_justificado_ou_presente(): void
    {
        $escala = HorarioEscala::query()->create(['nome' => 'Comercial', 'tipo' => 'fixa']);
        foreach (range(1, 5) as $diaSemana) {
            HorarioEscalaDia::query()->create([
                'horario_escala_id' => $escala->id,
                'dia_semana' => $diaSemana,
                'entrada_1' => '08:00',
                'saida_1' => '12:00',
                'entrada_2' => '13:00',
                'saida_2' => '17:00',
            ]);
        }

        $colab = Colaborador::query()->create([
            'nome' => 'Teste',
            'status' => 'ativo',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
            'data_admissao' => '2026-04-01',
        ]);

        foreach ([
            ['2026-04-07', 'presente', '08:00', '12:00', '13:00', '17:00'],
            ['2026-04-08', 'falta', null, null, null, null],
            ['2026-04-09', 'justificado', null, null, null, null],
            ['2026-04-10', 'incompleto', '08:00', null, null, null],
        ] as [$data, $status, $e1, $s1, $e2, $s2]) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colab->id,
                'data' => $data,
                'status' => $status,
                'entrada_1' => $e1,
                'saida_1' => $s1,
                'entrada_2' => $e2,
                'saida_2' => $s2,
                'origem' => 'manual',
            ]);
        }

        $r = RegularizacaoPontoPeriodo::calcular(
            Carbon::parse('2026-04-07'),
            Carbon::parse('2026-04-10'),
            ['286'],
            false
        );

        $this->assertGreaterThanOrEqual(4, $r['dias_exigem_tratamento']);
        $this->assertSame(3, $r['dias_tratados']);
        $this->assertSame(1, $r['incompletos']);
        $this->assertGreaterThan(0, $r['dias_pendentes']);
        $this->assertSame(0, $r['faltas_geradas']);
    }
}
