<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\RecrutamentoVaga;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MovimentacaoEfetivoPainelContratoTest extends TestCase
{
    use RefreshDatabase;

    public function test_conta_admissoes_para_colaboradores_vinculados_por_vaga_ou_centro(): void
    {
        $vaga = RecrutamentoVaga::query()->create([
            'titulo' => 'Vaga teste',
            'contrato' => '286',
            'status' => 'Em abertura',
        ]);

        Colaborador::query()->create([
            'nome' => 'Por vaga',
            'matricula' => 'MV001',
            'status' => 'ativo',
            'recrutamento_vaga_id' => $vaga->id,
            'data_admissao' => '2026-03-24',
        ]);

        Colaborador::query()->create([
            'nome' => 'Por centro',
            'matricula' => 'MV002',
            'status' => 'ativo',
            'centro_custo' => '0286',
            'data_admissao' => '2026-03-10',
        ]);

        Colaborador::query()->create([
            'nome' => 'Outro contrato',
            'matricula' => 'MV003',
            'status' => 'ativo',
            'centro_custo' => '999',
            'data_admissao' => '2026-03-15',
        ]);

        $ini = Carbon::create(2026, 3, 1)->startOfMonth();
        $fim = Carbon::create(2026, 3, 1)->endOfMonth();

        $resumo = (new MovimentacaoEfetivoPeriodo(['286']))->resumo($ini, $fim);

        $this->assertSame(2, $resumo['admitidos']);
        $this->assertSame(2, $resumo['efetivo_final']);
    }

    public function test_efetivo_sem_data_admissao_conta_quando_centro_bate_e_created_at_anterior(): void
    {
        $c = Colaborador::query()->create([
            'nome' => 'Sem admissão',
            'matricula' => 'SA001',
            'status' => 'ativo',
            'centro_custo' => '286',
            'data_admissao' => null,
        ]);

        DB::table('colaboradores')->where('id', $c->id)->update([
            'created_at' => '2026-02-01 10:00:00',
            'updated_at' => '2026-02-01 10:00:00',
        ]);

        $ini = Carbon::create(2026, 3, 1)->startOfMonth();
        $fim = Carbon::create(2026, 3, 1)->endOfMonth();

        $resumo = (new MovimentacaoEfetivoPeriodo(['286']))->resumo($ini, $fim);

        $this->assertSame(0, $resumo['admitidos']);
        $this->assertSame(1, $resumo['efetivo_final']);
    }

    public function test_centro_custo_texto_com_numero_do_contrato_inclui_colaborador(): void
    {
        Colaborador::query()->create([
            'nome' => 'Centro descritivo',
            'matricula' => 'CD001',
            'status' => 'ativo',
            'centro_custo' => 'CC-286-OBRA',
            'data_admissao' => '2026-03-05',
        ]);

        $ini = Carbon::create(2026, 3, 1)->startOfMonth();
        $fim = Carbon::create(2026, 3, 1)->endOfMonth();

        $resumo = (new MovimentacaoEfetivoPeriodo(['286']))->resumo($ini, $fim);

        $this->assertSame(1, $resumo['admitidos']);
    }
}
