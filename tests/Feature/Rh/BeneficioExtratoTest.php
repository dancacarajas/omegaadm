<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use App\Services\Rh\BeneficioExtratoCalculoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficioExtratoTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioRh(): User
    {
        return User::factory()->create(['todos_contratos' => true]);
    }

    public function test_pagina_config_extrato(): void
    {
        $this->actingAs($this->usuarioRh())
            ->get(route('rh.beneficios.extrato.config'))
            ->assertOk()
            ->assertSee('Quais benefícios entram no extrato', false);
    }

    public function test_salvar_config_exige_ao_menos_um_beneficio(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
            'valor' => 750,
        ]);

        $this->actingAs($this->usuarioRh())
            ->post(route('rh.beneficios.extrato.config.salvar'), [
                'beneficios' => [
                    $beneficio->id => ['ativo' => '0'],
                ],
            ])
            ->assertSessionHasErrors('beneficios');

        $this->assertDatabaseCount('beneficio_extrato_regras', 0);
    }

    public function test_extrato_consolidado_dois_beneficios(): void
    {
        Carbon::setTestNow('2026-05-15');

        $colab = Colaborador::query()->create([
            'nome' => 'Maria',
            'status' => 'ativo',
            'data_admissao' => '2020-01-01',
        ]);

        $vale = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
            'valor' => 500,
        ]);
        $cafe = Beneficio::query()->create([
            'nome' => 'Café da manhã',
            'status' => 'ativo',
            'valor' => 175,
        ]);

        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $vale->id,
            'tem_direito' => true,
        ]);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $cafe->id,
            'tem_direito' => true,
        ]);

        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $vale->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_VALOR_FIXO,
            'ativo' => true,
            'configurado' => true,
        ]);
        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $cafe->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_CAFE_MANHA,
            'ativo' => true,
            'configurado' => true,
            'parametros' => \App\Support\Rh\CafeDaManhaRegraConfig::padroes(),
        ]);

        $extrato = app(BeneficioExtratoCalculoService::class)->montarExtratoColaborador(
            $colab,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertCount(2, $extrato['linhas']);
        // Vale fixo 500 + café sem dias trabalhados no teste = 0
        $this->assertEquals(500.0, $extrato['total']);

        Carbon::setTestNow();
    }

    public function test_salvar_regras_vale_alimentacao_via_post(): void
    {
        $user = $this->usuarioRh();
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
            'valor' => 750,
        ]);
        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $beneficio->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_ASSIDUIDADE,
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.extrato.regras.salvar', $beneficio), [
                'ano_vigencia' => 2026,
                'desconto_faltas' => [
                    ['de' => 1, 'ate' => 1, 'percentual' => 25],
                    ['de' => 2, 'ate' => null, 'percentual' => 100],
                ],
                'proporcional_admissao_demissao' => '1',
                'afastamento_acidente_trabalho' => ['ativo' => '1', 'meses_limite_integral' => 3],
                'recarga_natal' => ['ativo' => '0'],
            ])
            ->assertRedirect(route('rh.beneficios.extrato.regras'));

        $regra = BeneficioExtratoRegra::query()->where('beneficio_id', $beneficio->id)->first();
        $this->assertTrue($regra->configurado);
        $this->assertSame(2026, $regra->ano_vigencia);
        $this->assertSame(0.25, $regra->configValeAlimentacao()->percentualDescontoPorFaltas(1));
    }

    public function test_pagina_gerar_com_colaborador(): void
    {
        $colab = Colaborador::query()->create(['nome' => 'João', 'status' => 'ativo']);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'valor' => 500,
        ]);
        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $beneficio->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_VALOR_FIXO,
            'ativo' => true,
            'configurado' => true,
        ]);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($this->usuarioRh())
            ->get(route('rh.beneficios.extrato.gerar', [
                'colaborador_id' => $colab->id,
                'periodo_inicio' => '01/04/2026',
                'periodo_fim' => '30/04/2026',
            ]))
            ->assertOk()
            ->assertSee('Total do extrato', false)
            ->assertSee('R$ 500,00', false);
    }

    public function test_pagina_gerar_nao_lista_colaborador_desligado(): void
    {
        $desligado = Colaborador::query()->create([
            'nome' => 'Jarbas Desligado',
            'matricula' => '022214',
            'status' => 'desligado',
            'data_demissao' => '2026-04-30',
        ]);
        $ativo = Colaborador::query()->create([
            'nome' => 'Maria Ativa',
            'status' => 'ativo',
        ]);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'valor' => 500,
        ]);
        BeneficioExtratoRegra::query()->create([
            'beneficio_id' => $beneficio->id,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_VALOR_FIXO,
            'ativo' => true,
            'configurado' => true,
        ]);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $desligado->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => false,
            'beneficio_ativo' => false,
        ]);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $ativo->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
        ]);

        $this->actingAs($this->usuarioRh())
            ->get(route('rh.beneficios.extrato.gerar', [
                'colaborador_id' => $desligado->id,
                'periodo_inicio' => '21/04/2026',
                'periodo_fim' => '20/05/2026',
            ]))
            ->assertOk()
            ->assertDontSee('Jarbas Desligado', false)
            ->assertSee('Maria Ativa', false)
            ->assertDontSee('Total do extrato', false);
    }
}
