<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use App\Support\Rh\ColaboradorVinculoPonto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadoresMensaisPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_aceita_periodo_inicial_e_final_customizado(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        Contrato::query()->create([
            'numero' => 'C-001',
            'nome' => 'Contrato teste',
            'centro_custo' => 'CC-TEST',
            'status' => 'ativo',
        ]);
        Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '1',
            'status' => 'ativo',
            'centro_custo' => 'CC-TEST',
            'data_admissao' => '2026-03-01',
        ]);

        $this->actingAs($user)
            ->get(route('rh.indicadores-mensais.painel-executivo', [
                'contrato' => 'CC-TEST',
                'periodo_inicio' => '2026-03-21',
                'periodo_fim' => '2026-04-20',
            ]))
            ->assertOk()
            ->assertSee('21/03/2026 a 20/04/2026', false)
            ->assertSee('value="2026-03-21"', false)
            ->assertSee('value="2026-04-20"', false);
    }

    public function test_usar_mes_competencia_ignora_datas_do_formulario(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        Contrato::query()->create([
            'numero' => 'C-002',
            'nome' => 'Contrato B',
            'centro_custo' => 'CC-B',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->get(route('rh.indicadores-mensais.painel-executivo', [
                'contrato' => 'CC-B',
                'competencia' => '2026-05',
                'usar_mes_competencia' => '1',
                'periodo_inicio' => '2026-01-01',
                'periodo_fim' => '2026-01-15',
            ]))
            ->assertOk()
            ->assertSee('value="2026-05-01"', false)
            ->assertSee('value="2026-05-31"', false);
    }

    public function test_painel_absenteismo_alinhado_a_frequencia_sem_faltas_injustificadas(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        Contrato::query()->create([
            'numero' => '286',
            'nome' => 'Contrato 286',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '1',
            'status' => 'ativo',
            'centro_custo' => '286',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'justificado',
            'origem' => 'csv_ponto',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-22',
            'status' => 'presente',
            'entrada_1' => '08:00:00',
            'saida_1' => '17:00:00',
            'origem' => 'csv_ponto',
        ]);

        $this->actingAs($user)
            ->get(route('rh.indicadores-mensais.painel-executivo', [
                'contrato' => '286',
                'periodo_inicio' => '2026-03-21',
                'periodo_fim' => '2026-03-22',
            ]))
            ->assertOk()
            ->assertSee('50,0%', false)
            ->assertSee('Absenteísmo geral', false)
            ->assertSee('Absenteísmo justificado', false)
            ->assertSee('Horas de ausência', false);
    }

    public function test_painel_nao_duplica_abono_em_duas_categorias(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        Contrato::query()->create([
            'numero' => '286',
            'nome' => 'Contrato 286',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Mob',
            'status' => 'ativo',
            'centro_custo' => '286',
            'data_admissao' => '2026-03-01',
        ]);

        foreach (['2026-03-21', '2026-03-22'] as $data) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colab->id,
                'data' => $data,
                'status' => 'justificado',
                'justificativa_tipo' => 'abono',
                'origem' => 'csv_ponto',
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('rh.indicadores-mensais.painel-executivo', [
                'contrato' => '286',
                'periodo_inicio' => '2026-03-21',
                'periodo_fim' => '2026-03-22',
            ]))
            ->assertOk()
            ->assertSee('Registros de ocorrência', false)
            ->assertSee('Horas de ausência', false)
            ->assertSee('100,0%', false);

        $this->assertSame(2, FrequenciaRegistro::query()->count());

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Abonos / mobilização'));
        $this->assertStringNotContainsString('Saídas antecipadas', $html);
        $this->assertStringNotContainsString('Faltas justificadas', $html);
        preg_match(
            '/Registros de ocorrência[\s\S]{0,200}?sm:text-2xl">(\d+)</',
            $html,
            $totalCard
        );
        $this->assertSame('2', $totalCard[1] ?? '', 'Card de ocorrências deve refletir os 2 abonos');
    }

    public function test_card_movimentacao_exibe_transferencias_no_grafico_e_leitura(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        Contrato::query()->create([
            'numero' => '286',
            'nome' => 'Contrato 286',
            'centro_custo' => '286',
            'status' => 'ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Transferido',
            'status' => 'ativo',
            'centro_custo' => 'OUTRO',
        ]);
        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colab->id,
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-04-10',
            'centro_custo_anterior' => 'OUTRO',
            'centro_custo_novo' => '286',
        ]);

        $this->actingAs($user)
            ->get(route('rh.indicadores-mensais.painel-executivo', [
                'contrato' => '286',
                'periodo_inicio' => '2026-04-01',
                'periodo_fim' => '2026-04-30',
            ]))
            ->assertOk()
            ->assertSee('Transf. entrada', false)
            ->assertSee('transferências internas (1 entrada(s)', false)
            ->assertSee('1 transferência(s) de entrada', false);
    }
}
