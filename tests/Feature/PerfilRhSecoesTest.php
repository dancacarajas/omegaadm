<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilRhSecoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_pode_restringir_area_rh_no_menu_e_na_rota(): void
    {
        $perfil = Perfil::query()->create([
            'nome' => 'RH Parcial',
            'ativo' => true,
            'permissoes' => [
                'rh' => [
                    'visualizar' => true,
                    'criar' => false,
                    'editar' => false,
                    'excluir' => false,
                    'secoes' => [
                        'dashboard' => true,
                        'efetivo' => true,
                        'chamados_movimentacao' => false,
                        'beneficios' => false,
                        'recrutamento' => false,
                        'frequencia_ponto' => false,
                        'frequencia_apuracao' => false,
                        'frequencia_feriados' => false,
                        'frequencia_justificativas' => false,
                        'horarios' => false,
                        'indicadores_mensais' => false,
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create(['perfil_id' => $perfil->id]);

        $this->actingAs($user)
            ->get(route('rh.efetivo.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('rh.chamados-movimentacao.index'))
            ->assertForbidden();

        $this->assertTrue($user->podeSecaoRh('efetivo'));
        $this->assertFalse($user->podeSecaoRh('chamados_movimentacao'));

        $this->actingAs($user)
            ->get(route('rh.efetivo.index'))
            ->assertOk()
            ->assertDontSee('Novo colaborador', false);

        $colab = \App\Models\Colaborador::query()->create([
            'nome' => 'Teste SSMA',
            'status' => 'ativo',
            'matricula' => '9999',
        ]);

        $this->actingAs($user)
            ->get(route('rh.efetivo.edit', $colab))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('rh.efetivo.create'))
            ->assertForbidden();
    }

    public function test_store_perfil_persiste_secoes_rh(): void
    {
        $admin = User::factory()->create(['perfil_id' => null]);

        $keys = array_keys(\App\Models\User::rhSecoesDefinicao());
        $secoes = [];
        foreach ($keys as $key) {
            $secoes[$key] = $key === 'efetivo' ? '1' : '0';
        }

        $this->actingAs($admin)->post(route('perfis.store'), [
            'nome' => 'RH Só Efetivo',
            'descricao' => 'Teste',
            'ativo' => '1',
            'permissoes' => [
                'rh' => [
                    'visualizar' => '1',
                    'secoes' => $secoes,
                ],
            ],
        ])->assertRedirect();

        $perfil = Perfil::query()->where('nome', 'RH Só Efetivo')->firstOrFail();
        $this->assertTrue($perfil->permiteSecaoRh('efetivo'));
        $this->assertFalse($perfil->permiteSecaoRh('chamados_movimentacao'));
    }
}
