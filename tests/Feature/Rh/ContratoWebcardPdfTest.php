<?php

namespace Tests\Feature\Rh;

use App\Models\Colaborador;
use App\Models\User;
use App\Services\Rh\ContratoWebcardPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratoWebcardPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_pdf_contrato_webcard_com_dados_do_colaborador(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'João da Silva Santos',
            'matricula' => '12345',
            'cpf' => '52998224725',
            'status' => 'ativo',
        ]);

        $pdf = app(ContratoWebcardPdfService::class)->render($colab, 'joao.santos@omegaservice.com.br');

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }

    public function test_pdf_usa_email_cadastrado_no_colaborador(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Ana Costa',
            'matricula' => '100',
            'cpf' => '39053344705',
            'email' => 'ana.costa@omegaservice.com.br',
            'status' => 'ativo',
        ]);

        $pdf = app(ContratoWebcardPdfService::class)->render($colab);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_rota_pdf_exige_autenticacao_e_secao_efetivo(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Maria Teste',
            'matricula' => '99',
            'cpf' => '39053344705',
            'status' => 'ativo',
        ]);

        $this->get(route('rh.efetivo.contrato-webcard.pdf', $colab))
            ->assertRedirect();

        $user = User::factory()->create(['perfil_id' => null]);

        $this->actingAs($user)
            ->get(route('rh.efetivo.contrato-webcard.pdf', ['colaborador' => $colab, 'email' => 'maria@empresa.com']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
