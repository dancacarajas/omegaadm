<?php

namespace Tests\Unit;

use App\Http\Middleware\ForceRequestRootUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class ForceRequestRootUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $hot = public_path('hot');
        if (is_file($hot)) {
            unlink($hot);
        }
    }

    public function test_mantem_base_public_quando_script_e_public_index_mesmo_sem_public_no_uri(): void
    {
        config(['app.force_public_url' => false]);

        $request = Request::create('/rh/efetivo/movimentacoes', 'GET', [], [], [], [
            'SCRIPT_NAME' => '/public/index.php',
            'REQUEST_URI' => '/rh/efetivo/movimentacoes',
            'OMEGA_REQUEST_USES_PUBLIC_URL' => '1',
        ]);

        $middleware = new ForceRequestRootUrl;
        $middleware->handle($request, fn (Request $req) => response('ok'));

        $this->assertStringEndsWith('/public', rtrim(URL::to('/'), '/'));
        $this->assertStringContainsString('/public/logo.png', asset('logo.png'));
    }

    public function test_mantem_base_public_quando_request_uri_tem_public(): void
    {
        $request = Request::create('/public/rh/beneficios/1', 'GET');

        $middleware = new ForceRequestRootUrl;
        $middleware->handle($request, fn (Request $req) => response('ok'));

        $this->assertStringEndsWith('/public', rtrim(URL::to('/'), '/'));
    }

    public function test_producao_forca_base_public_por_padrao(): void
    {
        config(['app.force_public_url' => true]);

        $request = Request::create('/rh', 'GET');

        $middleware = new ForceRequestRootUrl;
        $middleware->handle($request, fn (Request $req) => response('ok'));

        $this->assertStringContainsString('/public/logo.png', asset('logo.png'));
    }

    public function test_vite_asset_usa_public_build_apos_middleware(): void
    {
        config(['app.force_public_url' => false, 'app.url' => 'https://omegaadm.feston.net.br']);

        $request = Request::create('/public/login', 'GET');

        $middleware = new ForceRequestRootUrl;
        $middleware->handle($request, fn () => response('ok'));

        $css = Vite::asset('resources/css/app.css');

        $this->assertStringContainsString('/public/build/assets/', $css);
    }
}
