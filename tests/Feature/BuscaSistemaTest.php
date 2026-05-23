<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscaSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_encontra_colaborador_por_nome(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create([
            'nome' => 'Samuel Coutinho Especial',
            'status' => 'desligado',
            'matricula' => '999',
        ]);

        $this->actingAs($user)
            ->get(route('busca.index', ['q' => 'Samuel Coutinho']))
            ->assertOk()
            ->assertSee('Samuel Coutinho Especial')
            ->assertSee('Desligado');

        $this->actingAs($user)
            ->getJson(route('busca.sugestoes', ['q' => 'Samuel']))
            ->assertOk()
            ->assertJsonPath('grupos.0.itens.0.titulo', 'Samuel Coutinho Especial');
    }

    public function test_busca_exige_minimo_dois_caracteres(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        $this->actingAs($user)
            ->get(route('busca.index', ['q' => 'a']))
            ->assertOk()
            ->assertSee('2 caracteres');
    }
}
