<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoPontoPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_pdf_cartao_ponto_para_colaborador(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        Contrato::query()->create([
            'numero' => '286',
            'nome' => 'Contrato Teste',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'Alexandre Teste',
            'matricula' => '22270',
            'cpf' => '70032129270',
            'cargo' => 'Eletricista III',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-04-22',
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
            'status' => 'presente',
            'origem' => 'manual',
        ]);

        $response = $this->actingAs($user)->get(route('rh.frequencia.cartao-ponto.pdf', [
            'contrato' => '286',
            'data_inicio' => '2026-04-21',
            'data_fim' => '2026-04-22',
            'escopo' => 'colaborador',
            'colaborador_id' => $colaborador->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
