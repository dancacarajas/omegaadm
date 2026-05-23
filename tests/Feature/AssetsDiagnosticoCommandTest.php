<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetsDiagnosticoCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $hot = public_path('hot');
        if (is_file($hot)) {
            unlink($hot);
        }
    }

    public function test_diagnostico_cli_usa_app_url_nas_urls_vite(): void
    {
        config([
            'app.url' => 'https://omegaadm.feston.net.br',
            'app.force_public_url' => true,
        ]);

        $this->artisan('assets:diagnostico')
            ->expectsOutputToContain('Vite CSS: https://omegaadm.feston.net.br/public/build/')
            ->expectsOutputToContain('rootUrl: https://omegaadm.feston.net.br/public')
            ->assertSuccessful();
    }
}
