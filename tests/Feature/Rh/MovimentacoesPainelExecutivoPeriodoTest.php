<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use App\Support\Rh\MovimentacoesPainelExecutivoPeriodo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacoesPainelExecutivoPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolida_transferencia_promocao_e_desligamento_sem_duplicar(): void
    {
        $colabEntrada = Colaborador::query()->create([
            'nome' => 'Entrada',
            'status' => 'ativo',
            'centro_custo' => 'CC-286',
        ]);
        $colabSaida = Colaborador::query()->create([
            'nome' => 'Saída',
            'status' => 'ativo',
            'centro_custo' => 'CC-OUTRO',
        ]);
        $colabDeslig = Colaborador::query()->create([
            'nome' => 'Desligado',
            'status' => 'desligado',
            'centro_custo' => 'CC-286',
            'data_demissao' => '2026-04-15',
        ]);

        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colabEntrada->id,
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-04-06',
            'centro_custo_anterior' => 'CC-OLD',
            'centro_custo_novo' => 'CC-286',
        ]);
        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colabSaida->id,
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-04-10',
            'centro_custo_anterior' => 'CC-286',
            'centro_custo_novo' => 'CC-NEW',
        ]);
        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colabDeslig->id,
            'tipo' => ColaboradorMovimentacaoTipos::DESLIGAMENTO,
            'data_inicio' => '2026-04-15',
            'tipo_rescisao' => 'pedido_demissao',
        ]);
        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colabEntrada->id,
            'tipo' => ColaboradorMovimentacaoTipos::PROMOCAO,
            'data_inicio' => '2026-04-20',
            'cargo_anterior' => 'Aux',
            'cargo_novo' => 'Líder',
        ]);

        $ini = Carbon::parse('2026-04-01');
        $fim = Carbon::parse('2026-04-30');

        $resumo = MovimentacoesPainelExecutivoPeriodo::resumo(['CC-286'], $ini, $fim);

        $this->assertSame(1, $resumo['transferencia_entrada']);
        $this->assertSame(1, $resumo['transferencia_saida']);
        $this->assertSame(1, $resumo['promocoes']);
        $this->assertSame(1, $resumo['desligados']);
        $this->assertGreaterThanOrEqual(1, count($resumo['motivos']));
        $this->assertTrue(
            collect($resumo['motivos'])->contains(fn ($m) => str_contains($m['label'], 'Pedido de demissão'))
        );
    }

    public function test_transferencia_entrada_reconhece_centro_custo_numerico_equivalente(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Numérico',
            'status' => 'ativo',
            'centro_custo' => 'OUTRO',
        ]);
        ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $colab->id,
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-04-12',
            'centro_custo_anterior' => 'OUTRO',
            'centro_custo_novo' => '0286',
        ]);

        $resumo = MovimentacoesPainelExecutivoPeriodo::resumo(
            ['286'],
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertSame(1, $resumo['transferencia_entrada']);
    }

    public function test_desligamento_somente_por_data_demissao_no_cadastro(): void
    {
        Colaborador::query()->create([
            'nome' => 'Legado',
            'status' => 'desligado',
            'centro_custo' => '286',
            'data_demissao' => '2026-03-20',
        ]);

        $resumo = MovimentacoesPainelExecutivoPeriodo::resumo(
            ['286'],
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31')
        );

        $this->assertSame(1, $resumo['desligados']);
    }
}
