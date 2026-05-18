<?php

namespace Tests\Feature\Ssma;

use App\Models\Colaborador;
use App\Models\SsmaTstRegistro;
use App\Models\User;
use App\Support\SsmaTstRegistroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SsmaTstRegistroDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_excluir_registro_com_fotos_remove_arquivos_e_registro(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Jarbás Teste',
            'matricula' => '022214',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->post(route('sesmt.registros-tst.registros.store'), [
                'colaborador_id' => $colaborador->id,
                'data' => '2026-05-18',
                'descricao' => 'Registro para excluir',
                'arquivos' => [
                    UploadedFile::fake()->image('foto-a.jpg'),
                    UploadedFile::fake()->image('foto-b.jpg'),
                ],
            ])
            ->assertRedirect();

        $registro = SsmaTstRegistro::query()->with('fotos')->first();
        $this->assertNotNull($registro);
        $paths = $registro->fotos->pluck('arquivo_path')->all();
        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->actingAs($user)
            ->delete(route('sesmt.registros-tst.registros.destroy', $registro))
            ->assertRedirect(route('sesmt.registros-tst.registros.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, SsmaTstRegistro::query()->count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_excluir_registro_legado_so_com_arquivo_path(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Legado',
            'matricula' => '1',
            'status' => 'ativo',
        ]);

        $path = UploadedFile::fake()->image('legado.jpg')->store('ssma/tst/registros', 'public');

        $registro = SsmaTstRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => '2026-05-18',
            'descricao' => 'Sem linhas na tabela de fotos',
            'arquivo_path' => $path,
            'arquivo_nome' => 'legado.jpg',
            'arquivo_mime' => 'image/jpeg',
            'origem' => SsmaTstRegistroService::ORIGEM_SISTEMA,
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->delete(route('sesmt.registros-tst.registros.destroy', $registro))
            ->assertRedirect(route('sesmt.registros-tst.registros.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('ssma_tst_registros', ['id' => $registro->id]);
    }
}
