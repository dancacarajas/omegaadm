<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Services\Rh\ValeAlimentacaoCalculoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValeAlimentacaoCalculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentual_desconto_por_faltas(): void
    {
        $s = app(ValeAlimentacaoCalculoService::class);
        $this->assertSame(0.0, $s->percentualDescontoAssiduidade(0));
        $this->assertSame(0.20, $s->percentualDescontoAssiduidade(1));
        $this->assertSame(0.50, $s->percentualDescontoAssiduidade(2));
        $this->assertSame(1.0, $s->percentualDescontoAssiduidade(3));
    }

    public function test_calculo_750_sem_faltas_mes_anterior(): void
    {
        Carbon::setTestNow('2026-05-15');
        [$colab, $beneficio, $vinculo] = $this->cenarioValeAlimentacao();

        $mes = Carbon::parse('2026-04-01');
        $this->diaUtilComFalta($colab, $mes->copy()->day(10));

        $calc = app(ValeAlimentacaoCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertTrue($calc['aplica']);
        $this->assertSame(1, $calc['faltas_injustificadas']);
        $this->assertSame(20, $calc['percentual_desconto']);
        $this->assertEquals(600.0, $calc['valor_final']);
        $this->assertEquals(150.0, $calc['valor_descontado']);
        Carbon::setTestNow();
    }

    public function test_justificado_nao_conta_como_falta_injustificada(): void
    {
        Carbon::setTestNow('2026-05-15');
        [$colab, $beneficio, $vinculo] = $this->cenarioValeAlimentacao();

        $dia = Carbon::parse('2026-04-10');
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => $dia->toDateString(),
            'status' => 'justificado',
            'justificativa_tipo' => 'atestado',
            'justificativa_texto' => 'Atestado médico',
        ]);

        $calc = app(ValeAlimentacaoCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertSame(0, $calc['faltas_injustificadas']);
        $this->assertEquals(750.0, $calc['valor_final']);
        Carbon::setTestNow();
    }

    public function test_soma_faltas_entre_duas_datas(): void
    {
        Carbon::setTestNow('2026-05-15');
        [$colab, $beneficio, $vinculo] = $this->cenarioValeAlimentacao();

        $this->diaUtilComFalta($colab, Carbon::parse('2026-03-10'));
        $this->diaUtilComFalta($colab, Carbon::parse('2026-04-13'));

        $calc = app(ValeAlimentacaoCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertSame(2, $calc['faltas_injustificadas']);
        $this->assertSame(50, $calc['percentual_desconto']);
        $this->assertStringContainsString('01/03/2026', $calc['periodo_apuracao']);
        $this->assertStringContainsString('30/04/2026', $calc['periodo_apuracao']);
        Carbon::setTestNow();
    }

    public function test_proporcional_admissao_no_meio_do_mes(): void
    {
        Carbon::setTestNow('2026-05-15');
        [$colab, $beneficio, $vinculo] = $this->cenarioValeAlimentacao();
        $colab->update(['data_admissao' => '2026-05-16']);
        $vinculo->unsetRelation('colaborador');
        $vinculo->load('colaborador');

        $calc = app(ValeAlimentacaoCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-05-01')
        );

        $this->assertLessThan(1.0, $calc['fator_proporcional']);
        $this->assertLessThan(750.0, $calc['valor_final']);
        Carbon::setTestNow();
    }

    /**
     * @return array{0: Colaborador, 1: Beneficio, 2: ColaboradorBeneficio}
     */
    private function cenarioValeAlimentacao(): array
    {
        $escala = HorarioEscala::query()->create(['nome' => 'Comercial', 'tipo' => 'fixa']);
        foreach (range(1, 5) as $dow) {
            HorarioEscalaDia::query()->create([
                'horario_escala_id' => $escala->id,
                'dia_semana' => $dow,
                'entrada_1' => '08:00',
                'saida_1' => '12:00',
                'entrada_2' => '13:00',
                'saida_2' => '17:00',
            ]);
        }

        $colab = Colaborador::query()->create([
            'nome' => 'Teste VA',
            'status' => 'ativo',
            'data_admissao' => '2020-01-01',
            'horario_escala_id' => $escala->id,
        ]);

        $beneficio = Beneficio::query()->create([
            'nome' => 'VALE ALIMENTAÇÃO',
            'tipo' => 'MENSAL',
            'codigo' => 'ALELO001',
            'valor' => 750,
            'status' => 'ativo',
        ]);

        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colab->id,
            'tem_direito' => true,
        ]);
        $vinculo->load('colaborador');

        return [$colab, $beneficio, $vinculo];
    }

    private function diaUtilComFalta(Colaborador $colab, Carbon $dia): void
    {
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => $dia->toDateString(),
            'status' => 'falta',
            'origem' => 'grade',
        ]);
    }
}
