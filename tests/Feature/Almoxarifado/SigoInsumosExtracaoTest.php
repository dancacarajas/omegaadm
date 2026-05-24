<?php

namespace Tests\Feature\Almoxarifado;

use App\Models\Perfil;
use App\Models\User;
use App\Support\Almoxarifado\SigoInsumosExtracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SigoInsumosExtracaoTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioGestao(): User
    {
        $perfil = Perfil::create([
            'nome' => 'Gestão Almox',
            'permissoes' => [
                'almoxarifado' => [
                    'visualizar' => true,
                    'criar' => true,
                    'editar' => true,
                    'excluir' => true,
                    'papel' => 'gestao',
                ],
            ],
            'ativo' => true,
        ]);

        return User::factory()->create(['perfil_id' => $perfil->id, 'todos_contratos' => true]);
    }

    private function usuarioAlmoxarife(): User
    {
        $perfil = Perfil::create([
            'nome' => 'Almoxarife',
            'permissoes' => [
                'almoxarifado' => [
                    'visualizar' => true,
                    'editar' => true,
                    'papel' => 'almoxarife',
                ],
            ],
            'ativo' => true,
        ]);

        return User::factory()->create(['perfil_id' => $perfil->id, 'todos_contratos' => true]);
    }

    public function test_gestao_acessa_tela_de_extracao(): void
    {
        $this->actingAs($this->usuarioGestao())
            ->get(route('almoxarifado.sigo-insumos.index'))
            ->assertOk()
            ->assertSee('Credenciais SIGO');
    }

    public function test_almoxarife_nao_acessa_extracao(): void
    {
        $this->actingAs($this->usuarioAlmoxarife())
            ->get(route('almoxarifado.sigo-insumos.index'))
            ->assertForbidden();
    }

    public function test_extracao_valida_credenciais_e_redireciona_com_resultado(): void
    {
        $mock = Mockery::mock(SigoInsumosExtracaoService::class);
        $mock->shouldReceive('extrair')
            ->once()
            ->with('usuario.sigo', 'senha123')
            ->andReturn([
                'ok' => true,
                'token' => '20260524_teste1234',
                'resumo' => [
                    'ok' => true,
                    'data_extracao' => '2026-05-24 10:00:00',
                    'paginas_lidas' => 12,
                    'registros_brutos' => 300,
                    'registros_unicos' => 280,
                ],
            ]);

        $this->app->instance(SigoInsumosExtracaoService::class, $mock);

        $this->actingAs($this->usuarioGestao())
            ->post(route('almoxarifado.sigo-insumos.extrair'), [
                'sigo_usuario' => 'usuario.sigo',
                'sigo_senha' => 'senha123',
            ])
            ->assertRedirect(route('almoxarifado.sigo-insumos.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('sigo_extracao_resultado');
    }

    public function test_extracao_exige_usuario_e_senha(): void
    {
        $this->actingAs($this->usuarioGestao())
            ->post(route('almoxarifado.sigo-insumos.extrair'), [])
            ->assertSessionHasErrors(['sigo_usuario', 'sigo_senha']);
    }
}
