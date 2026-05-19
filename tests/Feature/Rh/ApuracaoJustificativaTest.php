<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaJustificativaTipo;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApuracaoJustificativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplica_atestado_medico_em_periodo_na_apuracao(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'João',
            'matricula' => '100',
            'status' => 'ativo',
        ]);

        $tipo = FrequenciaJustificativaTipo::query()->create([
            'nome' => 'Atestado Médico',
            'categoria' => 'atestado',
            'limpa_batidas' => true,
            'ativo' => true,
            'ordem' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('rh.frequencia.apuracao.justificativa'), [
                'colaborador_id' => $colaborador->id,
                'data_inicio' => '2026-04-27',
                'data_fim' => '2026-04-28',
                'justificativa_tipo_id' => $tipo->id,
            ])
            ->assertRedirect();

        $registro = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('data', '2026-04-27')
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('justificado', $registro->status);
        $this->assertSame('atestado', $registro->justificativa_tipo);
        $this->assertSame($tipo->id, $registro->justificativa_tipo_id);
        $this->assertStringContainsString('Atestado Médico', (string) $registro->justificativa_texto);
    }

    public function test_salva_marcacao_inline_na_apuracao(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Maria',
            'matricula' => '101',
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-04-01',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $this->actingAs($user)
            ->post(route('rh.frequencia.apuracao.marcacao'), [
                'registro_id' => $registro->id,
                'entrada_1' => '08:00',
                'saida_1' => '12:00',
                'entrada_2' => '13:00',
                'saida_2' => '17:00',
            ])
            ->assertRedirect();

        $registro->refresh();
        $this->assertSame('presente', $registro->status);
        $this->assertSame('08:00:00', $registro->entrada_1);
    }
}
