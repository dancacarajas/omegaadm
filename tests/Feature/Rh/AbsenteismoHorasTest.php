<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Support\Rh\AbsenteismoPeriodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenteismoHorasTest extends TestCase
{
    use RefreshDatabase;

    public function test_atestado_entra_no_absenteismo_geral(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '1',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'justificado',
            'justificativa_tipo' => 'atestado',
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

        $r = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-03-22');

        $this->assertSame(16.0, $r['horas_previstas']);
        $this->assertSame(8.0, $r['horas_ausencia_geral']);
        $this->assertSame(8.0, $r['horas_ausencia_justificada']);
        $this->assertSame(0.0, $r['horas_ausencia_injustificada']);
        $this->assertSame(50.0, $r['taxa_geral']);
        $this->assertSame(50.0, $r['taxa_justificada']);
        $this->assertSame(0.0, $r['taxa_injustificada']);
    }

    public function test_atraso_abaixo_da_tolerancia_nao_conta_como_injustificada(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '2',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'presente',
            'entrada_1' => '08:05:00',
            'saida_1' => '17:00:00',
            'origem' => 'csv_ponto',
        ]);

        $r = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-03-21');

        $this->assertSame(0.0, $r['horas_ausencia_injustificada']);
        $this->assertSame(0.0, $r['taxa_injustificada']);
        $this->assertSame(0, $r['ausencias']);
    }

    public function test_folga_nao_entra_em_horas_previstas(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-21',
            'status' => 'folga',
            'origem' => 'csv_ponto',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-22',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $r = app(AbsenteismoPeriodo::class)->calcular('2026-03-21', '2026-03-22');

        $this->assertSame(8.0, $r['horas_previstas']);
        $this->assertSame(8.0, $r['horas_ausencia_geral']);
        $this->assertSame(100.0, $r['taxa_geral']);
        $this->assertSame(1, $r['ausencias']);
    }
}
