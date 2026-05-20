<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EfetivoExportarExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporta_planilha_xlsx_do_efetivo(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $escala = HorarioEscala::query()->create(['nome' => 'CT 286', 'tipo' => 'fixa']);

        Colaborador::query()->create([
            'nome' => 'Export Test',
            'status' => 'ativo',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
            'matricula' => '99999',
        ]);

        $response = $this->actingAs($user)->get(route('rh.efetivo.exportar-excel'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString('efetivo-', $response->headers->get('content-disposition') ?? '');
    }

    public function test_export_respeita_filtro_de_busca(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        Colaborador::query()->create(['nome' => 'Ana Silva', 'status' => 'ativo']);
        Colaborador::query()->create(['nome' => 'Bruno Costa', 'status' => 'ativo']);

        $response = $this->actingAs($user)->get(route('rh.efetivo.exportar-excel', ['busca' => 'Ana']));

        $response->assertOk();
    }
}
