<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradorQueryPorContratoPainelTest extends TestCase
{
    use RefreshDatabase;

    public function test_inclui_colaborador_pela_escala_ct_286_sem_centro_custo(): void
    {
        $escala = HorarioEscala::query()->create(['nome' => 'CT 286', 'tipo' => 'fixa']);

        Colaborador::query()->create([
            'nome' => 'Sem CC',
            'status' => 'ativo',
            'centro_custo' => null,
            'horario_escala_id' => $escala->id,
        ]);

        $count = Colaborador::query()
            ->tap(fn ($q) => ColaboradorQueryPorContratoPainel::aplicar($q, ['286']))
            ->count();

        $this->assertSame(1, $count);
    }
}
