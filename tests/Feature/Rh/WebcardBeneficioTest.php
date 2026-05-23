<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\ColaboradorBeneficioWebcardSolicitacao;
use App\Models\User;
use App\Services\Rh\BeneficioExtratoCalculoService;
use App\Services\Rh\WebcardCalculoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebcardBeneficioTest extends TestCase
{
    use RefreshDatabase;

    public function test_inferir_tipo_webcard(): void
    {
        $b = Beneficio::query()->create(['nome' => 'WebCard Adiantamento', 'status' => 'ativo']);
        $this->assertSame(BeneficioExtratoRegra::TIPO_WEBCARD, BeneficioExtratoRegra::inferirTipoRegra($b));
    }

    public function test_limite_por_solicitacao_e_trinta_porcento_do_salario(): void
    {
        $config = \App\Support\Rh\WebcardRegraConfig::resolver(null);
        $this->assertSame(900.0, $config->limitePorSolicitacaoParaSalario(3000));
        $this->assertSame(30.0, $config->percentualLimitePorSolicitacao());
    }

    public function test_calculo_desconta_solicitacoes_do_mes_na_folha(): void
    {
        Carbon::setTestNow('2026-05-15');

        $colab = Colaborador::query()->create([
            'nome' => 'João',
            'status' => 'ativo',
            'salario_inicial' => 3000,
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'WebCard', 'codigo' => 'webcard', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'cartao_entregue' => true,
            'beneficio_ativo' => true,
        ]);

        ColaboradorBeneficioWebcardSolicitacao::query()->create([
            'colaborador_beneficio_id' => $vinculo->id,
            'data_solicitacao' => '2026-05-10',
            'valor' => 300,
        ]);
        ColaboradorBeneficioWebcardSolicitacao::query()->create([
            'colaborador_beneficio_id' => $vinculo->id,
            'data_solicitacao' => '2026-05-12',
            'valor' => 250.50,
        ]);

        $calc = app(WebcardCalculoService::class)->calcularParaVinculo(
            $vinculo->load('colaborador'),
            $beneficio,
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(550.50, $calc['valor_descontado']);
        $this->assertSame(900.0, $calc['limite_por_solicitacao']);
        $this->assertSame(0.0, $calc['valor_final']);
        $this->assertCount(2, $calc['solicitacoes']);
    }

    public function test_registra_solicitacao_respeitando_limites(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create([
            'nome' => 'Maria',
            'status' => 'ativo',
            'salario_inicial' => 3000,
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'WebCard', 'codigo' => 'webcard', 'status' => 'ativo']);
        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $beneficio->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'parametros' => \App\Support\Rh\WebcardRegraConfig::padroes(),
            'configurado' => true,
            'ativo' => true,
        ]);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.webcard.solicitacoes.store', $beneficio), [
                'colaborador_beneficio_id' => $vinculo->id,
                'data_solicitacao' => '2026-05-20',
                'valor' => 900,
            ])
            ->assertRedirect(route('rh.beneficios.show', $beneficio));

        $this->assertDatabaseHas('colaborador_beneficio_webcard_solicitacoes', [
            'colaborador_beneficio_id' => $vinculo->id,
            'valor' => 900,
        ]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.webcard.solicitacoes.store', $beneficio), [
                'colaborador_beneficio_id' => $vinculo->id,
                'data_solicitacao' => '2026-05-21',
                'valor' => 900.01,
            ])
            ->assertSessionHasErrors('valor');
    }

    public function test_extrato_integrado_com_webcard(): void
    {
        Carbon::setTestNow('2026-05-20');
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'status' => 'ativo',
            'salario_inicial' => 5000,
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'WebCard', 'codigo' => 'webcard', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'cartao_entregue' => true,
            'beneficio_ativo' => true,
        ]);
        ColaboradorBeneficioWebcardSolicitacao::query()->create([
            'colaborador_beneficio_id' => $vinculo->id,
            'data_solicitacao' => '2026-05-05',
            'valor' => 500,
        ]);
        $regra = BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $beneficio->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'parametros' => \App\Support\Rh\WebcardRegraConfig::padroes(),
            'configurado' => true,
            'ativo' => true,
        ]);

        $extrato = app(BeneficioExtratoCalculoService::class)->montarExtratoColaborador(
            $colab,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            collect([$regra->load('beneficio')])
        );

        $this->assertSame(500.0, $extrato['total_descontos']);
        $this->assertSame(0.0, $extrato['total']);
    }
}
