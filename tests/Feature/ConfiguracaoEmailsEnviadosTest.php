<?php

namespace Tests\Feature;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaEmailEnviado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConfiguracaoEmailsEnviadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_emails_enviados_requer_autenticacao(): void
    {
        $this->get(route('configuracoes.emails-enviados.index'))
            ->assertRedirect(route('login'));
    }

    public function test_hub_emails_enviados_exibe_catalogo(): void
    {
        $admin = User::factory()->create(['todos_contratos' => true]);

        $this->actingAs($admin)
            ->get(route('configuracoes.emails-enviados.index'))
            ->assertOk()
            ->assertSee('Catálogo de e-mails do sistema')
            ->assertSee('Usuário cadastrado')
            ->assertSee('Solicitação de adesão à Matriz');
    }

    public function test_listener_registra_envio_layout_html_mail(): void
    {
        Mail::mailer('array')->to('dest@test.local')->send(new LayoutHtmlMail(
            '<p>Teste</p>',
            'Assunto teste',
            metaEnvio: [
                'categoria' => 'sistema',
                'tipo' => 'teste-smtp-central',
                'mailer' => 'array',
            ],
        ));

        $this->assertDatabaseHas('sistema_emails_enviados', [
            'tipo' => 'teste-smtp-central',
            'destinatario' => 'dest@test.local',
            'assunto' => 'Assunto teste',
        ]);
    }

    public function test_detalhe_envio(): void
    {
        $admin = User::factory()->create(['todos_contratos' => true]);
        $registro = SistemaEmailEnviado::query()->create([
            'categoria' => 'auth',
            'tipo' => 'usuario-cadastrado',
            'nome' => 'Usuário cadastrado',
            'assunto' => 'Acesso ao sistema',
            'mailer' => 'smtp',
            'destinatario' => 'user@test.local',
            'status' => 'enviado',
            'enviado_em' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('configuracoes.emails-enviados.show', $registro))
            ->assertOk()
            ->assertSee('Acesso ao sistema')
            ->assertSee('user@test.local');
    }
}
