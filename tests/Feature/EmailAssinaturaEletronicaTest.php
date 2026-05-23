<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use App\Models\User;
use App\Services\EmailAssinaturaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAssinaturaEletronicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_requer_autenticacao(): void
    {
        $this->get(route('configuracoes.email.assinatura.index'))
            ->assertRedirect();
    }

    public function test_usuario_autenticado_acessa_gerador(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('configuracoes.email.assinatura.index'))
            ->assertOk()
            ->assertSee('Gerador de Assinatura Eletrônica', false)
            ->assertSee('Parauapebas/PA', false);
    }

    public function test_preview_retorna_html_com_dimensoes_e_campos_fixos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('configuracoes.email.assinatura.preview'), [
                'nome' => 'Maria da Silva',
                'funcao' => 'Assistente Administrativo',
                'contrato' => 'Administrativo 286',
                'telefone' => '(94) 99236-4397',
                'email' => 'maria@omegaservice.com.br',
            ]);

        $response->assertOk();
        $html = (string) $response->json('html');
        $this->assertStringContainsString('width:583px', $html);
        $this->assertStringContainsString('height:186px', $html);
        $this->assertStringContainsString('Maria da Silva', $html);
        $this->assertStringContainsString('Parauapebas/PA', $html);
        $this->assertStringContainsString('(94) 3352 0115/(94) 99236-4397', $html);
        $this->assertStringContainsString('font-weight:bold', $html);
        $this->assertStringContainsString('color:#000000', $html);
        $this->assertStringContainsString("font-family: 'Arial'", $html);
        $this->assertStringContainsString('fonts/Arial.ttf', $html);
        $this->assertStringContainsString('font-family:Arial,sans-serif', $html);
        $this->assertStringContainsString('assinatura-eletronica-bg.jpg', $html);
    }

    public function test_dados_colaborador_json(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'João Teste',
            'status' => 'ativo',
            'cargo' => 'Técnico',
            'centro_custo' => 'CT 286',
            'telefone' => '(94) 98888-7777',
            'email' => 'joao@omegaservice.com.br',
        ]);

        $this->actingAs($user)
            ->getJson(route('configuracoes.email.assinatura.colaborador', $colab))
            ->assertOk()
            ->assertJson([
                'nome' => 'João Teste',
                'funcao' => 'Técnico',
                'contrato' => 'CT 286',
                'telefone' => '(94) 98888-7777',
                'email' => 'joao@omegaservice.com.br',
            ]);
    }

    public function test_preview_formata_caixa_alta_como_modelo_sem_alterar_entrada(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('configuracoes.email.assinatura.preview'), [
                'nome' => 'JARBAS ALVES DE CARVALHO E SILVA',
                'funcao' => 'ASSISTENTE ADMINISTRATIVO',
                'contrato' => 'ADMINISTRATIVO 286',
                'telefone' => '(94) 99236-4397',
                'email' => 'JARBAS.ALVES@OMEGASERVICE.COM.BR',
            ]);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('Jarbas Alves de Carvalho e Silva', $html);
        $this->assertStringContainsString('Assistente Administrativo', $html);
        $this->assertStringContainsString('Administrativo 286', $html);
        $this->assertStringContainsString('jarbas.alves@omegaservice.com.br', $html);
        $this->assertStringNotContainsString('JARBAS ALVES DE CARVALHO', $html);
    }

    public function test_dados_colaborador_json_mantem_cadastro_bruto(): void
    {
        $user = User::factory()->create();
        $colab = Colaborador::query()->create([
            'nome' => 'JARBAS ALVES',
            'status' => 'ativo',
            'cargo' => 'ASSISTENTE ADMINISTRATIVO',
            'centro_custo' => 'ADMINISTRATIVO 286',
            'telefone' => '(94) 98888-7777',
            'email' => 'JARBAS@TESTE.COM',
        ]);

        $this->actingAs($user)
            ->getJson(route('configuracoes.email.assinatura.colaborador', $colab))
            ->assertOk()
            ->assertJson([
                'nome' => 'JARBAS ALVES',
                'funcao' => 'ASSISTENTE ADMINISTRATIVO',
                'contrato' => 'ADMINISTRATIVO 286',
                'email' => 'JARBAS@TESTE.COM',
            ]);
    }

    public function test_funcao_preserva_numeral_romano_em_maiusculas(): void
    {
        $service = app(EmailAssinaturaService::class);

        $formatado = $service->formatarParaAssinatura([
            'nome' => 'JOAO SILVA',
            'funcao' => 'ELETRICISTA II',
            'contrato' => '',
            'telefone' => '',
            'email' => '',
        ]);

        $this->assertSame('Eletricista II', $formatado['funcao']);

        $formatadoIi = $service->formatarParaAssinatura([
            'nome' => '',
            'funcao' => 'ELETRICISTA Ii',
            'contrato' => '',
            'telefone' => '',
            'email' => '',
        ]);

        $this->assertSame('Eletricista II', $formatadoIi['funcao']);
    }

    public function test_formatar_para_assinatura_preserva_sigla_em_contrato(): void
    {
        $service = app(EmailAssinaturaService::class);

        $formatado = $service->formatarParaAssinatura([
            'nome' => 'MARIA SILVA',
            'funcao' => 'TECNICA DE ENFERMAGEM',
            'contrato' => 'CT 286 - SALOBO',
            'telefone' => '(94) 99999-0000',
            'email' => 'MARIA@TESTE.COM',
        ]);

        $this->assertSame('Maria Silva', $formatado['nome']);
        $this->assertSame('Tecnica de Enfermagem', $formatado['funcao']);
        $this->assertSame('CT 286 - Salobo', $formatado['contrato']);
        $this->assertSame('maria@teste.com', $formatado['email']);
    }

    public function test_jpeg_endpoint_retorna_imagem_alta_resolucao(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('configuracoes.email.assinatura.jpeg'), [
                'nome' => 'Maria da Silva',
                'funcao' => 'Assistente Administrativo',
                'contrato' => 'Administrativo 286',
                'telefone' => '(94) 99236-4397',
                'email' => 'maria@omegaservice.com.br',
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
        $this->assertStringStartsWith("\xFF\xD8\xFF", $response->getContent());
        $this->assertGreaterThan(80_000, strlen($response->getContent()));
    }

    public function test_service_monta_linha_telefone_com_prefixo_fixo(): void
    {
        $service = app(EmailAssinaturaService::class);

        $this->assertSame(
            '(94) 3352 0115/(94) 99999-0000',
            $service->linhaTelefone('(94) 99999-0000')
        );
    }
}
