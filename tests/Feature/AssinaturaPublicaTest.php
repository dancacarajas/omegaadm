<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssinaturaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_publica_acessivel_sem_login(): void
    {
        $this->get(route('publico.assinatura.index'))
            ->assertOk()
            ->assertSee('Baixe sua assinatura de e-mail', false)
            ->assertSee('Informe seu CPF para localizar seus dados e gerar sua assinatura.', false);
    }

    public function test_rota_publico_nao_e_corrompida_pelo_prefixo_public(): void
    {
        $this->get('/publico/assinatura')
            ->assertOk()
            ->assertSee('Baixe sua assinatura de e-mail', false);
    }

    public function test_consulta_cpf_encontrado_retorna_dados(): void
    {
        Colaborador::query()->create([
            'nome' => 'MARIA DA SILVA',
            'status' => 'ativo',
            'cpf' => '123.456.789-09',
            'cargo' => 'Eletricista II',
            'centro_custo' => '286',
            'telefone' => '(94) 99236-4397',
            'email' => 'maria@omegaservice.com.br',
        ]);

        $this->postJson(route('publico.assinatura.cpf'), ['cpf' => '12345678909'])
            ->assertOk()
            ->assertJson([
                'encontrado' => true,
                'dados' => [
                    'nome' => 'MARIA DA SILVA',
                    'funcao' => 'Eletricista II',
                    'contrato' => '286',
                    'telefone' => '(94) 99236-4397',
                    'email' => 'maria@omegaservice.com.br',
                ],
            ]);
    }

    public function test_consulta_cpf_nao_cadastrado(): void
    {
        $this->postJson(route('publico.assinatura.cpf'), ['cpf' => '52998224725'])
            ->assertOk()
            ->assertJson(['encontrado' => false]);
    }

    public function test_consulta_cpf_invalido(): void
    {
        $this->postJson(route('publico.assinatura.cpf'), ['cpf' => '123'])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_download_jpeg_sem_autenticacao(): void
    {
        $response = $this->post(route('publico.assinatura.jpeg'), [
            'nome' => 'João Teste',
            'funcao' => 'Assistente Administrativo',
            'contrato' => 'Administrativo 286',
            'telefone' => '(94) 99236-4397',
            'email' => 'joao@omegaservice.com.br',
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
        $this->assertStringStartsWith("\xFF\xD8\xFF", $response->getContent());
    }

    public function test_download_jpeg_exige_algum_campo(): void
    {
        $this->postJson(route('publico.assinatura.jpeg'), [])
            ->assertStatus(422);
    }
}
