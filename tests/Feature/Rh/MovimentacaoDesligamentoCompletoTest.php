<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use App\Models\User;
use App\Services\Rh\MovimentacaoChamadoService;
use App\Services\Rh\MovimentacaoFinalizacaoService;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MovimentacaoDesligamentoCompletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_endpoint_retorna_pdf(): void
    {
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        $response = $this->actingAs($user)->get(route('rh.chamados-movimentacao.pdf', $chamado));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_chamado_finalizado_bloqueia_edicao_sigo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);
        $this->preencherMinimoParaFinalizar($chamado, $user);

        app(MovimentacaoFinalizacaoService::class)->finalizar($chamado->fresh(), $user->id);

        $this->actingAs($user)->patch(route('rh.chamados-movimentacao.sigo', $chamado), [
            'cadastrado' => '1',
            'data_cadastro' => '2026-05-01',
            'responsavel_cadastro' => 'Teste',
        ])->assertStatus(422);
    }

    public function test_finalizar_gera_anexo_pdf(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);
        $this->preencherMinimoParaFinalizar($chamado, $user);

        app(MovimentacaoFinalizacaoService::class)->finalizar($chamado->fresh(), $user->id);

        $this->assertTrue(
            $chamado->fresh()->anexos()->where('tipo_documento', MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF)->exists()
        );
        $this->assertSame(MovimentacaoChamadoStatus::CONCLUIDO, $chamado->fresh()->status);
    }

    public function test_anexo_item_nada_consta(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);
        $item = $chamado->nadaConsta->itens->first();

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.nada-consta-item.anexo', $item), [
            'tipo' => 'evidencia',
            'arquivo' => UploadedFile::fake()->create('evidencia.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $item->refresh();
        $this->assertNotNull($item->anexo_evidencia_id);
    }

    private function criarChamadoDesligamento(User $user): RhMovimentacaoChamado
    {
        $colab = Colaborador::query()->create(['nome' => 'Desligado Teste', 'status' => 'ativo', 'matricula' => '9001']);

        $this->actingAs($user)->post(route('rh.chamados-movimentacao.store'), [
            'colaborador_id' => $colab->id,
            'tipo' => MovimentacaoChamadoTipo::DESLIGAMENTO,
            'data_prevista' => '2026-05-20',
            'ultimo_dia_trabalhado' => '2026-05-19',
            'tipo_rescisao' => 'sem_justa_causa',
            'motivo_texto' => 'Reestruturação',
            'gestor_responsavel' => 'Gestor A',
            'havera_substituicao_vaga' => 'nao',
        ]);

        return RhMovimentacaoChamado::query()->with('etapas.checklistItens', 'nadaConsta.itens')->firstOrFail();
    }

    private function preencherMinimoParaFinalizar(RhMovimentacaoChamado $chamado, User $user): void
    {
        $depois = $chamado->dados_depois_json ?? [];
        $depois['sigo'] = [
            'cadastrado' => true,
            'data_cadastro' => '2026-05-10',
            'responsavel_cadastro' => 'RH Teste',
        ];
        $chamado->update(['dados_depois_json' => $depois]);

        $tipos = [
            MovimentacaoDesligamentoCatalog::ANEXO_FOLHA_PONTO,
            MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_ASSINADO,
            MovimentacaoDesligamentoCatalog::ANEXO_DOCUMENTO_DESLIGAMENTO,
        ];
        foreach ($tipos as $tipo) {
            $path = 'rh/chamados-movimentacao/'.$chamado->id.'/'.$tipo.'.pdf';
            Storage::disk('public')->put($path, '%PDF-1.4 fake');
            $chamado->anexos()->create([
                'nome_arquivo' => $tipo.'.pdf',
                'caminho' => $path,
                'tipo_documento' => $tipo,
                'obrigatorio' => true,
            ]);
        }

        $nada = $chamado->nadaConsta;
        foreach ($nada->itens as $item) {
            $item->update([
                'tem_debito' => false,
                'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
            ]);
        }
        $nada->update([
            'assinatura_colaborador' => 'Colaborador Teste',
            'validado_rh' => true,
            'validado_rh_em' => now(),
            'status' => MovimentacaoDesligamentoCatalog::NC_STATUS_VALIDADO_RH,
        ]);

        $service = app(MovimentacaoChamadoService::class);
        foreach ($chamado->fresh('etapas.checklistItens')->etapas as $etapa) {
            foreach ($etapa->checklistItens as $check) {
                if ($check->status !== 'concluido') {
                    $service->alternarChecklist($check, $user->id);
                }
            }
            $service->concluirEtapa($etapa, 'ok teste', $user->id);
        }
    }
}
