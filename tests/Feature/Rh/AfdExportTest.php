<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\AfdExport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfdExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporta_arquivo_afd_com_marcacoes(): void
    {
        $user = User::factory()->create();
        $data = '2026-05-15';
        $colaborador = Colaborador::query()->create([
            'nome' => 'Ana Export',
            'matricula' => '9001',
            'pis' => '12345678901',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $data,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'status' => 'presente',
            'origem' => 'manual',
        ]);

        $response = $this->actingAs($user)
            ->get(route('rh.frequencia.exportar-afd', [
                'data_inicio' => $data,
                'data_fim' => $data,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $conteudo = $response->getContent();
        $this->assertStringContainsString('202605150800', $conteudo);
        $this->assertStringContainsString('202605151200', $conteudo);
        $this->assertMatchesRegularExpression('/\d{9}3\d{8}\d{4}\d{12}/', $conteudo);
    }

    public function test_exporta_sem_marcacoes_retorna_erro(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('rh.frequencia.index'))
            ->get(route('rh.frequencia.exportar-afd', [
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-01-31',
            ]))
            ->assertRedirect(route('rh.frequencia.index'))
            ->assertSessionHas('error');
    }

    public function test_exporta_colaborador_sem_pis_usando_matricula(): void
    {
        $data = today()->toDateString();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Sem PIS',
            'matricula' => '7788',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $data,
            'entrada_1' => '09:00:00',
            'status' => 'incompleto',
            'origem' => 'manual',
        ]);

        $resultado = app(AfdExport::class)->gerar($data, $data);

        $this->assertSame(1, $resultado['total_marcacoes']);
        $this->assertStringContainsString('000000007788', $resultado['conteudo']);
    }

    public function test_export_usa_pis_com_12_digitos_no_registro_tipo_3(): void
    {
        $data = today()->toDateString();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Carlos AFD',
            'matricula' => '5555',
            'pis' => '98765432100',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $data,
            'entrada_1' => '07:15:00',
            'saida_1' => '11:30:00',
            'status' => 'presente',
            'origem' => 'manual',
        ]);

        $resultado = app(AfdExport::class)->gerar($data, $data);
        $dataAfd = Carbon::parse($data)->format('Ymd');

        $this->assertSame(2, $resultado['total_marcacoes']);
        $this->assertStringContainsString($dataAfd.'0715098765432100', $resultado['conteudo']);
        $this->assertStringContainsString($dataAfd.'1130098765432100', $resultado['conteudo']);
    }
}
