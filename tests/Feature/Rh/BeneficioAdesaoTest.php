<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use App\Services\Rh\BeneficioAdesaoService;
use App\Support\Rh\BeneficioAdesaoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficioAdesaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_inicial_manual_com_formulario(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'WebCard',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
            'adesao_automatica_admissao' => false,
            'exige_formulario_colaborador' => true,
        ]);

        $this->assertSame(
            BeneficioAdesaoStatus::PENDENTE_FORMULARIO,
            app(BeneficioAdesaoService::class)->statusInicialParaBeneficio($beneficio)
        );
    }

    public function test_painel_lista_vinculo_em_andamento(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create(['nome' => 'Pedro', 'status' => 'ativo']);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
            'adesao_automatica_admissao' => false,
        ]);
        ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'status_adesao' => BeneficioAdesaoStatus::FORMULARIO_RECEBIDO,
            'data_formulario_recebido' => '2026-05-10',
        ]);

        $this->actingAs($user)
            ->get(route('rh.beneficios.adesoes.index'))
            ->assertOk()
            ->assertSee('Pedro')
            ->assertSee('Formulário recebido');
    }

    public function test_atualiza_datas_adesao_no_vinculo(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create(['nome' => 'Lucia', 'status' => 'ativo']);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Plano Saúde',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'status_adesao' => BeneficioAdesaoStatus::FORMULARIO_RECEBIDO,
        ]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
                'status_adesao' => BeneficioAdesaoStatus::ENVIADO_MATRIZ,
                'data_envio_matriz' => '2026-05-20',
                'protocolo_matriz' => 'email-matriz-123',
            ])
            ->assertRedirect();

        $vinculo->refresh();
        $this->assertSame(BeneficioAdesaoStatus::AGUARDANDO_CARTAO, $vinculo->status_adesao);
        $this->assertSame('2026-05-20', $vinculo->data_envio_matriz->format('Y-m-d'));
        $this->assertSame('email-matriz-123', $vinculo->protocolo_matriz);
    }

    public function test_indicador_dias_entre_pedido_e_aviso_coleta(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'Plano',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colab = Colaborador::query()->create(['nome' => 'Teste', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'data_envio_matriz' => '2026-05-01',
            'data_aviso_coleta_matriz' => '2026-05-10',
            'status_adesao' => BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA,
        ]);

        $adesao = app(BeneficioAdesaoService::class);
        $this->assertSame(9, $adesao->diasEntrePedidoMatrizEAvisoColeta($vinculo));

        $indicador = $adesao->indicadorPrazoMatriz($vinculo);
        $this->assertSame('aviso_recebido', $indicador['tipo']);
        $this->assertSame(9, $indicador['dias']);
    }

    public function test_indicador_dias_aguardando_aviso_sem_previsao(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'Plano',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colab = Colaborador::query()->create(['nome' => 'Teste', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'data_envio_matriz' => now()->subDays(20)->toDateString(),
            'status_adesao' => BeneficioAdesaoStatus::AGUARDANDO_CARTAO,
        ]);

        $adesao = app(BeneficioAdesaoService::class);
        $this->assertSame(20, $adesao->diasAguardandoAvisoMatriz($vinculo));
        $this->assertTrue($adesao->cartaoAtrasado($vinculo, 15));

        $indicador = $adesao->indicadorPrazoMatriz($vinculo, 15);
        $this->assertSame('aguardando_aviso', $indicador['tipo']);
        $this->assertTrue($indicador['alerta']);
    }

    public function test_salvar_aviso_coleta_atualiza_status(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create(['nome' => 'Maria', 'status' => 'ativo']);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'data_envio_matriz' => '2026-05-01',
            'status_adesao' => BeneficioAdesaoStatus::AGUARDANDO_CARTAO,
        ]);

        $this->actingAs($user)
            ->post(route('rh.beneficios.show', $beneficio), [
                'vinculo_id' => $vinculo->id,
                'acao' => 'salvar',
                'tem_direito' => '1',
                'status_adesao' => BeneficioAdesaoStatus::AGUARDANDO_CARTAO,
                'data_envio_matriz' => '2026-05-01',
                'data_aviso_coleta_matriz' => '2026-05-12',
            ])
            ->assertRedirect();

        $vinculo->refresh();
        $this->assertSame(BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA, $vinculo->status_adesao);
        $this->assertSame('2026-05-12', $vinculo->data_aviso_coleta_matriz->format('Y-m-d'));
    }
}
