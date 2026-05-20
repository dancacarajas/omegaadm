<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Support\Rh\GarantirFrequenciaRegistrosPeriodo;
use App\Support\Rh\RegularizacaoPontoPeriodo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarantirFrequenciaRegistrosPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_falta_em_dia_de_jornada_prevista_sem_registro(): void
    {
        $escala = HorarioEscala::query()->create(['nome' => 'Comercial', 'tipo' => 'fixa']);
        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 3,
            'entrada_1' => '08:00',
            'saida_1' => '17:00',
            'entrada_2' => null,
            'saida_2' => null,
        ]);

        Colaborador::query()->create([
            'nome' => 'Sem grade',
            'status' => 'ativo',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
            'data_admissao' => '2026-04-01',
        ]);

        $criados = GarantirFrequenciaRegistrosPeriodo::gerarFaltasEmDiasSemRegistro(
            Carbon::parse('2026-04-08'),
            Carbon::parse('2026-04-08'),
            ['286'],
            true
        );

        $this->assertSame(1, $criados);
        $this->assertDatabaseHas('frequencia_registros', [
            'data' => '2026-04-08',
            'status' => 'falta',
            'origem' => 'grade',
        ]);
    }

    public function test_regularizacao_gera_faltas_e_conta_como_tratado(): void
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

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-10',
            'status' => 'incompleto',
            'entrada_1' => '08:00',
            'origem' => 'manual',
        ]);

        $r = RegularizacaoPontoPeriodo::calcular(
            Carbon::parse('2026-04-07'),
            Carbon::parse('2026-04-10'),
            ['286']
        );

        $this->assertGreaterThan(0, $r['faltas_geradas']);
        $this->assertSame(0, $r['sem_registro']);
        $this->assertSame(1, $r['incompletos']);
        $this->assertGreaterThanOrEqual(4, $r['dias_exigem_tratamento']);
        $this->assertSame($r['dias_exigem_tratamento'] - 1, $r['dias_tratados']);
    }
}
