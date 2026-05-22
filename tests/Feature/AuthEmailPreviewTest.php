<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEmailPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderiza_todos_tipos_de_email_auth(): void
    {
        foreach (array_keys(AuthEmailService::tiposPreview()) as $tipo) {
            $html = app(AuthEmailService::class)->renderPreview($tipo);
            $this->assertStringContainsString('Omega Adm CT 286', $html, "Rodapé ausente em {$tipo}");
            $this->assertStringContainsString('Pré-visualização do layout', $html, "Banner preview ausente em {$tipo}");
        }
    }

    public function test_preview_auth_requer_login(): void
    {
        $this->get(route('configuracoes.email.preview.auth', 'usuario-cadastrado'))
            ->assertRedirect();
    }

    public function test_preview_usuario_cadastrado_ok(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('configuracoes.email.preview.auth', 'usuario-cadastrado'))
            ->assertOk()
            ->assertSee('Bem-vindo ao portal', false)
            ->assertSee('Senha inicial', false);
    }

    public function test_tipo_invalido_retorna_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('configuracoes.email.preview.auth', 'inexistente'))
            ->assertNotFound();
    }
}
