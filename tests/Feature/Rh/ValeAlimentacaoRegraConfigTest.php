<?php

namespace Tests\Feature\Rh;

use App\Support\Rh\ValeAlimentacaoRegraConfig;
use Tests\TestCase;

class ValeAlimentacaoRegraConfigTest extends TestCase
{
    public function test_percentual_padrao_act(): void
    {
        $cfg = ValeAlimentacaoRegraConfig::resolver(null);
        $this->assertSame(0.0, $cfg->percentualDescontoPorFaltas(0));
        $this->assertSame(0.20, $cfg->percentualDescontoPorFaltas(1));
        $this->assertSame(0.50, $cfg->percentualDescontoPorFaltas(2));
        $this->assertSame(1.0, $cfg->percentualDescontoPorFaltas(3));
        $this->assertSame(1.0, $cfg->percentualDescontoPorFaltas(10));
    }

    public function test_faixas_customizadas(): void
    {
        $cfg = ValeAlimentacaoRegraConfig::resolver([
            'desconto_faltas' => [
                ['de' => 1, 'ate' => 2, 'percentual' => 10],
                ['de' => 3, 'ate' => null, 'percentual' => 80],
            ],
        ]);
        $this->assertSame(0.10, $cfg->percentualDescontoPorFaltas(1));
        $this->assertSame(0.10, $cfg->percentualDescontoPorFaltas(2));
        $this->assertSame(0.80, $cfg->percentualDescontoPorFaltas(5));
    }
}
