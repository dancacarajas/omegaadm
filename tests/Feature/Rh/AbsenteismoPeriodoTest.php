<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaJustificativaTipo;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\Rh\AbsenteismoPeriodo;
use App\Support\Rh\ColaboradorVinculoPonto;
use App\Support\Rh\ExtratoFaltasPeriodo;
use App\Support\Rh\FrequenciaRegistroReconciliacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AbsenteismoPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_conta_justificado_nem_folga_como_ausencia(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '1',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'presente',
            'entrada_1' => '08:00:00',
            'saida_1' => '17:00:00',
            'origem' => 'csv_ponto',
        ]);
        foreach ([
            ['2026-03-22', 'justificado'],
            ['2026-03-23', 'folga'],
            ['2026-03-24', 'falta'],
        ] as [$data, $status]) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colab->id,
                'data' => $data,
                'status' => $status,
                'origem' => 'csv_ponto',
            ]);
        }

        $resultado = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-03-24');

        $this->assertSame(1, $resultado['ausencias']);
        $this->assertSame(3, $resultado['base']);
        $this->assertSame(66.7, $resultado['taxa_geral']);
        $this->assertSame(33.3, $resultado['taxa_injustificada']);
    }

    public function test_filtra_absenteismo_por_colaborador(): void
    {
        $user = User::factory()->create();
        $jarbas = Colaborador::query()->create(['nome' => 'Jarbas', 'matricula' => '1', 'status' => 'ativo']);
        $outro = Colaborador::query()->create(['nome' => 'Outro', 'matricula' => '2', 'status' => 'ativo']);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $jarbas->id,
            'data' => '2026-03-21',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $outro->id,
            'data' => '2026-03-21',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $this->actingAs($user)
            ->get(route('rh.frequencia.index', [
                'absenteismo_inicio' => '2026-03-21',
                'absenteismo_fim' => '2026-03-21',
                'absenteismo_colaborador_id' => $jarbas->id,
                'absenteismo_calcular' => 1,
            ]))
            ->assertOk()
            ->assertSee('Jarbas', false)
            ->assertSee('0,0%', false);
    }

    public function test_painel_frequencia_exibe_taxa_corrigida(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '1',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'justificado',
            'justificativa_tipo' => 'abono',
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
            ->get(route('rh.frequencia.index', [
                'absenteismo_inicio' => '2026-03-21',
                'absenteismo_fim' => '2026-03-22',
                'absenteismo_calcular' => 1,
            ]))
            ->assertOk()
            ->assertSee('50,0%', false)
            ->assertSee('Absenteísmo geral', false);
    }

    public function test_extrato_faltas_agrupa_por_colaborador(): void
    {
        $a = Colaborador::query()->create(['nome' => 'Ana', 'matricula' => '1', 'status' => 'ativo']);
        $b = Colaborador::query()->create(['nome' => 'Bruno', 'matricula' => '2', 'status' => 'ativo']);

        foreach (['2026-03-21', '2026-03-22'] as $data) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $a->id,
                'data' => $data,
                'status' => 'falta',
                'origem' => 'grade',
            ]);
        }
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-03-21',
            'status' => 'falta',
            'origem' => 'csv_ponto',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-03-22',
            'status' => 'justificado',
            'origem' => 'csv_ponto',
        ]);

        $extrato = app(ExtratoFaltasPeriodo::class)->montar('2026-03-21', '2026-03-22');

        $this->assertSame(3, $extrato['total_faltas']);
        $this->assertCount(2, $extrato['colaboradores']);
        $this->assertSame(2, $extrato['colaboradores'][0]['total_faltas']);
        $this->assertSame('Ana', $extrato['colaboradores'][0]['colaborador']->nome);
    }

    public function test_pagina_extrato_faltas(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create(['nome' => 'Ana', 'matricula' => '1', 'status' => 'ativo']);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-24',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $this->actingAs($user)
            ->get(route('rh.frequencia.extrato-faltas', [
                'data_inicio' => '2026-03-21',
                'data_fim' => '2026-03-24',
            ]))
            ->assertOk()
            ->assertSee('Extrato de ausências', false)
            ->assertSee('Ana', false)
            ->assertSee('24/03/2026', false);

        $this->actingAs($user)
            ->get(route('rh.frequencia.index', [
                'absenteismo_inicio' => '2026-03-21',
                'absenteismo_fim' => '2026-03-24',
                'absenteismo_calcular' => 1,
            ]))
            ->assertOk()
            ->assertSee('Extrato de ausências', false);
    }

    public function test_nao_conta_falta_antes_da_data_admissao(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Samuel',
            'matricula' => '022479',
            'status' => 'ativo',
            'data_admissao' => '2026-04-07',
        ]);

        foreach (['2026-03-23', '2026-04-07', '2026-04-08'] as $data) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colab->id,
                'data' => $data,
                'status' => 'falta',
                'origem' => 'csv_ponto',
            ]);
        }

        $this->assertFalse(ColaboradorVinculoPonto::contaPontoNaData($colab, '2026-03-23'));
        $this->assertTrue(ColaboradorVinculoPonto::contaPontoNaData($colab, '2026-04-07'));

        app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
            '2026-03-21',
            '2026-04-20',
            $colab->id
        );

        $this->assertDatabaseMissing('frequencia_registros', [
            'colaborador_id' => $colab->id,
            'data' => '2026-03-23',
        ]);
        $this->assertTrue(
            FrequenciaRegistro::query()
                ->where('colaborador_id', $colab->id)
                ->whereDate('data', '2026-04-07')
                ->where('status', 'falta')
                ->exists()
        );

        $absenteismo = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-04-20', $colab->id);
        $this->assertSame(2, $absenteismo['ausencias']);

        $extrato = app(ExtratoFaltasPeriodo::class)->montar('2026-03-21', '2026-04-20', $colab->id);
        $this->assertSame(2, $extrato['total_faltas']);
        $datasExtrato = collect($extrato['colaboradores'][0]['faltas'])->pluck('data')->all();
        $this->assertNotContains('2026-03-23', $datasExtrato);
    }

    public function test_ranking_usa_mesmo_periodo_do_absenteismo(): void
    {
        $a = Colaborador::query()->create(['nome' => 'ALFA RANKING PERIODO TESTE', 'matricula' => '1', 'status' => 'ativo']);
        $b = Colaborador::query()->create(['nome' => 'BETA RANKING PERIODO TESTE', 'matricula' => '2', 'status' => 'ativo']);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $a->id,
            'data' => '2026-03-21',
            'status' => 'falta',
            'origem' => 'grade',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-04-15',
            'status' => 'falta',
            'origem' => 'grade',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-05-10',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $ranking = FrequenciaRegistro::query()
            ->select('colaborador_id', DB::raw('count(*) as total_faltas'))
            ->whereDate('data', '>=', '2026-03-21')
            ->whereDate('data', '<=', '2026-04-20')
            ->where('status', 'falta')
            ->whereHas('colaborador', function ($q) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            })
            ->groupBy('colaborador_id')
            ->get()
            ->keyBy('colaborador_id');

        $this->assertSame(1, (int) $ranking->get($a->id)?->total_faltas);
        $this->assertSame(1, (int) $ranking->get($b->id)?->total_faltas);
    }

    public function test_ranking_atestados_no_periodo(): void
    {
        $a = Colaborador::query()->create(['nome' => 'A ATESTADO RANK', 'matricula' => '10', 'status' => 'ativo']);
        $b = Colaborador::query()->create(['nome' => 'B ATESTADO RANK', 'matricula' => '11', 'status' => 'ativo']);

        $tipo = FrequenciaJustificativaTipo::query()->create([
            'nome' => 'Atestado Médico',
            'categoria' => 'atestado',
            'limpa_batidas' => true,
            'ativo' => true,
            'ordem' => 1,
        ]);

        foreach (['2026-04-01', '2026-04-02', '2026-04-03'] as $data) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $a->id,
                'data' => $data,
                'status' => 'justificado',
                'justificativa_tipo' => 'atestado',
                'justificativa_tipo_id' => $tipo->id,
                'origem' => 'manual',
            ]);
        }

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-04-10',
            'status' => 'justificado',
            'justificativa_tipo' => 'atestado',
            'origem' => 'csv_ponto',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $b->id,
            'data' => '2026-05-15',
            'status' => 'justificado',
            'justificativa_tipo' => 'atestado',
            'origem' => 'csv_ponto',
        ]);

        $ranking = FrequenciaRegistro::query()
            ->select('colaborador_id', DB::raw('count(*) as total_atestados'))
            ->whereDate('data', '>=', '2026-04-01')
            ->whereDate('data', '<=', '2026-04-20')
            ->atestadoMedico()
            ->whereHas('colaborador', function ($q) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            })
            ->groupBy('colaborador_id')
            ->get()
            ->keyBy('colaborador_id');

        $this->assertSame(3, (int) $ranking->get($a->id)?->total_atestados);
        $this->assertSame(1, (int) $ranking->get($b->id)?->total_atestados);
    }

    public function test_ranking_mensal_ignora_faltas_antes_da_admissao(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'ZELIA RANKING ADMISSAO TESTE',
            'matricula' => '99',
            'status' => 'ativo',
            'data_admissao' => '2026-04-15',
        ]);

        foreach (['2026-04-01', '2026-04-10'] as $data) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $colab->id,
                'data' => $data,
                'status' => 'falta',
                'origem' => 'csv_ponto',
            ]);
        }

        app(FrequenciaRegistroReconciliacao::class)->removerRegistrosForaDoVinculoNoPeriodo(
            '2026-04-01',
            '2026-04-30'
        );

        $this->assertSame(
            0,
            FrequenciaRegistro::query()
                ->where('colaborador_id', $colab->id)
                ->where('status', 'falta')
                ->count()
        );

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-20',
            'status' => 'falta',
            'origem' => 'csv_ponto',
        ]);

        $ranking = FrequenciaRegistro::query()
            ->select('colaborador_id')
            ->whereDate('data', '>=', '2026-04-01')
            ->whereDate('data', '<=', '2026-04-30')
            ->where('status', 'falta')
            ->whereHas('colaborador', function ($q) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            })
            ->where('colaborador_id', $colab->id)
            ->count();

        $this->assertSame(1, $ranking);
    }

    public function test_calcular_para_contrato_filtra_centro_custo(): void
    {
        $noContrato = Colaborador::query()->create([
            'nome' => 'Fora',
            'matricula' => '9',
            'status' => 'ativo',
            'centro_custo' => 'OUTRO',
        ]);
        $no286 = Colaborador::query()->create([
            'nome' => 'No 286',
            'matricula' => '8',
            'status' => 'ativo',
            'centro_custo' => '286',
        ]);

        foreach ([$noContrato, $no286] as $c) {
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $c->id,
                'data' => '2026-03-21',
                'status' => 'falta',
                'origem' => 'grade',
            ]);
            FrequenciaRegistro::query()->create([
                'colaborador_id' => $c->id,
                'data' => '2026-03-22',
                'status' => 'justificado',
                'origem' => 'csv_ponto',
            ]);
        }

        $geral = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-03-22');
        $contrato = app(AbsenteismoPeriodo::class)->calcularParaContrato('2026-03-21', '2026-03-22', ['286']);

        $this->assertSame(2, $geral['ausencias']);
        $this->assertSame(1, $contrato['ausencias']);
        $this->assertSame(2, $contrato['base']);
        $this->assertSame(100.0, $contrato['taxa_geral']);
        $this->assertSame(50.0, $contrato['taxa_injustificada']);
        $this->assertSame(100.0, $contrato['taxa']);
    }
}
