<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\User;
use App\Support\EscalaPontoRegras;
use App\Support\FrequenciaCalculo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrequenciaCalculoExtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_automatica_preenche_apenas_intervalo(): void
    {
        $user = User::factory()->create();
        $data = '2026-05-18';

        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '04:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        Colaborador::query()->create([
            'nome' => 'Efetivo grade',
            'matricula' => 'GRD-1',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->get(route('rh.frequencia.index', ['data' => $data]))
            ->assertOk();

        $registro = FrequenciaRegistro::query()->whereDate('data', $data)->first();
        $this->assertNotNull($registro);
        $this->assertNull($registro->entrada_1);
        $this->assertNull($registro->saida_2);
        $this->assertSame('12:00:00', $registro->saida_1);
        $this->assertSame('13:00:00', $registro->entrada_2);
    }

    public function test_minutos_extras_fora_da_escala_soma_entrada_antecipada_e_saida_tardia(): void
    {
        $escala = HorarioEscala::create([
            'nome' => 'Teste',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '04:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'Calc',
            'matricula' => 'CALC-1',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-05-18',
            'entrada_1' => '03:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '18:00:00',
            'status' => 'presente',
            'origem' => 'app_colaborador',
        ]);

        $registro->load('colaborador.horarioEscala.dias');

        $this->assertSame(60, FrequenciaCalculo::minutosExtrasForaDaEscala($registro));
        $this->assertGreaterThanOrEqual(60, FrequenciaCalculo::minutosExtras($registro));
    }

    public function test_sem_saida_final_nao_inventa_horas_da_escala_nem_extras(): void
    {
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'José',
            'matricula' => '22281',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-05-18',
            'entrada_1' => '04:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => null,
            'status' => 'incompleto',
            'origem' => 'manual',
        ]);

        $registro->load('colaborador.horarioEscala.dias');

        $resumo = FrequenciaCalculo::resumo($registro);
        $fallback = FrequenciaCalculo::resumoComFallbackEscala($registro);

        $this->assertSame(480, $resumo['trabalhadas']);
        $this->assertSame(0, $resumo['extras']);
        $this->assertGreaterThan(0, $resumo['falta'] ?? 0);
        $this->assertGreaterThan($resumo['trabalhadas'], $fallback['trabalhadas']);
    }

    public function test_dia_de_folga_rotativa_semanal_nao_gera_horas_falta(): void
    {
        $segunda = Carbon::parse('2026-05-18');
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'rotativa_semanal',
            'ciclo_dias' => 14,
            'data_inicio_ciclo' => '2026-05-04',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '04:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'Rafael',
            'matricula' => '22541',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 1,
            'status' => 'ativo',
        ]);

        $regras = app(EscalaPontoRegras::class);
        $this->assertFalse($regras->deveTrabalharNoDia($colaborador, $segunda));
        $this->assertTrue($regras->diaAbonadoPorFolgaEscala($colaborador, $segunda));

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $segunda->toDateString(),
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $registro->load('colaborador.horarioEscala.dias');
        $resumo = FrequenciaCalculo::resumo($registro);

        $this->assertSame(0, FrequenciaCalculo::jornadaMinutosParaRegistro($registro));
        $this->assertNull($resumo['falta']);
        $this->assertSame('Folga (abonada)', $resumo['falta_fmt']);
        $this->assertSame(0, $resumo['extras']);
    }
}
