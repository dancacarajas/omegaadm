<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Models\User;
use App\Services\Rh\ColaboradorMovimentacaoService;
use App\Support\Rh\AfastamentoAcidenteTrabalho;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfastamentoAcidenteTrabalhoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_movimentacao_acidente_fica_disponivel_para_regra(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Operador CAT',
            'status' => 'ativo',
        ]);

        app(ColaboradorMovimentacaoService::class)->registrar($colab, [
            'tipo' => ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            'data_inicio' => '2026-02-01',
            'especie_beneficio_inss' => 'acidente_trabalho',
            'cid' => 'S82.0',
            'forcar_finalizada' => true,
        ], $user->id);

        $situacao = AfastamentoAcidenteTrabalho::situacaoValeAlimentacaoNoMes(
            $colab->fresh(),
            Carbon::parse('2026-04-01'),
            3
        );

        $this->assertTrue($situacao['isento']);
        $this->assertSame(3, $situacao['mes_afastamento']);
        $this->assertNotNull(ColaboradorMovimentacao::query()->where('colaborador_id', $colab->id)->first());
    }
}
