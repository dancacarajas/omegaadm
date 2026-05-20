<?php

namespace Tests\Unit\Rh;

use App\Support\Rh\TurnoverIndicadoresPeriodo;
use PHPUnit\Framework\TestCase;

class TurnoverIndicadoresPeriodoTest extends TestCase
{
    public function test_turnover_geral_contrato_286_periodo_exemplo(): void
    {
        $r = TurnoverIndicadoresPeriodo::calcular([
            'efetivo_inicial' => 0,
            'admitidos' => 19,
            'desligados' => 1,
            'efetivo_final' => 18,
        ], ['desligamentos_voluntarios' => 1]);

        $this->assertSame(9.0, $r['efetivo_medio']);
        $this->assertSame(111.1, $r['turnover_geral']);
        $this->assertSame(11.1, $r['turnover_desligamento']);
        $this->assertSame(11.1, $r['turnover_voluntario']);
        $this->assertSame(0.0, $r['turnover_involuntario']);
    }

    public function test_exemplo_documentacao_maio(): void
    {
        $r = TurnoverIndicadoresPeriodo::calcular([
            'efetivo_inicial' => 100,
            'admitidos' => 10,
            'desligados' => 6,
            'efetivo_final' => 110,
        ]);

        $this->assertSame(105.0, $r['efetivo_medio']);
        $this->assertSame(7.6, $r['turnover_geral']);
        $this->assertSame(5.7, $r['turnover_desligamento']);
    }

    public function test_efetivo_medio_zero_retorna_traco(): void
    {
        $r = TurnoverIndicadoresPeriodo::calcular([
            'efetivo_inicial' => 0,
            'admitidos' => 0,
            'desligados' => 0,
            'efetivo_final' => 0,
        ]);

        $this->assertNull($r['turnover_geral']);
        $this->assertSame('—', $r['turnover_geral_label']);
    }
}
