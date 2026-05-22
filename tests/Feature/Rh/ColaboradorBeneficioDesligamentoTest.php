<?php

namespace Tests\Feature\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\User;
use App\Services\Rh\ColaboradorMovimentacaoService;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradorBeneficioDesligamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_desligamento_por_movimentacao_desativa_beneficios(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'status' => 'ativo',
            'data_admissao' => '2026-01-01',
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'beneficio_ativo' => true,
        ]);

        app(ColaboradorMovimentacaoService::class)->registrar($colab, [
            'tipo' => ColaboradorMovimentacaoTipos::DESLIGAMENTO,
            'data_inicio' => '2026-04-30',
            'tipo_rescisao' => 'sem_justa_causa',
            'motivo_texto' => 'Encerramento',
        ], $user->id);

        $vinculo->refresh();
        $this->assertFalse($vinculo->tem_direito);
        $this->assertFalse($vinculo->beneficio_ativo);
        $this->assertStringContainsString('desligamento', (string) $vinculo->observacoes);
    }

    public function test_edicao_status_desligado_desativa_beneficios(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);
        $colab = Colaborador::query()->create([
            'nome' => 'João',
            'matricula' => '100',
            'status' => 'ativo',
            'data_admissao' => '2026-01-01',
            'centro_custo' => 'CC-A',
            'cargo' => 'Auxiliar',
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'Café', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'beneficio_ativo' => true,
        ]);

        $this->actingAs($user)->put(route('rh.efetivo.update', $colab), [
            'nome' => 'João',
            'matricula' => '100',
            'status' => 'desligado',
            'data_demissao' => '2026-05-15',
            'data_admissao' => '2026-01-01',
            'centro_custo' => 'CC-A',
            'cargo' => 'Auxiliar',
        ])->assertRedirect();

        $vinculo->refresh();
        $this->assertFalse($vinculo->tem_direito);
        $this->assertFalse($vinculo->beneficio_ativo);
    }

    public function test_afastamento_nao_desativa_beneficios(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Maria',
            'status' => 'ativo',
        ]);
        $beneficio = Beneficio::query()->create(['nome' => 'Vale', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'colaborador_id' => $colab->id,
            'beneficio_id' => $beneficio->id,
            'tem_direito' => true,
            'beneficio_ativo' => true,
        ]);

        app(ColaboradorMovimentacaoService::class)->registrar($colab, [
            'tipo' => ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            'data_inicio' => today()->toDateString(),
            'especie_beneficio_inss' => 'auxilio_doenca',
            'cid' => 'M54.5',
        ], $user->id);

        $vinculo->refresh();
        $this->assertTrue($vinculo->tem_direito);
        $this->assertTrue($vinculo->beneficio_ativo);
    }
}
