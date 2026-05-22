<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UsuarioFotoPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_anexa_foto_no_cadastro(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Maria Silva',
                'email' => 'maria@example.com',
                'status' => 'ativo',
                'password' => 'senha123',
                'password_confirmation' => 'senha123',
                'todos_contratos' => '1',
                'foto_perfil' => UploadedFile::fake()->image('perfil.jpg', 200, 200),
            ]);

        $usuario = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $response->assertRedirect(route('usuarios.show', $usuario))
            ->assertSessionHasNoErrors();
        $this->assertNotNull($usuario->foto_path);
        Storage::disk('public')->assertExists($usuario->foto_path);
        $this->assertSame(route('usuarios.foto.show', $usuario), $usuario->urlFotoPerfil());

        $this->actingAs($admin)
            ->get(route('usuarios.foto.show', $usuario))
            ->assertOk();
    }

    public function test_usa_foto_do_colaborador_vinculado(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $fotoPath = 'rh/colaboradores/fotos/colab.jpg';
        Storage::disk('public')->put($fotoPath, 'conteudo-fake');

        $colab = Colaborador::query()->create([
            'nome' => 'João Efetivo',
            'status' => 'ativo',
            'foto_path' => $fotoPath,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'João Efetivo',
                'email' => 'joao@example.com',
                'status' => 'ativo',
                'password' => 'senha123',
                'password_confirmation' => 'senha123',
                'todos_contratos' => '1',
                'colaborador_id' => $colab->id,
            ]);

        $usuario = User::query()->where('email', 'joao@example.com')->firstOrFail();
        $response->assertRedirect(route('usuarios.show', $usuario));

        $this->assertNull($usuario->foto_path);
        $usuario->load('colaborador');
        $this->assertTrue($usuario->usaFotoDoColaborador());
        $this->assertSame(route('usuarios.foto.show', $usuario), $usuario->urlFotoPerfil());

        $this->actingAs($admin)
            ->get(route('usuarios.foto.show', $usuario))
            ->assertOk();
    }

    public function test_rejeita_arquivo_invalido(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Teste',
                'email' => 'teste@example.com',
                'status' => 'ativo',
                'password' => 'senha123',
                'password_confirmation' => 'senha123',
                'todos_contratos' => '1',
                'foto_perfil' => UploadedFile::fake()->create('doc.pdf', 100),
            ])
            ->assertSessionHasErrors('foto_perfil');
    }
}
