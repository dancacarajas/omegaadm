<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\FrequenciaFeriado;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Support\FeriadoPontoService;
use App\Services\Rh\CafeDaManhaCalculoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeDaManhaCalculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_atestado_sem_horas_trabalhadas_nao_conta_dia(): void
    {
        [$colab, $beneficio, $vinculo] = $this->cenarioCafe();

        $dia = Carbon::parse('2026-04-10');
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => $dia->toDateString(),
            'status' => 'justificado',
            'justificativa_tipo' => 'atestado',
            'justificativa_texto' => 'Atestado médico',
        ]);

        $this->diaComBatidas($colab, Carbon::parse('2026-04-09'));

        $calc = app(CafeDaManhaCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertTrue($calc['aplica']);
        $this->assertSame(1, $calc['dias_trabalhados']);
        $this->assertSame(1, $calc['dias_com_justificativa_sem_trabalho']);
        $this->assertEquals(7.95, $calc['valor_final']);
        $this->assertEquals(7.95, $calc['valor_descontado']);
        $this->assertCount(2, $calc['dias_apuracao']);
        $tipos = array_column($calc['dias_apuracao'], 'tipo');
        $this->assertContains('trabalhado', $tipos);
        $this->assertContains('justificado_sem_horas', $tipos);
    }

    public function test_valor_proporcional_e_teto_mensal(): void
    {
        [$colab, $beneficio, $vinculo] = $this->cenarioCafe();

        $uteis = 0;
        foreach (range(1, 22) as $d) {
            $dia = Carbon::parse('2026-04-'.str_pad((string) $d, 2, '0', STR_PAD_LEFT));
            if ($dia->isWeekend()) {
                continue;
            }
            $this->diaComBatidas($colab, $dia);
            $uteis++;
        }

        $calc = app(CafeDaManhaCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertSame($uteis, $calc['dias_trabalhados']);
        $this->assertEqualsWithDelta($uteis * 7.95, $calc['valor_proporcional'], 0.01);
        $this->assertEqualsWithDelta($uteis * 7.95, $calc['valor_final'], 0.01);
    }

    public function test_sabado_domingo_e_feriado_nao_entram_na_apuracao(): void
    {
        [$colab, $beneficio, $vinculo] = $this->cenarioCafe();

        $this->diaComBatidas($colab, Carbon::parse('2026-04-08'));

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => Carbon::parse('2026-04-11')->toDateString(),
            'status' => 'justificado',
            'justificativa_texto' => 'Mobilização SGC',
        ]);

        FrequenciaFeriado::query()->create([
            'data' => '2026-04-03',
            'nome' => 'Semana Santa',
            'recorrente' => false,
            'ativo' => true,
        ]);
        FeriadoPontoService::limparCache();

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-03',
            'status' => 'justificado',
            'origem' => FeriadoPontoService::ORIGEM,
            'justificativa_texto' => 'Semana Santa',
        ]);

        $calc = app(CafeDaManhaCalculoService::class)->calcularParaVinculo(
            $vinculo,
            $beneficio,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-15')
        );

        $this->assertSame(1, $calc['dias_trabalhados']);
        $this->assertSame(0, $calc['dias_com_justificativa_sem_trabalho']);
        $this->assertCount(1, $calc['dias_apuracao']);
        $this->assertSame('2026-04-08', $calc['dias_apuracao'][0]['data']);
    }

    public function test_inferir_tipo_cafe_pelo_nome(): void
    {
        $b = Beneficio::query()->create([
            'nome' => 'Café da manhã',
            'status' => 'ativo',
            'valor' => 175,
        ]);

        $this->assertSame(BeneficioExtratoRegra::TIPO_CAFE_MANHA, BeneficioExtratoRegra::inferirTipoRegra($b));
    }

    /**
     * @return array{0: Colaborador, 1: Beneficio, 2: ColaboradorBeneficio}
     */
    private function cenarioCafe(): array
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
            'nome' => 'Teste Café',
            'status' => 'ativo',
            'data_admissao' => '2020-01-01',
            'horario_escala_id' => $escala->id,
        ]);

        $beneficio = Beneficio::query()->create([
            'nome' => 'Café da manhã',
            'valor' => 175,
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

    private function diaComBatidas(Colaborador $colab, Carbon $dia): void
    {
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => $dia->toDateString(),
            'status' => 'normal',
            'entrada_1' => '08:00',
            'saida_1' => '12:00',
            'entrada_2' => '13:00',
            'saida_2' => '17:00',
            'origem' => 'manual',
        ]);
    }
}
