<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeneficioColaboradorVinculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_nao_e_capturado_pela_rota_show(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        $this->actingAs($user)
            ->get('/rh/beneficios/create')
            ->assertOk()
            ->assertSee('Novo benefício', false);
    }

    public function test_excluir_beneficio_pela_listagem(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale Teste', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'João', 'status' => 'ativo']);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colaborador->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('rh.beneficios.destroy', $beneficio))
            ->assertRedirect(route('rh.beneficios.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('beneficios', ['id' => $beneficio->id]);
        $this->assertDatabaseMissing('colaborador_beneficios', ['beneficio_id' => $beneficio->id]);
    }

    public function test_show_retorna_404_quando_beneficio_nao_existe(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        $this->actingAs($user)
            ->get('/rh/beneficios/99999')
            ->assertNotFound();
    }

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
            ->post(route('rh.beneficios.show', $beneficio), [
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
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'cartao_entregue' => '1',
                'beneficio_ativo' => '1',
                'tem_direito' => '1',
            ]);

        $response->assertRedirect(route('rh.beneficios.show', $beneficio).'?vinculo='.$vinculo->id.'#vinculo-'.$vinculo->id);
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
            ->post(route('rh.beneficios.show', $beneficio), [
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

    public function test_post_com_script_name_public_index_casa_rota_rh(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($user)
            ->call(
                'POST',
                '/rh/beneficios/'.$beneficio->id,
                [
                    'vinculo_id' => $vinculo->id,
                    'acao' => 'salvar',
                    'tem_direito' => '1',
                ],
                [],
                [],
                [
                    'SCRIPT_NAME' => '/public/index.php',
                    'REQUEST_URI' => '/public/rh/beneficios/'.$beneficio->id,
                ]
            )
            ->assertRedirect(route('rh.beneficios.show', $beneficio).'?vinculo='.$vinculo->id.'#vinculo-'.$vinculo->id);
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

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficioA))
            ->post(route('rh.beneficios.show', $beneficioA), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
            ])
            ->assertRedirect(route('rh.beneficios.show', $beneficioA))
            ->assertSessionHasErrors('vinculo_id');
    }

    public function test_salvar_vinculo_quando_beneficio_id_vem_como_string_do_banco(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'Elinaldo', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => true,
            'cartao_entregue' => false,
        ]);

        \DB::table('colaborador_beneficios')->whereKey($vinculo->id)->update(['beneficio_id' => '1']);
        $vinculo->refresh();
        $this->assertSame('1', (string) $vinculo->getRawOriginal('beneficio_id'));

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
                'cartao_entregue' => '1',
                'beneficio_ativo' => '1',
            ])
            ->assertRedirect(route('rh.beneficios.show', $beneficio).'?vinculo='.$vinculo->id.'#vinculo-'.$vinculo->id)
            ->assertSessionHasNoErrors();

        $vinculo->refresh();
        $this->assertTrue($vinculo->cartao_entregue);
    }

    public function test_show_pagina_vinculos_em_lotes_de_25(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);

        for ($i = 1; $i <= 30; $i++) {
            $colaborador = Colaborador::query()->create([
                'nome' => sprintf('Colaborador %02d', $i),
                'status' => 'ativo',
            ]);
            ColaboradorBeneficio::query()->create([
                'beneficio_id' => $beneficio->id,
                'colaborador_id' => $colaborador->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('rh.beneficios.show', $beneficio))
            ->assertOk()
            ->assertSee('Exibindo 1–25 de 30', false)
            ->assertSee('Colaborador 01', false)
            ->assertDontSee('Colaborador 30', false);

        $this->actingAs($user)
            ->get(route('rh.beneficios.show', ['beneficio' => $beneficio, 'page' => 2]))
            ->assertOk()
            ->assertSee('Exibindo 26–30 de 30', false)
            ->assertSee('Colaborador 30', false)
            ->assertDontSee('Colaborador 01', false);
    }

    public function test_excluir_vinculo_elinaldo_cenario(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'VALE ALIMENTAÇÃO', 'status' => 'ativo']);
        $colaborador = Colaborador::query()->create(['nome' => 'ELINALDO CHAGAS DE OLIVEIRA', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
        ]);

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'excluir',
            ])
            ->assertRedirect(route('rh.beneficios.show', $beneficio))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('colaborador_beneficios', ['id' => $vinculo->id]);
    }

    public function test_salvar_com_vinculo_id_inexistente_retorna_erro_nao_404(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => 99999,
                'acao' => 'salvar',
                'tem_direito' => '1',
            ])
            ->assertRedirect(route('rh.beneficios.show', $beneficio))
            ->assertSessionHasErrors('vinculo_id');
    }

    public function test_data_entrega_cartao_marca_cartao_entregue_sem_checkbox(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'Maria', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => true,
            'cartao_entregue' => false,
        ]);

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
                'cartao_entregue' => '0',
                'data_entrega_cartao' => '2026-05-20',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $vinculo->refresh();
        $this->assertTrue($vinculo->cartao_entregue);
        $this->assertSame('2026-05-20', $vinculo->data_entrega_cartao?->format('Y-m-d'));
    }

    public function test_upload_e_download_formulario_adesao_assinado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'João', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($user)
            ->from(route('rh.beneficios.show', $beneficio))
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
                'formulario_adesao_assinado' => UploadedFile::fake()->create('adesao.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $vinculo->refresh();
        $this->assertNotNull($vinculo->formulario_adesao_assinado_path);
        Storage::disk('public')->assertExists($vinculo->formulario_adesao_assinado_path);

        $this->actingAs($user)
            ->get(route('rh.beneficios.vinculos.formulario-adesao', [
                'beneficio' => $beneficio,
                'vinculo' => $vinculo,
            ]))
            ->assertOk();
    }
}
