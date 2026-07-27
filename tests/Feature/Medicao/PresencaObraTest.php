<?php

namespace Tests\Feature\Medicao;

use App\Models\Colaborador;
use App\Models\MedicaoPresencaObraRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresencaObraTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloqueia_login_sem_acesso_liberado(): void
    {
        Colaborador::query()->create([
            'nome' => 'Supervisor Sem Acesso',
            'matricula' => 'SUP-1',
            'cpf' => '123.456.789-09',
            'status' => 'ativo',
            'presenca_obra_liberado' => false,
        ]);

        $this->post(route('presenca-obra.identificar.store'), [
            'matricula' => '1',
            'cpf' => '12345678909',
        ])->assertSessionHasErrors('identificacao');
    }

    public function test_confirma_presenca_e_consulta_no_medicao(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor Liberado',
            'matricula' => 'SUP-10',
            'cpf' => '111.444.777-35',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
            'centro_custo' => '286',
        ]);

        $operario = Colaborador::query()->create([
            'nome' => 'JOSE EDVIVALDO',
            'matricula' => 'OP-20',
            'cpf' => '222.333.444-55',
            'status' => 'ativo',
            'centro_custo' => '286',
        ]);

        $this->post(route('presenca-obra.identificar.store'), [
            'matricula' => '10',
            'cpf' => '11144477735',
        ])->assertRedirect(route('presenca-obra.index'));

        $this->post(route('presenca-obra.salvar'), [
            'data' => '2026-07-27',
            'itens' => [
                $operario->id => ['status' => 'presente'],
                $supervisor->id => ['status' => 'ausente'],
            ],
        ])->assertRedirect();

        $this->assertTrue(
            MedicaoPresencaObraRegistro::query()
                ->where('colaborador_id', $operario->id)
                ->where('status', 'presente')
                ->where('confirmado_por_id', $supervisor->id)
                ->whereDate('data', '2026-07-27')
                ->exists()
        );

        $user = User::factory()->create([
            'todos_contratos' => true,
        ]);
        $this->actingAs($user)
            ->get(route('medicao.presenca-obra.consulta', ['data' => '2026-07-27']))
            ->assertOk()
            ->assertSee('JOSE EDVIVALDO', false)
            ->assertSee('Presente', false)
            ->assertSee('Supervisor Liberado', false);

        $this->assertSame(2, MedicaoPresencaObraRegistro::query()->whereDate('data', '2026-07-27')->count());
    }

    public function test_nao_grava_em_frequencia_registros(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor',
            'matricula' => 'S9',
            'cpf' => '390.533.447-05',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
        ]);
        $op = Colaborador::query()->create([
            'nome' => 'Operario',
            'matricula' => 'O9',
            'status' => 'ativo',
        ]);

        $this->withSession(['presenca_obra_colaborador_id' => $supervisor->id])
            ->post(route('presenca-obra.salvar'), [
                'data' => now()->toDateString(),
                'itens' => [
                    $op->id => ['status' => 'presente'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('frequencia_registros', 0);
        $this->assertDatabaseCount('medicao_presenca_obra_registros', 1);
    }

    public function test_exporta_folha_excel_com_p_e_f(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor Export',
            'matricula' => 'EXP-1',
            'cpf' => '390.533.447-05',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
            'centro_custo' => '286',
        ]);

        $operario = Colaborador::query()->create([
            'nome' => 'OPERARIO EXPORT',
            'matricula' => 'EXP-2',
            'cpf' => '222.333.444-55',
            'status' => 'ativo',
            'centro_custo' => '286',
        ]);

        $this->withSession(['presenca_obra_colaborador_id' => $supervisor->id])
            ->post(route('presenca-obra.salvar'), [
                'data' => '2026-07-27',
                'itens' => [
                    $operario->id => ['status' => 'presente'],
                    $supervisor->id => ['status' => 'ausente'],
                ],
            ])
            ->assertRedirect();

        $user = User::factory()->create(['todos_contratos' => true]);

        $response = $this->actingAs($user)->get(route('medicao.presenca-obra.exportar-excel', [
            'data_inicio' => '2026-07-27',
            'data_fim' => '2026-07-27',
            'centro_custo' => '286',
        ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString(
            'presenca-obra-20260727-20260727.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_salvar_retorna_json_para_sincronizacao_offline(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor Sync',
            'matricula' => 'SYN-1',
            'cpf' => '390.533.447-05',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
        ]);

        $operario = Colaborador::query()->create([
            'nome' => 'Operario Sync',
            'matricula' => 'SYN-2',
            'status' => 'ativo',
        ]);

        $this->withSession(['presenca_obra_colaborador_id' => $supervisor->id])
            ->postJson(route('presenca-obra.salvar'), [
                'data' => '2026-07-28',
                'itens' => [
                    (string) $operario->id => ['status' => 'presente'],
                ],
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'salvos' => 1,
            ]);

        $this->assertDatabaseHas('medicao_presenca_obra_registros', [
            'colaborador_id' => $operario->id,
            'status' => 'presente',
            'confirmado_por_id' => $supervisor->id,
        ]);
    }

    public function test_tela_confirmacao_inclui_suporte_offline(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor Offline',
            'matricula' => 'OFF-1',
            'cpf' => '390.533.447-05',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
        ]);

        $this->withSession(['presenca_obra_colaborador_id' => $supervisor->id])
            ->get(route('presenca-obra.index'))
            ->assertOk()
            ->assertSee('Registros pendentes de envio', false)
            ->assertSee('Enviar registros pendentes', false)
            ->assertSee('PresencaObraOffline', false);
    }

    public function test_identificar_retorna_json_com_cache_para_offline(): void
    {
        $supervisor = Colaborador::query()->create([
            'nome' => 'Supervisor Offline',
            'matricula' => 'OFF-1',
            'cpf' => '390.533.447-05',
            'status' => 'ativo',
            'presenca_obra_liberado' => true,
        ]);

        Colaborador::query()->create([
            'nome' => 'Operario Offline',
            'matricula' => 'OFF-2',
            'status' => 'ativo',
        ]);

        $this->postJson(route('presenca-obra.identificar.store'), [
            'matricula' => 'OFF-1',
            'cpf' => '39053344705',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('confirmador.id', $supervisor->id)
            ->assertJsonStructure([
                'cache' => ['colaboradores', 'centros_custo', 'confirmador'],
            ]);
    }

    public function test_modo_offline_abre_sem_sessao_servidor(): void
    {
        $this->get(route('presenca-obra.modo-offline'))
            ->assertOk()
            ->assertSee('Sem internet', false)
            ->assertSee('PresencaObraStore', false);
    }

    public function test_portal_publico_abre_sem_login_admin(): void
    {
        $this->get(route('medicao.presenca-obra.index'))
            ->assertOk()
            ->assertSee('Consulta de confirmações', false)
            ->assertSee('Confirmar presença (supervisor)', false)
            ->assertSee('Entrar para confirmar', false)
            ->assertSee('Filtrar', false);
    }

    public function test_consulta_exige_login_admin(): void
    {
        $this->get(route('medicao.presenca-obra.consulta'))
            ->assertRedirect(route('login'));
    }

    public function test_rejeita_periodo_maior_que_62_dias(): void
    {
        $user = User::factory()->create(['todos_contratos' => true]);

        $this->actingAs($user)
            ->from(route('medicao.presenca-obra.consulta'))
            ->get(route('medicao.presenca-obra.exportar-excel', [
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-04-01',
            ]))
            ->assertRedirect(route('medicao.presenca-obra.consulta'))
            ->assertSessionHasErrors('data_fim');
    }
}
