<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilAlmoxarifadoSecoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_pode_restringir_area_almoxarifado_no_menu_e_na_rota(): void
    {
        $perfil = Perfil::query()->create([
            'nome' => 'Almoxarifado Parcial',
            'ativo' => true,
            'permissoes' => [
                'almoxarifado' => [
                    'visualizar' => true,
                    'criar' => false,
                    'editar' => false,
                    'excluir' => false,
                    'secoes' => [
                        'painel' => true,
                        'mobilizacao_materiais' => true,
                        'sigo_insumos' => false,
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create(['perfil_id' => $perfil->id]);

        $this->actingAs($user)
            ->get(route('almoxarifado.mobilizacao-materiais.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('almoxarifado.sigo-insumos.index'))
            ->assertForbidden();

        $this->assertTrue($user->podeSecaoAlmoxarifado('mobilizacao_materiais'));
        $this->assertFalse($user->podeSecaoAlmoxarifado('sigo_insumos'));
    }

    public function test_store_perfil_persiste_secoes_almoxarifado(): void
    {
        $admin = User::factory()->create(['perfil_id' => null]);

        $keys = array_keys(User::almoxarifadoSecoesDefinicao());
        $secoes = [];
        foreach ($keys as $key) {
            $secoes[$key] = $key === 'painel' ? '1' : '0';
        }

        $this->actingAs($admin)->post(route('perfis.store'), [
            'nome' => 'Almoxarifado Só Painel',
            'descricao' => 'Teste',
            'ativo' => '1',
            'permissoes' => [
                'almoxarifado' => [
                    'visualizar' => '1',
                    'secoes' => $secoes,
                ],
            ],
        ])->assertRedirect();

        $perfil = Perfil::query()->where('nome', 'Almoxarifado Só Painel')->firstOrFail();
        $this->assertTrue($perfil->permiteSecaoAlmoxarifado('painel'));
        $this->assertFalse($perfil->permiteSecaoAlmoxarifado('mobilizacao_materiais'));
    }
}
