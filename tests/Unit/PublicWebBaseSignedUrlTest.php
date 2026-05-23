<?php

namespace Tests\Unit;

use App\Support\PublicWebBase;
use Tests\TestCase;

class PublicWebBaseSignedUrlTest extends TestCase
{
    public function test_assinatura_usa_base_sem_public(): void
    {
        config(['app.url' => 'https://omegaadm.feston.net.br', 'app.force_public_url' => true]);

        $url = PublicWebBase::temporarySignedRouteWithPublicPrefix(
            'rh.beneficios.vinculos.formulario-adesao.visualizar',
            now()->addHour(),
            ['beneficio' => 1, 'vinculo' => 2],
        );

        $this->assertStringContainsString('/public/rh/beneficios/1/vinculos/2/formulario-adesao/visualizar', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_inserir_public_no_path(): void
    {
        $url = PublicWebBase::inserirPublicNoPath('https://exemplo.com/rh/foo?expires=1&signature=abc');

        $this->assertSame('https://exemplo.com/public/rh/foo?expires=1&signature=abc', $url);
    }
}
