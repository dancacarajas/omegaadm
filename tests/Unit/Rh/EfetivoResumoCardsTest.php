<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\HorarioEscala;
use App\Support\Rh\EfetivoResumoCards;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EfetivoResumoCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumo_contrato_alinha_com_movimentacao_na_data(): void
    {
        Contrato::query()->create([
            'numero' => '286',
            'centro_custo' => '286',
            'nome' => 'Obra 286',
            'status' => 'ativo',
        ]);

        $escala = HorarioEscala::query()->create(['nome' => 'Geral', 'tipo' => 'fixa']);

        $escalaCt = HorarioEscala::query()->create(['nome' => 'CT 286', 'tipo' => 'fixa']);

        Colaborador::query()->create([
            'nome' => 'No contrato',
            'status' => 'ativo',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
            'data_admissao' => '2026-01-01',
        ]);

        Colaborador::query()->create([
            'nome' => 'Pela escala',
            'status' => 'ativo',
            'centro_custo' => null,
            'horario_escala_id' => $escalaCt->id,
            'data_admissao' => '2026-01-01',
        ]);

        Colaborador::query()->create([
            'nome' => 'Afastado',
            'status' => 'afastado',
            'centro_custo' => '286',
            'horario_escala_id' => $escala->id,
        ]);

        Colaborador::query()->create([
            'nome' => 'Desligado',
            'status' => 'desligado',
            'centro_custo' => '286',
            'data_demissao' => '2026-04-01',
            'horario_escala_id' => $escala->id,
        ]);

        Colaborador::query()->create([
            'nome' => 'Outro CC',
            'status' => 'ativo',
            'centro_custo' => '999',
            'horario_escala_id' => $escala->id,
        ]);

        $r = EfetivoResumoCards::paraTelaEfetivo(Carbon::parse('2026-05-31'));

        $this->assertTrue($r['tem_contrato_ref']);
        $this->assertSame(3, $r['efetivo_operacional']);
        $this->assertSame(2, $r['efetivo_contrato']);
        $this->assertSame(2, $r['ativos_contrato']);
        $this->assertSame(5, $r['cadastros_total']);
    }
}
