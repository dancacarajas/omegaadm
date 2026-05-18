<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Support\PontoColaboradorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PontoColaboradorMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_identificacao_com_matricula_e_cpf(): void
    {
        Colaborador::query()->create([
            'nome' => 'João Motorista',
            'matricula' => 'MOT-100',
            'cpf' => '123.456.789-09',
            'status' => 'ativo',
        ]);

        $response = $this->post(route('ponto.identificar.store'), [
            'matricula' => '100',
            'cpf' => '12345678909',
        ]);

        $response->assertRedirect(route('ponto.index'));
        $this->assertNotNull(session('ponto_colaborador_id'));
    }

    public function test_registra_hora_no_fuso_da_aplicacao(): void
    {
        config(['app.timezone' => 'America/Sao_Paulo']);

        $colaborador = Colaborador::query()->create([
            'nome' => 'José',
            'matricula' => '22281',
            'cpf' => '00000000000',
            'status' => 'ativo',
        ]);

        $this->travelTo(Carbon::parse('2026-05-18 12:36:05', 'America/Sao_Paulo'));

        $this->withSession(['ponto_colaborador_id' => $colaborador->id])
            ->post(route('ponto.registrar'))
            ->assertRedirect(route('ponto.index'))
            ->assertSessionHas('success');

        $registro = FrequenciaRegistro::query()->where('colaborador_id', $colaborador->id)->first();
        $this->assertSame('12:36:05', $registro->entrada_1);

        $this->travelBack();
    }

    public function test_registra_batidas_em_sequencia(): void
    {
        $colaborador = Colaborador::query()->create([
            'nome' => 'Maria',
            'matricula' => '200',
            'cpf' => '98765432100',
            'status' => 'ativo',
        ]);

        $this->withSession(['ponto_colaborador_id' => $colaborador->id])
            ->post(route('ponto.registrar'))
            ->assertRedirect(route('ponto.index'));

        $registro = FrequenciaRegistro::query()->where('colaborador_id', $colaborador->id)->first();
        $this->assertNotNull($registro->entrada_1);
        $this->assertSame('incompleto', $registro->status);
        $this->assertSame(PontoColaboradorService::ORIGEM, $registro->origem);
    }

    public function test_bloqueia_ponto_em_dia_de_folga_rotativa_semanal(): void
    {
        $segunda = now()->startOfWeek();
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'rotativa_semanal',
            'ciclo_dias' => 14,
            'data_inicio_ciclo' => $segunda->toDateString(),
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '08:00:00',
            'saida_1' => '17:00:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'Pedro',
            'matricula' => '300',
            'cpf' => '11122233344',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 0,
            'status' => 'ativo',
        ]);

        $terca = $segunda->copy()->addDay();
        $this->assertFalse(app(\App\Support\EscalaPontoRegras::class)->deveTrabalharNoDia($colaborador, $terca));

        $this->travelTo($terca);

        $this->withSession(['ponto_colaborador_id' => $colaborador->id])
            ->post(route('ponto.registrar'))
            ->assertRedirect(route('ponto.index'))
            ->assertSessionHasErrors('ponto');

        $this->travelBack();
    }

    public function test_intervalo_automatico_conforme_escala_ao_registrar_entrada(): void
    {
        config(['app.timezone' => 'America/Sao_Paulo']);

        $segunda = Carbon::parse('2026-05-18', 'America/Sao_Paulo');
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'semanal',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'José',
            'matricula' => '22281',
            'cpf' => '00000000000',
            'horario_escala_id' => $escala->id,
            'status' => 'ativo',
        ]);

        $this->travelTo($segunda->copy()->setTime(8, 15, 0));

        $this->withSession(['ponto_colaborador_id' => $colaborador->id])
            ->post(route('ponto.registrar'))
            ->assertRedirect(route('ponto.index'))
            ->assertSessionHas('success');

        $registro = FrequenciaRegistro::query()->where('colaborador_id', $colaborador->id)->first();
        $this->assertSame('08:15:00', $registro->entrada_1);
        $this->assertSame('12:00:00', $registro->saida_1);
        $this->assertSame('13:00:00', $registro->entrada_2);
        $this->assertNull($registro->saida_2);

        $this->travelBack();
    }

    public function test_sincroniza_intervalo_em_registro_ja_com_entrada(): void
    {
        $segunda = Carbon::parse('2026-05-18', 'America/Sao_Paulo');
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas',
            'tipo' => 'rotativa_semanal',
            'ciclo_dias' => 14,
            'data_inicio_ciclo' => '2026-04-20',
            'status' => 'ativo',
        ]);

        HorarioEscalaDia::create([
            'horario_escala_id' => $escala->id,
            'dia_semana' => 1,
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
        ]);

        $colaborador = Colaborador::query()->create([
            'nome' => 'José',
            'matricula' => '22281',
            'cpf' => '00000000000',
            'horario_escala_id' => $escala->id,
            'horario_escala_ciclo_offset' => 0,
            'status' => 'ativo',
        ]);

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colaborador->id,
            'data' => $segunda->toDateString(),
            'entrada_1' => '12:51:00',
            'status' => 'incompleto',
            'origem' => 'app_colaborador',
        ]);

        $this->travelTo($segunda->copy()->setTime(12, 55, 0));

        $this->withSession(['ponto_colaborador_id' => $colaborador->id])
            ->get(route('ponto.index'))
            ->assertOk()
            ->assertSee('12:00')
            ->assertSee('13:00');

        $registro = FrequenciaRegistro::query()->where('colaborador_id', $colaborador->id)->first();
        $this->assertSame('12:00:00', $registro->saida_1);
        $this->assertSame('13:00:00', $registro->entrada_2);

        $this->travelBack();
    }

    public function test_tela_identificar_renderiza(): void
    {
        $this->get(route('ponto.identificar'))
            ->assertOk()
            ->assertSee('Marcação de ponto');
    }
}
