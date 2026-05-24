<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use App\Services\Rh\BeneficioProtocoloEntregaCartaoPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficioProtocoloEntregaCartaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_pdf_protocolo_com_multiplos_colaboradores(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'WEBCARD',
            'status' => 'ativo',
        ]);

        $colab1 = Colaborador::query()->create([
            'nome' => 'Vinícius Araújo Costa',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);
        $colab2 = Colaborador::query()->create([
            'nome' => 'Danilo Gonzaga Silva',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);

        $v1 = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab1->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);
        $v2 = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab2->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $pdf = app(BeneficioProtocoloEntregaCartaoPdfService::class)->render(
            $beneficio,
            collect([$v1->load('colaborador'), $v2->load('colaborador')]),
            'Maria Entregadora',
            'Assistente RH',
        );

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(8000, strlen($pdf));
        $this->assertStringNotContainsString('&nbsp;', $pdf);
    }

    public function test_rota_pdf_exige_autenticacao_e_valida_vinculos(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Colaborador Teste',
            'centro_custo' => '312',
            'status' => 'ativo',
        ]);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $this->post(route('rh.beneficios.protocolo-entrega.pdf', $beneficio), [
            'vinculo_ids' => [$vinculo->id],
        ])->assertRedirect();

        $user = User::factory()->create(['perfil_id' => null]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.protocolo-entrega.pdf', $beneficio), [
                'vinculo_ids' => [$vinculo->id],
                'entregador_nome' => 'RH Omega',
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_rejeita_vinculo_de_outro_beneficio(): void
    {
        $beneficioA = Beneficio::query()->create(['nome' => 'A', 'status' => 'ativo']);
        $beneficioB = Beneficio::query()->create(['nome' => 'B', 'status' => 'ativo']);
        $colab = Colaborador::query()->create(['nome' => 'X', 'status' => 'ativo']);
        $vinculoB = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficioB->id,
            'tem_direito' => true,
        ]);

        $user = User::factory()->create(['perfil_id' => null]);

        $this->actingAs($user)
            ->postJson(route('rh.beneficios.protocolo-entrega.pdf', $beneficioA), [
                'vinculo_ids' => [$vinculoB->id],
            ])
            ->assertStatus(422);
    }
}
