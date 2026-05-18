<?php

namespace Tests\Feature\Ssma;

use App\Models\Colaborador;
use App\Models\SsmaTstAtividade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsmaTstAtividadeAppVisibilidadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_colaborador_lista_apenas_atividades_marcadas_para_app(): void
    {
        $colaborador = Colaborador::query()->create([
            'nome' => 'Colaborador App',
            'matricula' => '200',
            'cpf' => '11122233344',
            'status' => 'ativo',
        ]);

        SsmaTstAtividade::query()->create([
            'nome' => 'Visível no app',
            'ativo' => true,
            'exibir_no_app' => true,
            'ordem' => 1,
        ]);

        SsmaTstAtividade::query()->create([
            'nome' => 'Somente painel SSMA',
            'ativo' => true,
            'exibir_no_app' => false,
            'ordem' => 2,
        ]);

        SsmaTstAtividade::query()->create([
            'nome' => 'Inativa',
            'ativo' => false,
            'exibir_no_app' => true,
            'ordem' => 3,
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->get(route('tst-campo.index'))
            ->assertOk()
            ->assertSee('Visível no app')
            ->assertDontSee('Somente painel SSMA')
            ->assertDontSee('Inativa');
    }
}
