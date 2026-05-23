<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
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

    public function test_extrato_mostra_direito_trinta_porcento_do_salario(): void
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

        $calc = app(WebcardCalculoService::class)->calcularParaVinculo(
            $vinculo->load('colaborador'),
            $beneficio,
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(900.0, $calc['valor_final']);
        $this->assertSame(900.0, $calc['valor_direito_mensal']);
        $this->assertSame(0.0, $calc['valor_descontado']);
        $this->assertSame(3000.0, $calc['salario_referencia']);
    }

    public function test_extrato_aplica_teto_mensal_quando_percentual_ultrapassa(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Carlos',
            'status' => 'ativo',
            'salario_inicial' => 6000,
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'WebCard', 'codigo' => 'webcard', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'cartao_entregue' => true,
            'beneficio_ativo' => true,
        ]);

        $calc = app(WebcardCalculoService::class)->calcularParaVinculo(
            $vinculo->load('colaborador'),
            $beneficio,
            Carbon::parse('2026-05-01')
        );

        $this->assertSame(1500.0, $calc['valor_final']);
    }

    public function test_extrato_integrado_com_webcard(): void
    {
        Carbon::setTestNow('2026-05-20');
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'status' => 'ativo',
            'salario_inicial' => 3000,
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'WebCard', 'codigo' => 'webcard', 'status' => 'ativo']);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'cartao_entregue' => true,
            'beneficio_ativo' => true,
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

        $this->assertSame(900.0, $extrato['total']);
        $this->assertSame(0.0, $extrato['total_descontos']);
    }
}
