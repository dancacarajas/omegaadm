<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApuracaoPontoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_apuracao_exibe_batidas_e_folga(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Maria Apuração',
            'matricula' => '88001',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-04-06',
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-04-04',
            'status' => 'folga',
            'origem' => 'csv_ponto',
        ]);

        $this->actingAs($user)
            ->get(route('rh.frequencia.apuracao.index', [
                'colaborador_id' => $colaborador->id,
                'data_inicio' => '2026-04-01',
                'data_fim' => '2026-04-10',
            ]))
            ->assertOk()
            ->assertSee('Apuração do Ponto')
            ->assertSee('Maria Apuração')
            ->assertSee('07:30 (C)')
            ->assertSee('Folga')
            ->assertSee('TOTAL');
    }
}
