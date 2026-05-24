<?php

namespace Tests\Feature\Almoxarifado;

use App\Jobs\ExtrairSigoInsumosJob;
use App\Models\Almoxarifado\SigoExtracao;
use App\Models\Perfil;
use App\Models\User;
use App\Support\Almoxarifado\SigoInsumosExtracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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

    public function test_extracao_valida_credenciais_e_enfileira_job(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $uuid = (string) Str::uuid();
        $registro = new SigoExtracao([
            'uuid' => $uuid,
            'user_id' => 1,
            'sigo_usuario' => 'usuario.sigo',
            'status' => SigoExtracao::STATUS_PENDENTE,
            'diretorio_relativo' => 'almoxarifado/sigo-extracoes/'.$uuid,
        ]);
        $registro->id = 99;

        $mock = Mockery::mock(SigoInsumosExtracaoService::class);
        $mock->shouldReceive('iniciarExtracao')
            ->once()
            ->with(Mockery::type('int'), 'usuario.sigo', 'senha123')
            ->andReturn($registro);

        $this->app->instance(SigoInsumosExtracaoService::class, $mock);

        $user = $this->usuarioGestao();

        $this->actingAs($user)
            ->post(route('almoxarifado.sigo-insumos.extrair'), [
                'sigo_usuario' => 'usuario.sigo',
                'sigo_senha' => 'senha123',
            ])
            ->assertRedirect(route('almoxarifado.sigo-insumos.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('sigo_extracao_uuid', $uuid);

        Queue::assertPushed(ExtrairSigoInsumosJob::class, fn (ExtrairSigoInsumosJob $job) => $job->sigoExtracaoId === 99);
    }

    public function test_status_retorna_json_da_extracao_do_usuario(): void
    {
        $user = $this->usuarioGestao();
        $registro = SigoExtracao::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'sigo_usuario' => 'usuario.sigo',
            'status' => SigoExtracao::STATUS_CONCLUIDO,
            'paginas_lidas' => 5,
            'registros_brutos' => 100,
            'registros_unicos' => 90,
            'finalizado_em' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('almoxarifado.sigo-insumos.status', ['uuid' => $registro->uuid]))
            ->assertOk()
            ->assertJsonPath('uuid', $registro->uuid)
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('registros_unicos', 90);
    }

    public function test_extracao_exige_usuario_e_senha(): void
    {
        $this->actingAs($this->usuarioGestao())
            ->post(route('almoxarifado.sigo-insumos.extrair'), [])
            ->assertSessionHasErrors(['sigo_usuario', 'sigo_senha']);
    }
}
