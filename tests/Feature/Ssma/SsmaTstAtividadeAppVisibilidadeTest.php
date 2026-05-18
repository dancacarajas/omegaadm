<?php

namespace Tests\Feature\Ssma;

use App\Models\Colaborador;
use App\Models\Perfil;
use App\Models\SsmaTstAtividade;
use App\Models\User;
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

    public function test_colaborador_com_perfil_ssma_ve_todas_atividades_ativas_no_app(): void
    {
        $perfilSsma = Perfil::query()->create([
            'nome' => 'SSMA',
            'ativo' => true,
            'permissoes' => ['sesmt' => ['visualizar' => true]],
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'CHRISTIAM LIMA PINTO',
            'matricula' => '22228',
            'cpf' => '11122233344',
            'status' => 'ativo',
        ]);

        User::factory()->create([
            'name' => 'CHRISTIAM LIMA PINTO',
            'colaborador_id' => $colaborador->id,
            'perfil_id' => $perfilSsma->id,
            'status' => 'ativo',
        ]);

        SsmaTstAtividade::query()->create([
            'nome' => 'INSPEÇÃO INTERNA OMEGA',
            'ativo' => true,
            'exibir_no_app' => true,
            'ordem' => 1,
        ]);

        SsmaTstAtividade::query()->create([
            'nome' => 'CAMPANHAS DE SEGURANÇA',
            'ativo' => true,
            'exibir_no_app' => false,
            'ordem' => 2,
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->get(route('tst-campo.index'))
            ->assertOk()
            ->assertSee('INSPEÇÃO INTERNA OMEGA')
            ->assertSee('CAMPANHAS DE SEGURANÇA');
    }

    public function test_colaborador_ssma_pode_registrar_atividade_nao_marcada_para_app(): void
    {
        $perfilSsma = Perfil::query()->create([
            'nome' => 'SSMA',
            'ativo' => true,
            'permissoes' => ['sesmt' => ['visualizar' => true]],
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'Tecnico SSMA',
            'matricula' => '22229',
            'cpf' => '55566677788',
            'status' => 'ativo',
        ]);

        User::factory()->create([
            'name' => 'Tecnico SSMA',
            'colaborador_id' => $colaborador->id,
            'perfil_id' => $perfilSsma->id,
            'status' => 'ativo',
        ]);

        $atividadePainel = SsmaTstAtividade::query()->create([
            'nome' => 'REC EMITIDOS',
            'ativo' => true,
            'exibir_no_app' => false,
        ]);

        $this->withSession(['tst_colaborador_id' => $colaborador->id])
            ->post(route('tst-campo.store'), [
                'ssma_tst_atividade_id' => $atividadePainel->id,
                'data' => '2026-05-18',
                'descricao' => 'Registro interno SSMA.',
                'arquivos' => [\Illuminate\Http\UploadedFile::fake()->image('foto.jpg')],
            ])
            ->assertRedirect(route('tst-campo.index'))
            ->assertSessionHas('success');
    }
}
