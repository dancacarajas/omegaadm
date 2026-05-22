<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EmailLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracaoEmailLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_layout_requer_autenticacao(): void
    {
        $this->get(route('configuracoes.email.layout-preview'))
            ->assertRedirect();
    }

    public function test_renderiza_html_do_exemplo(): void
    {
        $html = EmailLayout::render('emails.exemplo-aprovacao', ['preview' => true]);

        $this->assertStringContainsString('Chamado de movimentação atualizado', $html);
        $this->assertStringContainsString('Maria da Silva Santos', $html);
        $this->assertStringContainsString('#6f1731', $html);
        $this->assertStringContainsString('Pré-visualização do layout', $html);
    }

    public function test_usuario_autenticado_acessa_preview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('configuracoes.email.layout-preview'))
            ->assertOk()
            ->assertSee('Chamado de movimentação atualizado', false)
            ->assertSee('Pré-visualização do layout', false);
    }
}
