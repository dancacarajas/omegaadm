<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioColaboradorEfetivoTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_create_lista_colaboradores_ativos_e_dados_para_preencher_nome(): void
    {
        $user = User::factory()->create(['status' => 'ativo']);

        $colab = Colaborador::query()->create([
            'nome' => 'Maria Efetivo Silva',
            'matricula' => 'EF001',
            'telefone' => '(11) 99999-0000',
            'cargo' => 'Técnica SSMA',
            'status' => 'ativo',
        ]);

        Colaborador::query()->create([
            'nome' => 'Inativo RH',
            'matricula' => 'EF002',
            'status' => 'inativo',
        ]);

        $response = $this->actingAs($user)->get(route('usuarios.create'));

        $response->assertOk();
        $response->assertSee('Colaborador do efetivo', false);
        $response->assertSee('Maria Efetivo Silva (EF001)', false);
        $response->assertDontSee('Inativo RH', false);
        $response->assertSee('"nome":"Maria Efetivo Silva"', false);
    }

    public function test_store_vincula_colaborador_do_efetivo(): void
    {
        $admin = User::factory()->create(['status' => 'ativo']);

        $colab = Colaborador::query()->create([
            'nome' => 'João do Efetivo',
            'matricula' => 'EF100',
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'colaborador_id' => $colab->id,
            'name' => 'João do Efetivo',
            'email' => 'joao.efetivo@example.test',
            'status' => 'ativo',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'todos_contratos' => '1',
        ]);

        $novo = User::query()->where('email', 'joao.efetivo@example.test')->first();

        $response->assertRedirect(route('usuarios.show', $novo));
        $this->assertNotNull($novo);
        $this->assertSame($colab->id, $novo->colaborador_id);
        $this->assertSame('João do Efetivo', $novo->name);
    }
}
