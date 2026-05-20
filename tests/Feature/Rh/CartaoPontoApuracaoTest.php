<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\User;
use App\Support\FrequenciaCalculo;
use App\Support\Rh\CartaoPontoService;
use App\Support\Rh\FrequenciaRegistroReconciliacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoPontoApuracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_minutos_trabalhados_com_data_string(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Teste',
            'matricula' => '1',
            'status' => 'ativo',
        ]);

        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:31:00',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);

        $registro = $registro->fresh();
        $this->assertGreaterThan(500, FrequenciaCalculo::minutosTrabalhados($registro));
    }

    public function test_apuracao_soma_total_trabalhado_e_nao_infla_horas_falta(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'Comercial',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
        ]);

        $colab = Colaborador::query()->create([
            'nome' => 'Ana',
            'matricula' => '10',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);

        $cartao = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-04-06',
            '2026-04-06'
        )[0];

        $linha = $cartao['linhas'][0];
        $this->assertNotSame('', $linha['total_trabalhado']);
        $this->assertSame('', $linha['horas_falta']);
        $this->assertNotSame('', $cartao['totais']['trabalhado']);
        $this->assertSame('', $cartao['totais']['horas_falta']);
    }

    public function test_intervalo_da_grade_sem_entrada_saida_nao_gera_nove_horas_falta(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'Motoristas',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
        ]);

        $colab = Colaborador::query()->create([
            'nome' => 'Grade',
            'matricula' => '11',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'entrada_1' => null,
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => null,
            'status' => 'presente',
            'origem' => 'grade',
        ]);

        $cartao = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-04-06',
            '2026-04-06'
        )[0];

        $this->assertSame('08:00', $cartao['linhas'][0]['horas_falta']);
    }

    public function test_nao_dobra_desconto_somando_falta_e_atraso(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'CT',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);
        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Atraso 20',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'status' => 'presente',
            'entrada_1' => '08:20:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
            'origem' => 'csv_ponto',
        ]);

        $linha = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-04-06',
            '2026-04-06'
        )[0]['linhas'][0];

        $this->assertSame('00:20', $linha['horas_atraso']);
        $this->assertSame('00:20', $linha['horas_falta']);
        $this->assertSame('00:20', $linha['falta_atraso']);
        $this->assertSame(20, $linha['minutos_falta_atraso']);
        $this->assertNotSame('00:40', $linha['falta_atraso']);
        $this->assertSame('', $linha['dia_falta']);
    }

    public function test_tolerancia_nao_marca_falta_em_diferenca_de_minutos(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'CT',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colab = Colaborador::query()->create([
            'nome' => 'Jarbas Teste',
            'matricula' => '022214',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'entrada_1' => '07:31:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);

        $cartao = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-04-06',
            '2026-04-06'
        )[0];

        $linha = $cartao['linhas'][0];
        $this->assertSame('', $linha['horas_falta']);
        $this->assertSame('', $linha['falta_atraso']);
        $this->assertSame('', $linha['dia_falta']);
        $this->assertTrue($linha['apurado']);
    }

    public function test_apuracao_nao_marca_falta_antes_da_admissao(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'CT 286',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        foreach ([1, 2, 3, 4, 5] as $diaSemana) {
            HorarioEscalaDia::query()->create([
                'horario_escala_id' => $escala->id,
                'dia_semana' => $diaSemana,
                'entrada_1' => '07:30:00',
                'saida_1' => '12:00:00',
                'entrada_2' => '13:00:00',
                'saida_2' => '17:30:00',
            ]);
        }

        $colab = Colaborador::query()->create([
            'nome' => 'Samuel',
            'matricula' => '022479',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
            'data_admissao' => '2026-04-07',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-23',
            'status' => 'falta',
            'origem' => 'csv_ponto',
        ]);

        $cartao = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-03-21',
            '2026-03-24'
        )[0];

        $linha23 = collect($cartao['linhas'])->firstWhere('data_ymd', '2026-03-23');
        $this->assertNotNull($linha23);
        $this->assertTrue($linha23['fora_vinculo'] ?? false);
        $this->assertSame('', $linha23['dia_falta']);
        $this->assertSame('Antes da admissão', $linha23['entrada_1']);
        $this->assertSame('', $cartao['totais']['dia_falta']);
    }

    public function test_rota_apuracao_nao_exibe_dia_falta_antes_admissao(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Samuel Web',
            'matricula' => '022480',
            'status' => 'ativo',
            'data_admissao' => '2026-04-07',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-03-23',
            'status' => 'falta',
            'origem' => 'csv_ponto',
        ]);

        app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
            '2026-03-21',
            '2026-04-20',
            $colab->id
        );

        $this->actingAs($user)
            ->get(route('rh.frequencia.apuracao.index', [
                'colaborador_id' => $colab->id,
                'data_inicio' => '2026-03-21',
                'data_fim' => '2026-03-27',
            ]))
            ->assertOk()
            ->assertSee('Antes da admiss', false)
            ->assertDontSee('text-red-600 font-semibold">1</', false);
    }

    public function test_dia_sem_batida_com_jornada_conta_horas_falta_da_escala(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'CT',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);
        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Sem batida',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);
        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'status' => 'falta',
            'origem' => 'grade',
        ]);

        $linha = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            '2026-04-06',
            '2026-04-06'
        )[0]['linhas'][0];

        $this->assertSame('1', $linha['dia_falta']);
        $this->assertSame('08:00', $linha['horas_falta']);
        $this->assertFalse($linha['apurado']);
    }

    public function test_resumo_para_apuracao_iguala_cartao_com_quatro_batidas(): void
    {
        $escala = HorarioEscala::query()->create([
            'nome' => 'Motorista',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);
        HorarioEscalaDia::query()->create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '04:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);
        $colab = Colaborador::query()->create([
            'nome' => 'Motorista',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);
        $registro = FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'status' => 'presente',
            'entrada_1' => '07:31:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '20:30:00',
            'origem' => 'csv_ponto',
        ]);
        $registro->load('colaborador.horarioEscala.dias');

        $linha = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab]),
            '2026-04-06',
            '2026-04-06'
        )[0]['linhas'][0];

        $this->assertSame('00:31', $linha['horas_falta']);
        $this->assertSame('00:31', $linha['falta_atraso']);
        $this->assertSame('03:31', $linha['horas_atraso']);
        $this->assertSame('', $linha['dia_falta']);
        $this->assertSame('11:59', $linha['total_trabalhado']);
        $this->assertSame('12:30', $linha['horas_previstas']);
    }

    public function test_rota_apuracao_exibe_totais_coerentes(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'Web',
            'matricula' => '20',
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-04-06',
            'entrada_1' => '08:00:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:00:00',
            'status' => 'presente',
            'origem' => 'csv_ponto',
        ]);

        $this->actingAs($user)
            ->get(route('rh.frequencia.apuracao.index', [
                'colaborador_id' => $colab->id,
                'data_inicio' => '2026-04-06',
                'data_fim' => '2026-04-06',
            ]))
            ->assertOk()
            ->assertSee('08:00', false)
            ->assertDontSee('97:09', false);
    }
}
