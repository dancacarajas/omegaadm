<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\User;
use App\Support\Rh\ExtratoAusenciasPeriodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtratoAusenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_extrato_lista_atestado_e_falta_com_horas(): void
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
            'origem' => 'manual',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-22',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $extrato = app(ExtratoAusenciasPeriodo::class)->montar('2026-03-21', '2026-03-22');

        $this->assertSame(2, $extrato['total_ocorrencias']);
        $this->assertSame(16.0, $extrato['total_horas_ausencia']);
        $this->assertSame(8.0, $extrato['total_horas_justificada']);
        $this->assertSame(8.0, $extrato['total_horas_injustificada']);

        $injust = app(ExtratoAusenciasPeriodo::class)->montar(
            '2026-03-21',
            '2026-03-22',
            null,
            ExtratoAusenciasPeriodo::NATUREZA_INJUSTIFICADA
        );
        $this->assertSame(1, $injust['total_ocorrencias']);
        $this->assertSame(50.0, $injust['resumo_exibicao']['taxa_injustificada']);
        $this->assertSame(8.0, $injust['resumo_exibicao']['horas_ausencia_injustificada']);

        FrequenciaRegistro::query()->where('status', 'falta')->delete();
        $semInjust = app(ExtratoAusenciasPeriodo::class)->montar(
            '2026-03-21',
            '2026-03-22',
            null,
            ExtratoAusenciasPeriodo::NATUREZA_INJUSTIFICADA
        );
        $this->assertSame(0, $semInjust['total_ocorrencias']);
        $this->assertSame(0.0, $semInjust['resumo_exibicao']['taxa_injustificada']);
        $this->assertSame(0.0, $semInjust['resumo_exibicao']['horas_ausencia_injustificada']);
    }

    public function test_pagina_extrato_exibe_taxas_e_natureza(): void
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
            ->assertSee('Absenteísmo geral', false)
            ->assertSee('h. ausência', false)
            ->assertSee('Falta injustificada', false);
    }
}
