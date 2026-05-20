<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficioColaboradorVinculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_vincula_colaborador_ao_beneficio(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create([
            'nome' => 'Ana',
            'status' => 'ativo',
            'data_admissao' => '2026-01-15',
        ]);

        $response = $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.colaboradores.store', $beneficio), [
            'colaborador_id' => $colaborador->id,
            'tem_direito' => '1',
            'cartao_entregue' => '0',
        ]);

        $response->assertRedirect(route('rh.beneficios.show', $beneficio));
        $this->assertDatabaseHas('colaborador_beneficios', [
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => 1,
        ]);
    }

    public function test_atualiza_vinculo_do_beneficio(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => true,
            'cartao_entregue' => false,
            'beneficio_ativo' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.colaboradores.store', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'cartao_entregue' => '1',
                'beneficio_ativo' => '1',
                'tem_direito' => '1',
            ]);

        $response->assertRedirect(route('rh.beneficios.show', $beneficio));
        $vinculo->refresh();
        $this->assertTrue($vinculo->cartao_entregue);
        $this->assertTrue($vinculo->beneficio_ativo);
    }

    public function test_remove_vinculo_do_beneficio(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.colaboradores.store', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'excluir',
            ]);

        $response->assertRedirect(route('rh.beneficios.show', $beneficio));
        $this->assertDatabaseMissing('colaborador_beneficios', ['id' => $vinculo->id]);
    }

    public function test_show_filtra_e_ordeniza_vinculos_por_nome(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);

        $ze = Colaborador::query()->create(['nome' => 'Zeca', 'status' => 'ativo']);
        $ana = Colaborador::query()->create(['nome' => 'Ana Silva', 'status' => 'ativo']);

        ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $ze->id,
        ]);
        ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $ana->id,
        ]);

        $this->actingAs($user)
            ->get(route('rh.beneficios.show', [
                'beneficio' => $beneficio,
                'busca' => 'Ana',
                'ordenacao' => 'alfabetica',
            ]))
            ->assertOk()
            ->assertSee('Ana Silva', false)
            ->assertDontSee('Zeca', false);
    }

    public function test_show_filtra_cartao_pendente(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);

        $pendente = Colaborador::query()->create(['nome' => 'Colab Alfa', 'status' => 'ativo']);
        $entregue = Colaborador::query()->create(['nome' => 'Colab Beta', 'status' => 'ativo']);

        ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $pendente->id,
            'tem_direito' => true,
            'cartao_entregue' => false,
        ]);
        ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $entregue->id,
            'tem_direito' => true,
            'cartao_entregue' => true,
        ]);

        $this->actingAs($user)
            ->get(route('rh.beneficios.show', [
                'beneficio' => $beneficio,
                'cartao' => 'pendente',
            ]))
            ->assertOk()
            ->assertSee('Colab Alfa', false)
            ->assertDontSee('Colab Beta', false);
    }

    public function test_nao_atualiza_vinculo_de_outro_beneficio(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficioA = Beneficio::query()->create(['nome' => 'A', 'status' => 'ativo']);
        $beneficioB = Beneficio::query()->create(['nome' => 'B', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficioB->id,
            'colaborador_id' => $colaborador->id,
        ]);

        $this->actingAs($user)->post(route('rh.beneficios.colaboradores.store', $beneficioA), [
            'vinculo_id' => $vinculo->id,
            'acao' => 'salvar',
            'tem_direito' => '1',
        ])->assertNotFound();
    }
}
