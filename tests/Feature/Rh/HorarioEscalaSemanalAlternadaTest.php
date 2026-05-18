<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\User;
use App\Support\HorarioEscalaSemanalAlternada;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioEscalaSemanalAlternadaTest extends TestCase
{
    use RefreshDatabase;

    private function escalaMotoristas(): HorarioEscala
    {
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
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        return $escala;
    }

    public function test_semana_1_joao_seg_qua_sex_pedro_ter_qui(): void
    {
        $escala = $this->escalaMotoristas();
        $segundaSem1 = Carbon::parse('2026-05-04');

        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1, 0));
        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1, 1));

        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDay(), 0));
        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDay(), 1));

        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDays(2), 0));
        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDays(4), 0));

        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDays(5), 0));
        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem1->copy()->addDays(6), 0));
    }

    public function test_semana_2_inverte_joao_ter_qui_pedro_seg_qua_sex(): void
    {
        $escala = $this->escalaMotoristas();
        $segundaSem2 = Carbon::parse('2026-05-11');

        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2, 0));
        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2, 1));

        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2->copy()->addDay(), 0));
        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2->copy()->addDay(), 1));

        $this->assertFalse(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2->copy()->addDays(2), 0));
        $this->assertTrue(HorarioEscalaSemanalAlternada::trabalhaNoDia($escala, $segundaSem2->copy()->addDays(2), 1));
    }

    public function test_colaborador_dia_na_data_integra_com_modelo(): void
    {
        $escala = $this->escalaMotoristas();

        $joao = Colaborador::query()->create([
            'nome' => 'João',
            'matricula' => 'J1',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 0,
            'status' => 'ativo',
        ]);
        $pedro = Colaborador::query()->create([
            'nome' => 'Pedro',
            'matricula' => 'P1',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 1,
            'status' => 'ativo',
        ]);

        $segunda = Carbon::parse('2026-05-04');
        $terca = Carbon::parse('2026-05-05');

        $this->assertNotNull($joao->horarioEscalaDiaNaData($segunda)?->entrada_1);
        $this->assertNull($joao->horarioEscalaDiaNaData($terca)?->entrada_1);

        $this->assertNull($pedro->horarioEscalaDiaNaData($segunda)?->entrada_1);
        $this->assertNotNull($pedro->horarioEscalaDiaNaData($terca)?->entrada_1);
    }

    public function test_pode_cadastrar_escala_rotativa_semanal_via_http(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('rh.horarios.store'), [
            'nome' => 'Motoristas revezamento',
            'tipo' => 'rotativa_semanal',
            'status' => 'ativo',
            'data_inicio_ciclo' => '2026-05-07',
            'dias' => [
                1 => [
                    'entrada_1' => '07:30',
                    'saida_1' => '12:00',
                    'entrada_2' => '13:00',
                    'saida_2' => '17:30',
                    'almoco_livre' => '0',
                    'compensado' => '0',
                    'neutro' => '0',
                    'noturno' => '0',
                ],
            ],
        ]);

        $response->assertRedirect(route('rh.horarios.index'));
        $escala = HorarioEscala::query()->where('nome', 'Motoristas revezamento')->first();
        $this->assertNotNull($escala);
        $this->assertSame('rotativa_semanal', $escala->tipo);
        $this->assertSame('2026-05-04', $escala->data_inicio_ciclo->toDateString());
    }
}
