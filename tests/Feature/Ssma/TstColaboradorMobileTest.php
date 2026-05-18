<?php

namespace Tests\Feature\Ssma;

use App\Models\Colaborador;
use App\Models\SsmaTstAtividade;
use App\Models\SsmaTstRegistro;
use App\Support\SsmaTstRegistroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TstColaboradorMobileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_identificacao_com_matricula_e_cpf(): void
    {
        Colaborador::query()->create([
            'nome' => 'Carlos Campo',
            'matricula' => 'TST-50',
            'cpf' => '123.456.789-09',
            'status' => 'ativo',
        ]);

        $this->post(route('tst-campo.identificar.store'), [
            'matricula' => '50',
            'cpf' => '12345678909',
        ])
            ->assertRedirect(route('tst-campo.index'));

        $this->assertNotNull(session('tst_colaborador_id'));
    }

    public function test_colaborador_registra_evento_com_foto(): void
    {
        $colaborador = Colaborador::query()->create([
            'nome' => 'Ana SSMA',
            'matricula' => '100',
            'cpf' => '98765432100',
            'status' => 'ativo',
        ]);

        $atividade = SsmaTstAtividade::query()->create([
            'nome' => 'Inspeção',
            'ativo' => true,
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->post(route('tst-campo.store'), [
                'ssma_tst_atividade_id' => $atividade->id,
                'data' => '2026-05-18',
                'descricao' => 'Inspeção de EPI na área 3.',
                'arquivo' => UploadedFile::fake()->image('campo.jpg'),
            ])
            ->assertRedirect(route('tst-campo.index'))
            ->assertSessionHas('success');

        $registro = SsmaTstRegistro::query()->first();
        $this->assertNotNull($registro);
        $this->assertSame($colaborador->id, $registro->colaborador_id);
        $this->assertSame(SsmaTstRegistroService::ORIGEM_APP_COLABORADOR, $registro->origem);
        Storage::disk('public')->assertExists($registro->arquivo_path);
    }

    public function test_app_exige_sessao(): void
    {
        $this->get(route('tst-campo.index'))
            ->assertRedirect(route('tst-campo.identificar'));
    }
}
