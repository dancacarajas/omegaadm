<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_excluir_outro_usuario(): void
    {
        $admin = User::factory()->create(['status' => 'ativo']);
        $alvo = User::factory()->create([
            'email' => 'remover@example.test',
            'status' => 'ativo',
        ]);

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $alvo))
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $alvo->id]);
    }

    public function test_nao_pode_excluir_proprio_usuario(): void
    {
        $admin = User::factory()->create(['status' => 'ativo']);

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
