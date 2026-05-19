<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\FrequenciaPontoCsvImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FrequenciaPontoCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private function colaboradorTeste(): Colaborador
    {
        return Colaborador::query()->create([
            'nome' => 'Teste CSV',
            'matricula' => '99901',
            'cpf' => '12345678901',
            'status' => 'ativo',
        ]);
    }

    public function test_importa_batidas_horarios_exatos(): void
    {
        $this->colaboradorTeste();

        $resultado = app(FrequenciaPontoCsvImport::class)->importar(
            base_path('tests/Fixtures/frequencia-ponto-export.csv'),
            '2026-03-01',
            '2026-04-30'
        );

        $this->assertSame(3, $resultado['importados']);

        $registro = FrequenciaRegistro::query()
            ->whereHas('colaborador', fn ($q) => $q->where('matricula', '99901'))
            ->whereDate('data', '2026-04-06')
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('07:30:00', $registro->entrada_1);
        $this->assertSame('12:00:00', $registro->saida_1);
        $this->assertSame('13:00:00', $registro->entrada_2);
        $this->assertSame('17:31:00', $registro->saida_2);
        $this->assertSame('presente', $registro->status);
        $this->assertSame(FrequenciaPontoCsvImport::ORIGEM, $registro->origem);
    }

    public function test_importa_folga_e_justificado(): void
    {
        $this->colaboradorTeste();
        app(FrequenciaPontoCsvImport::class)->importar(
            base_path('tests/Fixtures/frequencia-ponto-export.csv'),
            '2026-03-01',
            '2026-04-30'
        );

        $folga = FrequenciaRegistro::query()
            ->whereHas('colaborador', fn ($q) => $q->where('matricula', '99901'))
            ->whereDate('data', '2026-04-04')
            ->first();

        $this->assertSame('folga', $folga->status);
        $this->assertNull($folga->entrada_1);

        $justificado = FrequenciaRegistro::query()
            ->whereHas('colaborador', fn ($q) => $q->where('matricula', '99901'))
            ->whereDate('data', '2026-03-24')
            ->first();

        $this->assertSame('justificado', $justificado->status);
        $this->assertSame('Mobilização SGC', $justificado->justificativa_texto);
        $this->assertSame('abono', $justificado->justificativa_tipo);
    }

    public function test_rota_http_importar_csv(): void
    {
        $user = User::factory()->create();
        $this->colaboradorTeste();

        $arquivo = new UploadedFile(
            base_path('tests/Fixtures/frequencia-ponto-export.csv'),
            'exportacao-ponto.csv',
            'text/csv',
            null,
            true
        );

        $this->actingAs($user)
            ->post(route('rh.frequencia.importar-csv'), [
                'arquivo' => $arquivo,
                'data_inicio' => '2026-03-01',
                'data_fim' => '2026-04-30',
                'escopo_colaboradores' => 'todos',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(3, FrequenciaRegistro::query()->count());
    }

    public function test_importa_somente_linhas_dentro_do_periodo(): void
    {
        $this->colaboradorTeste();

        $resultado = app(FrequenciaPontoCsvImport::class)->importar(
            base_path('tests/Fixtures/frequencia-ponto-export.csv'),
            '2026-04-01',
            '2026-04-30'
        );

        $this->assertSame(2, $resultado['importados']);
        $this->assertSame(1, $resultado['fora_periodo']);

        $this->assertNull(
            FrequenciaRegistro::query()
                ->whereHas('colaborador', fn ($q) => $q->where('matricula', '99901'))
                ->whereDate('data', '2026-03-24')
                ->first()
        );

        $this->assertNotNull(
            FrequenciaRegistro::query()
                ->whereHas('colaborador', fn ($q) => $q->where('matricula', '99901'))
                ->whereDate('data', '2026-04-06')
                ->first()
        );
    }
}
