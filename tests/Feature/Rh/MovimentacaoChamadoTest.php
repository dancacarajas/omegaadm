<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\User;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacaoChamadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_abrir_chamado_desligamento_nao_altera_cadastro_ate_finalizar(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create(['nome' => 'Teste', 'status' => 'ativo']);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), [
            'colaborador_id' => $colab->id,
            'tipo' => MovimentacaoChamadoTipo::DESLIGAMENTO,
            'data_efetiva' => '2026-05-15',
            'tipo_rescisao' => 'pedido_demissao',
            'motivo_texto' => 'Teste chamado',
        ])->assertRedirect();

        $this->assertSame('ativo', $colab->fresh()->status);

        $chamado = RhMovimentacaoChamado::query()->first();
        $this->assertNotNull($chamado);
        $this->assertSame(MovimentacaoChamadoStatus::ABERTO, $chamado->status);
        $this->assertGreaterThanOrEqual(9, $chamado->etapas()->count());
        $this->assertStringStartsWith('MOV-RH-', $chamado->protocolo);
    }
}
