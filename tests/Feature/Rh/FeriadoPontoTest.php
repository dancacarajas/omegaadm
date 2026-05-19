<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaFeriado;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\FeriadoPontoService;
use App\Support\FrequenciaCalculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeriadoPontoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_de_feriado_aplica_abono_no_ponto(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '9001',
            'status' => 'ativo',
        ]);

        $data = '2026-12-25';

        $this->actingAs($user)
            ->post(route('rh.frequencia.feriados.store'), [
                'nome' => 'Natal',
                'data' => $data,
                'ativo' => '1',
            ])
            ->assertRedirect(route('rh.frequencia.feriados.index', ['ano' => 2026]));

        $registro = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('data', $data)
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('justificado', $registro->status);
        $this->assertSame(FeriadoPontoService::ORIGEM, $registro->origem);
        $this->assertSame('abono', $registro->justificativa_tipo);
        $this->assertStringContainsString('Natal', (string) $registro->justificativa_texto);

        $resumo = FrequenciaCalculo::resumo($registro);
        $this->assertStringContainsString('Feriado', $resumo['falta_fmt'] ?? '');
    }

    public function test_feriado_recorrente_vale_em_qualquer_ano(): void
    {
        FrequenciaFeriado::query()->create([
            'nome' => 'Independência',
            'data' => '2020-09-07',
            'recorrente' => true,
            'ativo' => true,
        ]);

        FeriadoPontoService::limparCache();

        $feriado = app(FeriadoPontoService::class)->feriadoNaData('2028-09-07');
        $this->assertNotNull($feriado);
        $this->assertSame('Independência', $feriado->nome);
    }

    public function test_nao_sobrescreve_registro_com_batidas(): void
    {
        $colaborador = Colaborador::query()->create([
            'nome' => 'Carlos',
            'matricula' => '9002',
            'status' => 'ativo',
        ]);

        $data = '2026-01-01';

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $data,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
            'status' => 'presente',
            'origem' => 'manual',
        ]);

        FrequenciaFeriado::query()->create([
            'nome' => 'Ano Novo',
            'data' => $data,
            'recorrente' => false,
            'ativo' => true,
        ]);

        FeriadoPontoService::limparCache();
        app(FeriadoPontoService::class)->sincronizarData($data);

        $registro = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('data', $data)
            ->first();

        $this->assertSame('presente', $registro->status);
        $this->assertSame('manual', $registro->origem);
        $this->assertSame('08:00:00', $registro->entrada_1);
    }
}
