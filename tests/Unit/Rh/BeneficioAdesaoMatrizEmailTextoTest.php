<?php

namespace Tests\Unit\Rh;

use App\Models\Colaborador;
use App\Support\Rh\BeneficioAdesaoMatrizEmailTexto;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BeneficioAdesaoMatrizEmailTextoTest extends TestCase
{
    #[DataProvider('horariosSaudacao')]
    public function test_saudacao_conforme_horario_brasilia(int $hora, string $esperado): void
    {
        $momento = Carbon::parse('2026-05-22 00:00:00', 'America/Sao_Paulo')->setHour($hora);

        $this->assertSame($esperado, BeneficioAdesaoMatrizEmailTexto::saudacaoHorarioBrasilia($momento));
    }

    /** @return array<string, array{int, string}> */
    public static function horariosSaudacao(): array
    {
        return [
            'manha' => [9, 'bom dia'],
            'meio_dia' => [12, 'boa tarde'],
            'tarde' => [15, 'boa tarde'],
            'noite' => [18, 'boa noite'],
            'madrugada' => [2, 'boa noite'],
        ];
    }

    public function test_tagline_contrato_usa_centro_custo(): void
    {
        $this->assertSame('CONTRATO 286', BeneficioAdesaoMatrizEmailTexto::taglineContrato('286'));
        $this->assertSame('CONTRATO 236', BeneficioAdesaoMatrizEmailTexto::taglineContrato('236'));
        $this->assertSame('CONTRATO 236', BeneficioAdesaoMatrizEmailTexto::taglineContrato('contrato 236'));
    }

    public function test_assunto_segue_estrutura_fixa(): void
    {
        $assunto = BeneficioAdesaoMatrizEmailTexto::montarAssunto(
            'Vale Alimentação',
            '012345',
            'Maria da Silva',
        );

        $this->assertSame(
            'Solicitação de adesão à Matriz | Vale Alimentação | 012345 - Maria da Silva',
            $assunto,
        );
    }

    public function test_termos_femininos_para_colaboradora(): void
    {
        $colab = new Colaborador(['sexo' => 'Feminino']);
        $termos = BeneficioAdesaoMatrizEmailTexto::termosColaborador($colab);

        $this->assertSame('colaboradora', $termos['substantivo']);
        $this->assertSame('assinada', $termos['assinado']);
        $this->assertSame('pela colaboradora', $termos['pela']);
    }
}
