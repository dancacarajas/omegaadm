<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ColaboradorExclusaoMassaTest extends TestCase
{
    use RefreshDatabase;

    public function test_exclui_colaboradores_selecionados_em_massa(): void
    {
        $user = User::factory()->create();
        $a = Colaborador::query()->create(['nome' => 'Ana', 'matricula' => '1', 'status' => 'ativo']);
        $b = Colaborador::query()->create(['nome' => 'Bruno', 'matricula' => '2', 'status' => 'ativo']);
        $c = Colaborador::query()->create(['nome' => 'Carla', 'matricula' => '3', 'status' => 'ativo']);

        $this->actingAs($user)
            ->post(route('rh.efetivo.excluir-massa'), [
                'colaborador_ids' => [$a->id, $b->id],
            ])
            ->assertRedirect(route('rh.efetivo.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('colaboradores', ['id' => $a->id]);
        $this->assertDatabaseMissing('colaboradores', ['id' => $b->id]);
        $this->assertDatabaseHas('colaboradores', ['id' => $c->id]);
    }

    public function test_lista_por_ordem_alfabetica(): void
    {
        $user = User::factory()->create();
        Colaborador::query()->create(['nome' => 'Zeca', 'matricula' => 'z', 'status' => 'ativo']);
        Colaborador::query()->create(['nome' => 'Ana', 'matricula' => 'a', 'status' => 'ativo']);

        $this->actingAs($user)
            ->get(route('rh.efetivo.index', ['ordenacao' => 'alfabetica']))
            ->assertOk()
            ->assertSeeInOrder(['Ana', 'Zeca'], false);
    }

    public function test_exclui_colaborador_mesmo_com_registros_tst(): void
    {
        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Técnico SST',
            'matricula' => '9',
            'cargo' => 'Técnico em Segurança do Trabalho',
            'status' => 'ativo',
        ]);

        DB::table('ssma_tst_registros')->insert([
            'colaborador_id' => $colaborador->id,
            'data' => now()->toDateString(),
            'descricao' => 'Teste',
            'arquivo_path' => 'tst/x.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('rh.efetivo.excluir-massa'), [
                'colaborador_ids' => [$colaborador->id],
            ])
            ->assertRedirect(route('rh.efetivo.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('colaboradores', ['id' => $colaborador->id]);
        $this->assertDatabaseMissing('ssma_tst_registros', ['colaborador_id' => $colaborador->id]);
    }
}
