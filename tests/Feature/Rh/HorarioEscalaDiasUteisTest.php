<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\User;
use App\Support\EscalaPontoRegras;
use App\Support\FrequenciaCalculo;
use App\Support\HorarioEscalaDiasUteis;
use App\Support\Rh\CartaoPontoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioEscalaDiasUteisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: HorarioEscala, 1: array<int, Colaborador>}
     */
    private function escalaComQuatroPosicoes(string $segundaInicio = '2026-05-04'): array
    {
        $escala = HorarioEscala::create([
            'nome' => 'Motoristas dias úteis',
            'tipo' => 'rotativa_dias_uteis',
            'ciclo_dias' => 4,
            'data_inicio_ciclo' => $segundaInicio,
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

        $nomes = [
            0 => 'JOSE EDVIVALDO',
            1 => 'RAFAEL',
            2 => 'FRANCISCO',
            3 => 'JOSUE',
        ];

        $colaboradores = [];
        foreach ($nomes as $offset => $nome) {
            $colaboradores[$offset] = Colaborador::query()->create([
                'nome' => $nome,
                'matricula' => 'M'.$offset,
                'horario_escala_id' => $escala->id,
                'horario_escala_ciclo_offset' => $offset,
                'status' => 'ativo',
            ]);
        }

        return [$escala, $colaboradores];
    }

    public function test_matriz_quatro_semanas_exemplo_obrigatorio(): void
    {
        [$escala] = $this->escalaComQuatroPosicoes('2026-05-04');

        $esperado = [
            // Semana 1
            '2026-05-04' => 0, // JOSE
            '2026-05-05' => 1, // RAFAEL
            '2026-05-06' => 2, // FRANCISCO
            '2026-05-07' => 3, // JOSUE
            '2026-05-08' => 0, // JOSE
            // Semana 2
            '2026-05-11' => 1, // RAFAEL
            '2026-05-12' => 2, // FRANCISCO
            '2026-05-13' => 3, // JOSUE
            '2026-05-14' => 0, // JOSE
            '2026-05-15' => 1, // RAFAEL
            // Semana 3
            '2026-05-18' => 2, // FRANCISCO
            '2026-05-19' => 3, // JOSUE
            '2026-05-20' => 0, // JOSE
            '2026-05-21' => 1, // RAFAEL
            '2026-05-22' => 2, // FRANCISCO
            // Semana 4
            '2026-05-25' => 3, // JOSUE
            '2026-05-26' => 0, // JOSE
            '2026-05-27' => 1, // RAFAEL
            '2026-05-28' => 2, // FRANCISCO
            '2026-05-29' => 3, // JOSUE
        ];

        foreach ($esperado as $data => $posicao) {
            $this->assertSame(
                $posicao,
                HorarioEscalaDiasUteis::posicaoNaData($escala, $data),
                "Posição incorreta em {$data}"
            );

            for ($p = 0; $p < 4; $p++) {
                $this->assertSame(
                    $p === $posicao,
                    HorarioEscalaDiasUteis::trabalhaNoDia($escala, $data, $p),
                    "trabalhaNoDia falhou em {$data} posição {$p}"
                );
            }
        }
    }

    public function test_totais_cinco_dias_por_posicao_em_vinte_dias_uteis(): void
    {
        [$escala] = $this->escalaComQuatroPosicoes('2026-05-04');
        $contagem = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $dia = Carbon::parse('2026-05-04');

        $uteis = 0;
        while ($uteis < 20) {
            if ((int) $dia->isoWeekday() <= 5) {
                $pos = HorarioEscalaDiasUteis::posicaoNaData($escala, $dia);
                $this->assertNotNull($pos);
                $contagem[$pos]++;
                $uteis++;
            }
            $dia->addDay();
        }

        $this->assertSame([0 => 5, 1 => 5, 2 => 5, 3 => 5], $contagem);
    }

    public function test_fim_de_semana_ninguem_trabalha_e_sequencia_continua(): void
    {
        [$escala] = $this->escalaComQuatroPosicoes('2026-05-04');

        $sabado = Carbon::parse('2026-05-09');
        $domingo = Carbon::parse('2026-05-10');
        $segundaSeguinte = Carbon::parse('2026-05-11');

        $this->assertNull(HorarioEscalaDiasUteis::indiceDiaUtil($escala, $sabado));
        $this->assertNull(HorarioEscalaDiasUteis::posicaoNaData($escala, $domingo));

        for ($p = 0; $p < 4; $p++) {
            $this->assertFalse(HorarioEscalaDiasUteis::trabalhaNoDia($escala, $sabado, $p));
            $this->assertFalse(HorarioEscalaDiasUteis::trabalhaNoDia($escala, $domingo, $p));
        }

        $this->assertSame(5, HorarioEscalaDiasUteis::indiceDiaUtil($escala, $segundaSeguinte));
        $this->assertSame(1, HorarioEscalaDiasUteis::posicaoNaData($escala, $segundaSeguinte));
        $this->assertTrue(HorarioEscalaDiasUteis::trabalhaNoDia($escala, $segundaSeguinte, 1));
    }

    public function test_colaborador_dia_na_data_integra_com_modelo(): void
    {
        [, $cols] = $this->escalaComQuatroPosicoes('2026-05-04');

        $segunda = Carbon::parse('2026-05-04');
        $terca = Carbon::parse('2026-05-05');
        $sabado = Carbon::parse('2026-05-09');

        $this->assertNotNull($cols[0]->horarioEscalaDiaNaData($segunda)?->entrada_1);
        $this->assertNull($cols[0]->horarioEscalaDiaNaData($terca)?->entrada_1);
        $this->assertNotNull($cols[1]->horarioEscalaDiaNaData($terca)?->entrada_1);
        $this->assertNull($cols[0]->horarioEscalaDiaNaData($sabado));
    }

    public function test_pode_cadastrar_e_editar_escala_via_http(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cols = [];
        foreach (['A', 'B', 'C', 'D'] as $i => $letra) {
            $cols[$i] = Colaborador::query()->create([
                'nome' => "Motorista {$letra}",
                'matricula' => "X{$i}",
                'status' => 'ativo',
            ]);
        }

        $payload = [
            'nome' => 'Rodízio dias úteis',
            'tipo' => 'rotativa_dias_uteis',
            'status' => 'ativo',
            'ciclo_dias' => 4,
            'data_inicio_ciclo' => '2026-05-07',
            'dias' => [
                1 => [
                    'entrada_1' => '07:30',
                    'saida_1' => '12:00',
                    'entrada_2' => '13:00',
                    'saida_2' => '17:30',
                    'almoco_livre' => '0',
                    'compensado' => '0',
                    'neutro' => '0',
                    'noturno' => '0',
                ],
            ],
            'escala_colaboradores' => [
                ['colaborador_id' => $cols[0]->id, 'ciclo_offset' => 0],
                ['colaborador_id' => $cols[1]->id, 'ciclo_offset' => 1],
                ['colaborador_id' => $cols[2]->id, 'ciclo_offset' => 2],
                ['colaborador_id' => $cols[3]->id, 'ciclo_offset' => 3],
            ],
        ];

        $response = $this->post(route('rh.horarios.store'), $payload);
        $response->assertRedirect(route('rh.horarios.index'));

        $escala = HorarioEscala::query()->where('nome', 'Rodízio dias úteis')->first();
        $this->assertNotNull($escala);
        $this->assertSame('rotativa_dias_uteis', $escala->tipo);
        $this->assertSame(4, $escala->ciclo_dias);
        $this->assertSame('2026-05-04', $escala->data_inicio_ciclo->toDateString());
        $this->assertSame(1, HorarioEscalaDia::query()->where('horario_escala_id', $escala->id)->count());

        foreach ($cols as $i => $col) {
            $col->refresh();
            $this->assertSame($escala->id, $col->horario_escala_id);
            $this->assertSame($i, (int) $col->horario_escala_ciclo_offset);
        }

        $payload['nome'] = 'Rodízio dias úteis editado';
        $payload['ciclo_dias'] = 4;
        $payload['data_inicio_ciclo'] = '2026-05-04';
        $payload['dias'][1]['entrada_1'] = '08:00';

        $update = $this->put(route('rh.horarios.update', $escala), $payload);
        $update->assertRedirect(route('rh.horarios.edit', $escala));

        $escala->refresh();
        $this->assertSame('Rodízio dias úteis editado', $escala->nome);
        $dia = HorarioEscalaDiasUteis::templateDia($escala);
        $this->assertSame('08:00', substr((string) $dia?->entrada_1, 0, 5));
    }

    public function test_validacao_posicao_duplicada_e_quantidade(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cols = [];
        for ($i = 0; $i < 4; $i++) {
            $cols[$i] = Colaborador::query()->create([
                'nome' => "Colab {$i}",
                'matricula' => "V{$i}",
                'status' => 'ativo',
            ]);
        }

        $base = [
            'nome' => 'Inválida',
            'tipo' => 'rotativa_dias_uteis',
            'status' => 'ativo',
            'ciclo_dias' => 4,
            'data_inicio_ciclo' => '2026-05-04',
            'dias' => [
                1 => [
                    'entrada_1' => '07:30',
                    'saida_1' => '12:00',
                    'entrada_2' => '13:00',
                    'saida_2' => '17:30',
                    'almoco_livre' => '0',
                    'compensado' => '0',
                    'neutro' => '0',
                    'noturno' => '0',
                ],
            ],
        ];

        $this->from(route('rh.horarios.create'))
            ->post(route('rh.horarios.store'), $base + [
                'escala_colaboradores' => [
                    ['colaborador_id' => $cols[0]->id, 'ciclo_offset' => 0],
                    ['colaborador_id' => $cols[1]->id, 'ciclo_offset' => 0],
                    ['colaborador_id' => $cols[2]->id, 'ciclo_offset' => 2],
                    ['colaborador_id' => $cols[3]->id, 'ciclo_offset' => 3],
                ],
            ])
            ->assertSessionHasErrors('escala_colaboradores');

        $this->from(route('rh.horarios.create'))
            ->post(route('rh.horarios.store'), $base + [
                'escala_colaboradores' => [
                    ['colaborador_id' => $cols[0]->id, 'ciclo_offset' => 0],
                    ['colaborador_id' => $cols[1]->id, 'ciclo_offset' => 1],
                ],
            ])
            ->assertSessionHasErrors('escala_colaboradores');

        $this->from(route('rh.horarios.create'))
            ->post(route('rh.horarios.store'), array_merge($base, [
                'ciclo_dias' => 1,
                'escala_colaboradores' => [
                    ['colaborador_id' => $cols[0]->id, 'ciclo_offset' => 0],
                ],
            ]))
            ->assertSessionHasErrors('ciclo_dias');

        $this->from(route('rh.horarios.create'))
            ->post(route('rh.horarios.store'), array_merge($base, [
                'ciclo_dias' => 15,
                'escala_colaboradores' => collect(range(0, 14))->map(fn ($i) => [
                    'colaborador_id' => Colaborador::query()->create([
                        'nome' => "Extra {$i}",
                        'matricula' => "E{$i}",
                        'status' => 'ativo',
                    ])->id,
                    'ciclo_offset' => $i,
                ])->all(),
            ]))
            ->assertSessionHasErrors('ciclo_dias');
    }

    public function test_integracao_frequencia_folga_e_jornada(): void
    {
        [, $cols] = $this->escalaComQuatroPosicoes('2026-05-04');
        $regras = app(EscalaPontoRegras::class);

        $segunda = Carbon::parse('2026-05-04');
        $terca = Carbon::parse('2026-05-05');

        $this->assertTrue($regras->deveTrabalharNoDia($cols[0], $segunda));
        $this->assertFalse($regras->deveTrabalharNoDia($cols[0], $terca));
        $this->assertTrue($regras->diaAbonadoPorFolgaEscala($cols[0], $terca));

        $registroFolga = FrequenciaRegistro::query()->create([
            'colaborador_id' => $cols[0]->id,
            'data' => $terca->toDateString(),
            'status' => 'falta',
            'origem' => 'grade',
        ]);
        $registroFolga->load('colaborador.horarioEscala.dias');
        $resumoFolga = FrequenciaCalculo::resumo($registroFolga);
        $this->assertSame(0, FrequenciaCalculo::jornadaMinutosParaRegistro($registroFolga));
        $this->assertNull($resumoFolga['falta']);
        $this->assertSame('Folga (abonada)', $resumoFolga['falta_fmt']);

        $registroTrabalho = FrequenciaRegistro::query()->create([
            'colaborador_id' => $cols[0]->id,
            'data' => $segunda->toDateString(),
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
            'status' => 'completo',
            'origem' => 'ponto',
        ]);
        $registroTrabalho->load('colaborador.horarioEscala.dias');
        $this->assertGreaterThan(0, FrequenciaCalculo::jornadaMinutosParaRegistro($registroTrabalho));

        $avaliacaoFolga = $regras->avaliarMarcacao($cols[0], $terca, true);
        $this->assertFalse($avaliacaoFolga['permitido']);
        $this->assertSame('folga_escala', $avaliacaoFolga['codigo']);
    }

    public function test_cartao_ponto_reconhece_jornada_no_dia_escalado(): void
    {
        [, $cols] = $this->escalaComQuatroPosicoes('2026-05-04');
        $colab = $cols[0];

        FrequenciaRegistro::query()->create([
            'colaborador_id' => $colab->id,
            'data' => '2026-05-04',
            'entrada_1' => '07:30:00',
            'saida_1' => '12:00:00',
            'entrada_2' => '13:00:00',
            'saida_2' => '17:30:00',
            'status' => 'completo',
            'origem' => 'ponto',
        ]);

        $cartoes = app(CartaoPontoService::class)->montarCartoes(
            collect([$colab->load('horarioEscala.dias')]),
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-10')
        );

        $this->assertNotEmpty($cartoes);
        $linhaSegunda = collect($cartoes[0]['linhas'])
            ->first(fn ($linha) => ($linha['data_ymd'] ?? null) === '2026-05-04');

        $this->assertNotNull($linhaSegunda, 'Linha da segunda-feira não encontrada no cartão');
        $this->assertGreaterThan(0, (int) ($linhaSegunda['minutos_previstas'] ?? 0));
        $this->assertNotSame('', $linhaSegunda['total_trabalhado'] ?? '');
    }

    public function test_datas_anteriores_ao_inicio_continuam_deterministicas(): void
    {
        [$escala] = $this->escalaComQuatroPosicoes('2026-05-04');

        // Sexta anterior à segunda inicial = índice -1 → posição 3
        $this->assertSame(-1, HorarioEscalaDiasUteis::indiceDiaUtil($escala, '2026-05-01'));
        $this->assertSame(3, HorarioEscalaDiasUteis::posicaoNaData($escala, '2026-05-01'));
        $this->assertTrue(HorarioEscalaDiasUteis::trabalhaNoDia($escala, '2026-05-01', 3));
    }
}
