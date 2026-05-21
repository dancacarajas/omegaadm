<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ColaboradorFotoPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_anexa_foto_pela_ficha(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Jarbás Silva',
            'matricula' => '022214',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->post(route('rh.efetivo.foto.update', $colab), [
                'foto_perfil' => UploadedFile::fake()->image('perfil.jpg', 200, 200),
            ])
            ->assertRedirect(route('rh.efetivo.show', $colab))
            ->assertSessionHas('success');

        $colab->refresh();
        $this->assertNotNull($colab->foto_path);
        Storage::disk('public')->assertExists($colab->foto_path);
        $this->assertStringContainsString('storage/', (string) $colab->urlFotoPerfil());
    }

    public function test_rejeita_arquivo_invalido(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Teste',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->post(route('rh.efetivo.foto.update', $colab), [
                'foto_perfil' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('foto_perfil');
    }
}
