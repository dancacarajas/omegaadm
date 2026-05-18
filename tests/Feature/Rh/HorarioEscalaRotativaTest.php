<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\User;
use App\Support\HorarioEscalaRotativa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioEscalaRotativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_indice_ciclo_alterna_a_cada_dia_do_calendario(): void
    {
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas revezamento',
            'tipo' => 'rotativa',
            'ciclo_dias' => 2,
            'data_inicio_ciclo' => '2026-05-01',
            'status' => 'ativo',
        ]);

        $this->assertSame(1, HorarioEscalaRotativa::indiceDiaCiclo($escala, Carbon::parse('2026-05-01'), 0));
        $this->assertSame(2, HorarioEscalaRotativa::indiceDiaCiclo($escala, Carbon::parse('2026-05-01'), 1));
        $this->assertSame(2, HorarioEscalaRotativa::indiceDiaCiclo($escala, Carbon::parse('2026-05-02'), 0));
        $this->assertSame(1, HorarioEscalaRotativa::indiceDiaCiclo($escala, Carbon::parse('2026-05-02'), 1));
    }

    public function test_segunda_de_uma_semana_e_segunda_da_outra_tem_fases_opostas(): void
    {
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'rotativa',
            'ciclo_dias' => 2,
            'data_inicio_ciclo' => '2026-05-05',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '06:00:00',
            'saida_1' => '18:00:00',
        ]);
        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 2,
        ]);

        $joao = Colaborador::query()->create([
            'nome' => 'João',
            'matricula' => 'MOT001',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 0,
            'status' => 'ativo',
        ]);
        $pedro = Colaborador::query()->create([
            'nome' => 'Pedro',
            'matricula' => 'MOT002',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 1,
            'status' => 'ativo',
        ]);

        $segundaSemana1 = Carbon::parse('2026-05-05');
        $segundaSemana2 = Carbon::parse('2026-05-12');

        $joaoDia1 = $joao->horarioEscalaDiaNaData($segundaSemana1);
        $pedroDia1 = $pedro->horarioEscalaDiaNaData($segundaSemana1);
        $this->assertNotNull($joaoDia1?->entrada_1);
        $this->assertNull($pedroDia1?->entrada_1);

        $joaoDia2 = $joao->horarioEscalaDiaNaData($segundaSemana2);
        $pedroDia2 = $pedro->horarioEscalaDiaNaData($segundaSemana2);
        $this->assertNull($joaoDia2?->entrada_1);
        $this->assertNotNull($pedroDia2?->entrada_1);
    }

    public function test_pode_cadastrar_escala_rotativa_via_http(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('rh.horarios.store'), [
            'nome' => 'Revezamento motoristas',
            'tipo' => 'rotativa',
            'status' => 'ativo',
            'ciclo_dias' => 2,
            'data_inicio_ciclo' => '2026-05-01',
            'dias' => [
                1 => ['entrada_1' => '06:00', 'saida_1' => '18:00', 'almoco_livre' => '0', 'compensado' => '0', 'neutro' => '0', 'noturno' => '0'],
                2 => ['almoco_livre' => '0', 'compensado' => '0', 'neutro' => '0', 'noturno' => '0'],
            ],
        ]);

        $response->assertRedirect(route('rh.horarios.index'));
        $this->assertDatabaseHas('horario_escalas', [
            'nome' => 'Revezamento motoristas',
            'tipo' => 'rotativa',
            'ciclo_dias' => 2,
        ]);
    }
}
