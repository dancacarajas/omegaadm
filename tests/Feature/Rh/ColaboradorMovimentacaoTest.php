<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Models\User;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradorMovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_desligamento_atualiza_colaborador(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '001',
            'status' => 'ativo',
            'data_admissao' => '2026-01-01',
            'centro_custo' => 'CC-A',
            'cargo' => 'Auxiliar',
        ]);

        $this->actingAs($user)->post(route('rh.efetivo.movimentacoes.store', $colab), [
            'tipo' => ColaboradorMovimentacaoTipos::DESLIGAMENTO,
            'data_inicio' => '2026-04-30',
            'tipo_rescisao' => 'sem_justa_causa',
            'motivo_texto' => 'Encerramento',
        ])->assertRedirect(route('rh.efetivo.show', $colab));

        $colab->refresh();
        $this->assertSame('desligado', $colab->status);
        $this->assertSame('2026-04-30', $colab->data_demissao->toDateString());

        $mov = ColaboradorMovimentacao::query()->where('colaborador_id', $colab->id)->first();
        $this->assertNotNull($mov);
        $this->assertSame(ColaboradorMovimentacaoTipos::DESLIGAMENTO, $mov->tipo);
    }

    public function test_registra_transferencia_atualiza_centro_custo(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'João',
            'status' => 'ativo',
            'centro_custo' => 'CC-OLD',
            'cargo' => 'Motorista',
        ]);

        $this->actingAs($user)->post(route('rh.efetivo.movimentacoes.store', $colab), [
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-04-06',
            'centro_custo_novo' => 'CC-NEW',
        ]);

        $colab->refresh();
        $this->assertSame('CC-NEW', $colab->centro_custo);
        $this->assertSame('CC-OLD', ColaboradorMovimentacao::query()->first()->centro_custo_anterior);
    }

    public function test_registra_afastamento_inss_muda_status(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Maria',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)->post(route('rh.efetivo.movimentacoes.store', $colab), [
            'tipo' => ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            'data_inicio' => today()->toDateString(),
            'especie_beneficio_inss' => 'auxilio_doenca',
            'cid' => 'M54.5',
        ]);

        $this->assertSame('afastado', $colab->fresh()->status);
    }

    public function test_pagina_movimentacoes_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('rh.efetivo.movimentacoes.index'))
            ->assertOk();
    }

    public function test_edita_afastamento_inss_alterando_especie(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Pedro',
            'status' => 'afastado',
        ]);

        $this->actingAs($user)->post(route('rh.efetivo.movimentacoes.store', $colab), [
            'tipo' => ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            'data_inicio' => '2026-05-14',
            'especie_beneficio_inss' => 'auxilio_acidente',
        ]);

        $mov = ColaboradorMovimentacao::query()->where('colaborador_id', $colab->id)->first();
        $this->assertNotNull($mov);

        $this->actingAs($user)
            ->get(route('rh.efetivo.movimentacoes.edit', [$colab, $mov]))
            ->assertOk()
            ->assertSee('Alterar movimentação', false)
            ->assertSee('auxilio_acidente', false);

        $this->actingAs($user)->put(route('rh.efetivo.movimentacoes.update', [$colab, $mov]), [
            'data_inicio' => '2026-05-14',
            'especie_beneficio_inss' => 'acidente_trabalho',
            'cid' => 'S82.0',
        ])->assertRedirect(route('rh.efetivo.show', $colab));

        $mov->refresh();
        $this->assertSame('acidente_trabalho', $mov->especie_beneficio_inss);
        $this->assertSame('S82.0', $mov->cid);
    }

    public function test_editar_movimentacao_de_outro_colaborador_retorna_404(): void
    {
        $user = User::factory()->create();
        $a = Colaborador::query()->create(['nome' => 'A', 'status' => 'ativo']);
        $b = Colaborador::query()->create(['nome' => 'B', 'status' => 'ativo']);
        $mov = ColaboradorMovimentacao::query()->create([
            'colaborador_id' => $b->id,
            'tipo' => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            'data_inicio' => '2026-05-01',
            'centro_custo_novo' => 'CC-B',
        ]);

        $this->actingAs($user)
            ->get(route('rh.efetivo.movimentacoes.edit', [$a, $mov]))
            ->assertNotFound();
    }
}
