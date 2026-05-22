<?php

namespace Tests\Unit;

use App\Support\TstRegistroNotificacaoDestinatarios;
use PHPUnit\Framework\TestCase;

class TstRegistroNotificacaoDestinatariosTest extends TestCase
{
    public function test_normalizar_remove_duplicados(): void
    {
        $itens = TstRegistroNotificacaoDestinatarios::normalizar([
            ['tipo' => 'colaborador', 'id' => 1],
            ['tipo' => 'colaborador', 'id' => 1],
            ['tipo' => 'usuario', 'id' => 2],
        ]);

        $this->assertCount(2, $itens);
    }

    public function test_chave_composta(): void
    {
        $this->assertSame('usuario:5', TstRegistroNotificacaoDestinatarios::chave('usuario', 5));
    }
}
