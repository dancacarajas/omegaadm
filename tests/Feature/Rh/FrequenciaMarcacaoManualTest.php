<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrequenciaMarcacaoManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_limpa_todas_as_batidas_do_dia(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'José',
            'matricula' => '22281',
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => today()->toDateString(),
            'entrada_1' => '15:35:00',
            'status' => 'incompleto',
            'origem' => 'app_colaborador',
        ]);

        $this->actingAs($user)
            ->post(route('rh.frequencia.limpar-marcacoes', $registro))
            ->assertRedirect();

        $registro->refresh();
        $this->assertNull($registro->entrada_1);
        $this->assertSame('falta', $registro->status);
        $this->assertSame('manual', $registro->origem);
    }

    public function test_salvar_marcacao_manual_nao_repreenche_apos_recarregar_index(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Maria',
            'matricula' => '100',
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => today()->toDateString(),
            'entrada_1' => '15:35:00',
            'status' => 'incompleto',
            'origem' => 'app_colaborador',
        ]);

        $this->actingAs($user)
            ->post(route('rh.frequencia.marcacao', $registro), [
                'entrada_1' => '07:30',
                'saida_1' => '',
                'entrada_2' => '',
                'saida_2' => '',
                '_registro_id' => $registro->id,
            ])
            ->assertRedirect();

        $registro->refresh();
        $this->assertSame('07:30:00', $registro->entrada_1);

        $this->actingAs($user)
            ->get(route('rh.frequencia.index', ['data' => today()->toDateString()]))
            ->assertOk();

        $registro->refresh();
        $this->assertSame('07:30:00', $registro->entrada_1);
    }
}
