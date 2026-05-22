<?php

namespace Tests\Unit\Rh;

use App\Support\Rh\MoedaBr;
use PHPUnit\Framework\TestCase;

class MoedaBrTest extends TestCase
{
    public function test_parse_formato_brasileiro_com_centavos(): void
    {
        $this->assertSame(3091.91, MoedaBr::parse('3.091,91'));
        $this->assertSame(3091.91, MoedaBr::parse('3091,91'));
        $this->assertSame(2669.35, MoedaBr::parse('2.669,35'));
        $this->assertSame(2669.35, MoedaBr::parse('R$ 2.669,35'));
    }

    public function test_parse_decimal_americano_do_excel(): void
    {
        $this->assertSame(3091.91, MoedaBr::parse('3091.91'));
    }

    public function test_nao_confunde_milhar_sem_virgula_com_decimal(): void
    {
        $this->assertSame(3091.0, MoedaBr::parse('3.091'));
    }

    public function test_format(): void
    {
        $this->assertSame('R$ 3.091,91', MoedaBr::format(3091.91));
    }
}
