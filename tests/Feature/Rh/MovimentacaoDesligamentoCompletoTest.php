<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use App\Models\User;
use App\Services\Rh\MovimentacaoChamadoService;
use App\Services\Rh\MovimentacaoDesligamentoAutoProgressaoService;
use App\Services\Rh\MovimentacaoDesligamentoChecklistAutoService;
use App\Services\Rh\MovimentacaoDesligamentoRules;
use App\Services\Rh\MovimentacaoNadaConstaService;
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

    public function test_toggle_checklist_via_json_sem_recarregar_pagina(): void
    {
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);
        $etapa = $chamado->etapas->firstWhere('slug', 'triagem_rh');
        $item = $etapa?->checklistItens->first(fn ($i) => $i->status !== 'concluido');
        $this->assertNotNull($item);

        $this->actingAs($user)
            ->postJson(route('rh.chamados-movimentacao.checklist.toggle', $item))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('item_id', $item->id);

        $this->assertSame('concluido', $item->fresh()->status);
    }

    public function test_checklist_solicitacao_marca_automatico_com_dados_do_chamado(): void
    {
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        app(MovimentacaoDesligamentoChecklistAutoService::class)->sincronizar($chamado->fresh(['etapas.checklistItens', 'colaborador']), $user->id);

        $etapa = $chamado->fresh('etapas.checklistItens')->etapas->firstWhere('slug', 'solicitacao');
        $porSlug = $etapa->checklistItens->pluck('status', 'slug');

        $this->assertSame('concluido', $porSlug['dados-do-colaborador-contrato-e-funcao-conferidos']);
        $this->assertSame('concluido', $porSlug['data-prevista-e-ultimo-dia-trabalhado-informados']);
        $this->assertSame('concluido', $porSlug['tipo-de-rescisao-e-motivo-registrados']);
        $this->assertSame('concluido', $porSlug['gestor-e-substituicao-de-vaga-informados']);
    }

    public function test_checklist_cadastro_sigo_marca_automatico_com_sigo_e_pacote(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        $depois = $chamado->dados_depois_json ?? [];
        $depois['sigo'] = [
            'cadastrado' => true,
            'data_cadastro' => '2026-05-11',
            'responsavel_cadastro' => 'RH Teste',
        ];
        $chamado->update(['dados_depois_json' => $depois]);

        $path = 'rh/chamados-movimentacao/'.$chamado->id.'/pacote.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');
        $chamado->anexos()->create([
            'nome_arquivo' => 'pacote.pdf',
            'caminho' => $path,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            'obrigatorio' => true,
        ]);

        app(MovimentacaoDesligamentoChecklistAutoService::class)
            ->sincronizar($chamado->fresh(['etapas.checklistItens', 'anexos']), $user->id);

        $etapa = $chamado->fresh('etapas.checklistItens')->etapas->firstWhere('slug', 'cadastro_sigo');
        $porSlug = $etapa->checklistItens->pluck('status', 'slug');

        $this->assertSame('concluido', $porSlug['desligamento-cadastrado-no-sigo']);
        $this->assertSame('concluido', $porSlug['folha-de-ponto-anexada']);
        $this->assertSame('concluido', $porSlug['documento-do-desligamento-anexado']);
        $this->assertSame('concluido', $porSlug['anexos-obrigatorios-por-tipo-de-rescisao-conferidos']);
    }

    public function test_auto_progressao_conclui_solicitacao_e_cadastro_sigo_com_pacote(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        $depois = $chamado->dados_depois_json ?? [];
        $depois['sigo'] = [
            'cadastrado' => true,
            'data_cadastro' => '2026-05-11',
            'responsavel_cadastro' => 'RH Teste',
        ];
        $chamado->update(['dados_depois_json' => $depois]);

        $path = 'rh/chamados-movimentacao/'.$chamado->id.'/pacote.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');
        $chamado->anexos()->create([
            'nome_arquivo' => 'pacote.pdf',
            'caminho' => $path,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            'obrigatorio' => true,
        ]);

        app(MovimentacaoDesligamentoAutoProgressaoService::class)
            ->sincronizar($chamado->fresh(['etapas', 'anexos', 'nadaConsta.itens']), $user->id);

        $slugs = $chamado->fresh('etapas')->etapas->filter(fn ($e) => $e->isConcluida())->pluck('slug')->all();

        $this->assertContains('solicitacao', $slugs);
        $this->assertContains('cadastro_sigo', $slugs);
        $this->assertNotContains('nada_consta', $slugs);
    }

    public function test_sincronizar_remove_itens_fora_do_catalogo(): void
    {
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);
        $nada = $chamado->nadaConsta;

        RhMovimentacaoNadaConstaItem::query()->create([
            'nada_consta_id' => $nada->id,
            'area' => 'rh',
            'item' => 'cracha_funcional',
            'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
        ]);
        RhMovimentacaoNadaConstaItem::query()->create([
            'nada_consta_id' => $nada->id,
            'area' => 'financeiro',
            'item' => 'despesas_prestacao',
            'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
        ]);

        app(MovimentacaoNadaConstaService::class)->sincronizarItensComCatalogo($nada->fresh('itens'));

        $slugs = $nada->fresh('itens')->itens->pluck('item')->all();
        $areas = $nada->fresh('itens')->itens->pluck('area')->unique()->all();

        $this->assertNotContains('cracha_funcional', $slugs);
        $this->assertNotContains('emprestimo_consignado', $slugs);
        $this->assertNotContains('webcard_adiantamentos', $slugs);
        $this->assertNotContains('adiantamentos', $slugs);
        $this->assertNotContains('despesas_prestacao', $slugs);
        $this->assertNotContains('financeiro', $areas);
    }

    public function test_pacote_documentos_dispensa_conferencia_item_a_item_do_nada_consta(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        $path = 'rh/chamados-movimentacao/'.$chamado->id.'/pacote.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');
        $chamado->anexos()->create([
            'nome_arquivo' => 'pacote.pdf',
            'caminho' => $path,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            'obrigatorio' => true,
        ]);

        $pendencias = app(MovimentacaoDesligamentoRules::class)->pendenciasNadaConsta($chamado->fresh(['anexos', 'nadaConsta.itens']));

        $this->assertNotEmpty($pendencias);
        $this->assertTrue(
            collect($pendencias)->contains(fn (string $p) => str_contains($p, 'Valide o Nada Consta')),
            'Deve orientar validação RH quando há pacote e ainda não validado.',
        );
        $this->assertFalse(
            collect($pendencias)->contains(fn (string $p) => str_contains($p, 'conferir item')),
            'Com pacote único não exige conferência item a item.',
        );
    }

    public function test_validar_nada_consta_rh_com_pacote_documentos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $chamado = $this->criarChamadoDesligamento($user);

        $path = 'rh/chamados-movimentacao/'.$chamado->id.'/pacote.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');
        $chamado->anexos()->create([
            'nome_arquivo' => 'pacote.pdf',
            'caminho' => $path,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            'obrigatorio' => true,
        ]);

        $this->actingAs($user)
            ->post(route('rh.chamados-movimentacao.nada-consta.validar-rh', $chamado))
            ->assertRedirect(route('rh.chamados-movimentacao.show', $chamado))
            ->assertSessionHas('success');

        $chamado->load('nadaConsta');
        $this->assertTrue($chamado->nadaConsta->validado_rh);
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

        $tipoPacote = MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS;
        $path = 'rh/chamados-movimentacao/'.$chamado->id.'/pacote.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 fake');
        $chamado->anexos()->create([
            'nome_arquivo' => 'pacote-documentos.pdf',
            'caminho' => $path,
            'tipo_documento' => $tipoPacote,
            'obrigatorio' => true,
        ]);

        $nada = $chamado->nadaConsta;
        foreach ($nada->itens as $item) {
            $item->update([
                'tem_debito' => false,
                'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
            ]);
        }
        $nada->update([
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
