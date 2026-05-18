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

    public function test_colaborador_registra_evento_com_ate_quatro_fotos(): void
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
            'exibir_no_app' => true,
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->post(route('tst-campo.store'), [
                'ssma_tst_atividade_id' => $atividade->id,
                'data' => '2026-05-18',
                'descricao' => 'Inspeção de EPI na área 3.',
                'arquivos' => [
                    UploadedFile::fake()->image('campo-1.jpg'),
                    UploadedFile::fake()->image('campo-2.jpg'),
                ],
            ])
            ->assertRedirect(route('tst-campo.index'))
            ->assertSessionHas('success');

        $registro = SsmaTstRegistro::query()->with('fotos')->first();
        $this->assertNotNull($registro);
        $this->assertSame($colaborador->id, $registro->colaborador_id);
        $this->assertSame(SsmaTstRegistroService::ORIGEM_APP_COLABORADOR, $registro->origem);
        $this->assertCount(2, $registro->fotos);
        Storage::disk('public')->assertExists($registro->arquivo_path);
        foreach ($registro->fotos as $foto) {
            Storage::disk('public')->assertExists($foto->arquivo_path);
        }
    }

    public function test_rejeita_mais_de_quatro_fotos(): void
    {
        $colaborador = Colaborador::query()->create([
            'nome' => 'Ana SSMA',
            'matricula' => '100',
            'cpf' => '98765432100',
            'status' => 'ativo',
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->post(route('tst-campo.store'), [
                'data' => '2026-05-18',
                'descricao' => 'Teste limite de fotos.',
                'arquivos' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                    UploadedFile::fake()->image('d.jpg'),
                    UploadedFile::fake()->image('e.jpg'),
                ],
            ])
            ->assertSessionHasErrors('arquivos');

        $this->assertSame(0, SsmaTstRegistro::query()->count());
    }

    public function test_app_exige_sessao(): void
    {
        $this->get(route('tst-campo.index'))
            ->assertRedirect(route('tst-campo.identificar'));
    }
}
