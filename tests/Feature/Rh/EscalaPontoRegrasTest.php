<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\HorarioEscalaExcecao;
use App\Models\User;
use App\Support\EscalaPontoRegras;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalaPontoRegrasTest extends TestCase
{
    use RefreshDatabase;

    private function criarEscalaRotativaMotoristas(): array
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
            'entrada_1' => '07:30:00',
            'saida_1' => '17:30:00',
        ]);
        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 2,
        ]);

        $joao = Colaborador::query()->create([
            'nome' => 'João',
            'matricula' => 'MOT-J',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 0,
            'status' => 'ativo',
        ]);
        $pedro = Colaborador::query()->create([
            'nome' => 'Pedro',
            'matricula' => 'MOT-P',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 1,
            'status' => 'ativo',
        ]);

        return compact('escala', 'joao', 'pedro');
    }

    public function test_bloqueia_ponto_no_dia_de_folga_rotativa(): void
    {
        ['joao' => $joao, 'pedro' => $pedro] = $this->criarEscalaRotativaMotoristas();
        $regras = app(EscalaPontoRegras::class);

        $segunda = Carbon::parse('2026-05-05');

        $this->assertTrue($regras->deveTrabalharNoDia($joao, $segunda));
        $this->assertFalse($regras->deveTrabalharNoDia($pedro, $segunda));

        $terca = Carbon::parse('2026-05-06');
        $this->assertFalse($regras->deveTrabalharNoDia($joao, $terca));
        $this->assertTrue($regras->deveTrabalharNoDia($pedro, $terca));
    }

    public function test_excecao_ausencia_bloqueia_e_cobertura_libera_todos_os_dias(): void
    {
        ['escala' => $escala, 'joao' => $joao, 'pedro' => $pedro] = $this->criarEscalaRotativaMotoristas();

        HorarioEscalaExcecao::create([
            'horario_escala_id' => $escala->id,
            'colaborador_ausente_id' => $pedro->id,
            'colaborador_cobertura_id' => $joao->id,
            'data_inicio' => '2026-05-05',
            'data_fim' => '2026-05-20',
            'motivo' => 'Luto familiar',
        ]);

        $regras = app(EscalaPontoRegras::class);
        $terca = Carbon::parse('2026-05-06');

        $this->assertFalse($regras->deveTrabalharNoDia($pedro, $terca));
        $this->assertTrue($regras->deveTrabalharNoDia($joao, $terca));
    }

    public function test_marcacao_manual_rejeita_dia_de_folga(): void
    {
        ['pedro' => $pedro] = $this->criarEscalaRotativaMotoristas();

        $user = User::factory()->create();
        $this->actingAs($user);

        $registro = FrequenciaRegistro::create([
            'colaborador_id' => $pedro->id,
            'data' => '2026-05-05',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $response = $this->post(route('rh.frequencia.marcacao', $registro), [
            'entrada_1' => '07:30',
            'saida_1' => '17:30',
        ]);

        $response->assertSessionHasErrors('marcacao');
        $registro->refresh();
        $this->assertNull($registro->entrada_1);
    }

    public function test_marcacao_manual_permite_dia_de_trabalho(): void
    {
        ['pedro' => $pedro] = $this->criarEscalaRotativaMotoristas();

        $user = User::factory()->create();
        $this->actingAs($user);

        $registro = FrequenciaRegistro::create([
            'colaborador_id' => $pedro->id,
            'data' => '2026-05-06',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $response = $this->post(route('rh.frequencia.marcacao', $registro), [
            'entrada_1' => '07:30',
            'saida_1' => '17:30',
        ]);

        $response->assertSessionHasNoErrors();
        $registro->refresh();
        $this->assertNotNull($registro->entrada_1);
    }
}
