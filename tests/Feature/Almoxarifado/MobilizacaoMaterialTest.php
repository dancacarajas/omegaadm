<?php

namespace Tests\Feature\Almoxarifado;

use App\Models\Almoxarifado\MobilizacaoMaterial;
use App\Models\Almoxarifado\MobilizacaoMaterialCategoria;
use App\Models\Contrato;
use App\Models\Perfil;
use App\Models\User;
use App\Support\Almoxarifado\MobilizacaoMaterialStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilizacaoMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_e_cadastro_com_recalculo_de_status(): void
    {
        $perfil = Perfil::create([
            'nome' => 'Teste Almox',
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

        $user = User::factory()->create(['perfil_id' => $perfil->id, 'todos_contratos' => true]);
        $contrato = Contrato::create([
            'numero' => 'CT-TEST',
            'nome' => 'Contrato teste',
            'status' => 'ativo',
        ]);
        $categoria = MobilizacaoMaterialCategoria::first();

        $this->actingAs($user)
            ->get(route('almoxarifado.painel'))
            ->assertOk();

        $response = $this->actingAs($user)->post(route('almoxarifado.mobilizacao-materiais.store'), [
            'contrato_id' => $contrato->id,
            'disciplina' => 'ELÉTRICA',
            'categoria_descricao' => 'Material elétrico',
            'situacao_tratativa' => 'SEM TRATATIVA LOCALIZADA',
            'descricao_material' => 'Cabo PP 4mm',
            'unidade_medida' => 'M',
            'quantidade_necessaria' => 100,
            'prioridade' => 'ALTA',
        ]);

        $response->assertRedirect();
        $material = MobilizacaoMaterial::first();
        $this->assertNotNull($material);
        $this->assertSame(MobilizacaoMaterialStatus::SEM_TRATATIVA, $material->status);
        $this->assertSame(100.0, (float) $material->saldo_a_comprar);

        $this->actingAs($user)->post(route('almoxarifado.mobilizacao-materiais.sigo', $material), [
            'numero_pm' => 'PM-123',
            'quantidade_pedida_sigo' => 100,
        ])->assertRedirect();

        $material->refresh();
        $this->assertSame(MobilizacaoMaterialStatus::PEDIDO_NO_SIGO, $material->status);
        $this->assertStringContainsString('Cobrar Compras', $material->acao_do_dia);
    }

    public function test_altera_status_direto_na_lista(): void
    {
        $perfil = Perfil::create([
            'nome' => 'Teste Almox',
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

        $user = User::factory()->create(['perfil_id' => $perfil->id, 'todos_contratos' => true]);
        $contrato = Contrato::create([
            'numero' => 'CT-TEST2',
            'nome' => 'Contrato teste 2',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)->post(route('almoxarifado.mobilizacao-materiais.store'), [
            'contrato_id' => $contrato->id,
            'disciplina' => 'ELÉTRICA',
            'categoria_descricao' => 'Material elétrico',
            'situacao_tratativa' => 'SEM TRATATIVA LOCALIZADA',
            'descricao_material' => 'Disjuntor 32A',
            'unidade_medida' => 'UN',
            'quantidade_necessaria' => 10,
            'prioridade' => 'MEDIA',
        ])->assertRedirect();

        $material = MobilizacaoMaterial::query()->where('descricao_material', 'Disjuntor 32A')->first();
        $this->assertNotNull($material);

        $this->actingAs($user)
            ->patch(route('almoxarifado.mobilizacao-materiais.status', $material), [
                'status' => MobilizacaoMaterialStatus::PEDIDO_NO_SIGO,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $material->refresh();
        $this->assertSame(MobilizacaoMaterialStatus::PEDIDO_NO_SIGO, $material->status);
    }
}
