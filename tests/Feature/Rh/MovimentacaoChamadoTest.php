<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\RecrutamentoVaga;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\User;
use App\Services\Rh\MovimentacaoChamadoService;
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
            'data_prevista' => '2026-05-15',
            'ultimo_dia_trabalhado' => '2026-05-14',
            'tipo_rescisao' => 'pedido_demissao',
            'motivo_texto' => 'Teste chamado',
            'gestor_responsavel' => 'Gestor Teste',
            'havera_substituicao_vaga' => 'nao',
        ])->assertRedirect();

        $this->assertSame('ativo', $colab->fresh()->status);

        $chamado = RhMovimentacaoChamado::query()->first();
        $this->assertNotNull($chamado);
        $this->assertSame(MovimentacaoChamadoStatus::ABERTO, $chamado->status);
        $this->assertSame(11, $chamado->etapas()->count());
        $this->assertNotNull($chamado->nadaConsta);
        $this->assertGreaterThan(10, $chamado->nadaConsta->itens()->count());
        $this->assertStringStartsWith('MOV-RH-', $chamado->protocolo);
    }

    public function test_desligamento_bloqueia_etapa_sigo_sem_cadastro(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create(['nome' => 'Teste', 'status' => 'ativo']);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), $this->payloadDesligamento($colab->id));

        $chamado = RhMovimentacaoChamado::query()->with('etapas')->firstOrFail();
        $etapaSigo = $chamado->etapas->firstWhere('slug', 'cadastro_sigo');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(MovimentacaoChamadoService::class)->concluirEtapa($etapaSigo, null, $user->id);
    }

    public function test_desligamento_preenche_gestor_do_contrato_do_colaborador(): void
    {
        $user = User::factory()->create();
        Contrato::query()->create([
            'numero' => '286',
            'centro_custo' => 'CC-286',
            'nome' => 'Contrato 286',
            'gestor' => 'Maria Gestora',
            'status' => 'Ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Com Gestor',
            'status' => 'ativo',
            'centro_custo' => '286',
        ]);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), array_merge(
            $this->payloadDesligamento($colab->id),
            ['gestor_responsavel' => '']
        ))->assertRedirect();

        $chamado = RhMovimentacaoChamado::query()->firstOrFail();
        $this->assertSame('Maria Gestora', $chamado->dados_depois_json['gestor_responsavel']);
    }

    public function test_desligamento_com_substituicao_cria_vaga_no_contrato_do_colaborador(): void
    {
        $user = User::factory()->create();
        $contrato =         Contrato::query()->create([
            'numero' => '286',
            'centro_custo' => 'CC-286',
            'nome' => 'Contrato 286',
            'gestor' => 'Gestor Contrato',
            'status' => 'Ativo',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Desligado Substituição',
            'status' => 'ativo',
            'cargo' => 'Analista RH',
            'centro_custo' => $contrato->numero,
        ]);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), $this->payloadDesligamento($colab->id))
            ->assertRedirect();

        $chamado = RhMovimentacaoChamado::query()->firstOrFail();
        $vaga = RecrutamentoVaga::query()->first();

        $this->assertNotNull($vaga);
        $this->assertSame('286', $vaga->contrato);
        $this->assertSame('Substituição', $vaga->tipo);
        $this->assertSame($chamado->id, $vaga->form_state['origem_desligamento_chamado_id']);
        $this->assertSame($vaga->id, $chamado->dados_depois_json['recrutamento_vaga_id']);
        $this->assertSame($contrato->id, $vaga->form_state['origem_desligamento_contrato_id']);
    }

    public function test_desligamento_nao_finaliza_sem_anexos_obrigatorios(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create(['nome' => 'Teste', 'status' => 'ativo']);
        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), $this->payloadDesligamento($colab->id));

        $chamado = RhMovimentacaoChamado::query()->with('etapas')->firstOrFail();
        $pendencias = app(\App\Services\Rh\MovimentacaoWorkflowService::class)->podeFinalizar($chamado);

        $this->assertNotEmpty($pendencias);
        $this->assertTrue(collect($pendencias)->contains(fn ($p) => str_contains($p, 'Anexo obrigatório') || str_contains($p, 'SIGO')));
    }

    /** @return array<string, mixed> */
    private function payloadDesligamento(int $colaboradorId): array
    {
        return [
            'colaborador_id' => $colaboradorId,
            'tipo' => MovimentacaoChamadoTipo::DESLIGAMENTO,
            'data_prevista' => '2026-05-15',
            'ultimo_dia_trabalhado' => '2026-05-14',
            'tipo_rescisao' => 'pedido_demissao',
            'motivo_texto' => 'Pedido do colaborador',
            'gestor_responsavel' => 'Gestor',
            'havera_substituicao_vaga' => 'sim',
        ];
    }

    public function test_abrir_chamado_afastamento_inss_cria_onze_etapas_e_atestado(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create(['nome' => 'Afastado', 'status' => 'ativo', 'matricula' => '1001']);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), [
            'colaborador_id' => $colab->id,
            'tipo' => MovimentacaoChamadoTipo::AFASTAMENTO_INSS,
            'data_recebimento_atestado' => '2026-05-01',
            'data_inicio_afastamento' => '2026-05-02',
            'data_final_atestado' => '2026-05-20',
            'quantidade_dias' => 19,
            'tipo_afastamento' => 'doenca_comum',
            'doenca_comum' => '1',
            'atestado_medico' => \Illuminate\Http\UploadedFile::fake()->create('atestado.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $chamado = RhMovimentacaoChamado::query()->where('tipo', MovimentacaoChamadoTipo::AFASTAMENTO_INSS)->first();
        $this->assertNotNull($chamado);
        $this->assertSame(MovimentacaoChamadoStatus::ATESTADO_RECEBIDO, $chamado->status);
        $this->assertGreaterThanOrEqual(11, $chamado->etapas()->count());
        $this->assertSame('ativo', $colab->fresh()->status);
        $this->assertTrue($chamado->anexos()->where('tipo_documento', 'atestado_medico')->exists());
        $depois = $chamado->dados_depois_json;
        $this->assertSame(19, (int) ($depois['quantidade_dias'] ?? 0));
    }
}
