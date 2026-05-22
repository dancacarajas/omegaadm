<?php

namespace Tests\Unit;

use App\Support\EmailLayout;
use App\Support\PublicWebBase;
use Tests\TestCase;

class EmailLayoutLogoUrlTest extends TestCase
{
    public function test_logo_url_inclui_public_quando_force_public_url_ativo(): void
    {
        config([
            'app.url' => 'https://omegaadm.feston.net.br',
            'app.force_public_url' => true,
        ]);

        $this->assertSame(
            'https://omegaadm.feston.net.br/public/logo-email-branco.png',
            PublicWebBase::assetUrl('logo-email-branco.png')
        );

        $dados = EmailLayout::dadosPadrao();

        if ($dados['logoUrl'] !== null) {
            $this->assertStringContainsString('/public/logo', $dados['logoUrl']);
        }
    }

    public function test_logo_url_sem_duplicar_public_quando_app_url_ja_tem_public(): void
    {
        config([
            'app.url' => 'https://omegaadm.feston.net.br/public',
            'app.force_public_url' => true,
        ]);

        $this->assertSame(
            'https://omegaadm.feston.net.br/public/logo.png',
            PublicWebBase::assetUrl('logo.png')
        );
    }

    public function test_logo_url_sem_public_em_localhost(): void
    {
        config([
            'app.url' => 'http://127.0.0.1:2080',
            'app.force_public_url' => false,
        ]);

        $this->assertSame(
            'http://127.0.0.1:2080/logo.png',
            PublicWebBase::assetUrl('logo.png')
        );
    }
}
