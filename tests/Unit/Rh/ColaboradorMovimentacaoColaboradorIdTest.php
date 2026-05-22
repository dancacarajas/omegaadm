<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use PHPUnit\Framework\TestCase;

class ColaboradorMovimentacaoColaboradorIdTest extends TestCase
{
    public function test_comparacao_estrita_falha_quando_colaborador_id_e_string(): void
    {
        $colaborador = new Colaborador;
        $colaborador->id = 34;

        $movimentacao = new ColaboradorMovimentacao;
        $movimentacao->setRawAttributes(['colaborador_id' => '34']);

        $rawColaboradorId = $movimentacao->getAttributes()['colaborador_id'];
        $this->assertSame('34', $rawColaboradorId);
        $this->assertFalse($rawColaboradorId === $colaborador->id);
        $this->assertTrue((int) $rawColaboradorId === (int) $colaborador->id);
    }
}
