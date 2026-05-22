<?php

namespace Tests\Feature;

use App\Http\Middleware\ForceRequestRootUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AssetsPublicBaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $hot = public_path('hot');
        if (is_file($hot)) {
            unlink($hot);
        }
    }

    public function test_vite_assets_usam_base_public_em_producao(): void
    {
        config([
            'app.url' => 'https://omegaadm.feston.net.br',
            'app.force_public_url' => true,
        ]);

        URL::forceRootUrl('https://omegaadm.feston.net.br/public');
        Vite::createAssetPathsUsing(static fn (string $path, ?bool $secure = null): string => asset($path));

        $css = Vite::asset('resources/css/app.css');

        $this->assertStringContainsString('/public/build/assets/', $css);
    }

    public function test_middleware_mantem_vite_com_public_quando_request_uri_tem_public(): void
    {
        config(['app.force_public_url' => false, 'app.url' => 'https://omegaadm.feston.net.br']);

        $request = Request::create('/public/rh/chamados-movimentacao/1', 'GET');

        $middleware = new ForceRequestRootUrl;
        $middleware->handle($request, fn () => response('ok'));

        $css = Vite::asset('resources/css/app.css');

        $this->assertStringContainsString('/public/build/assets/', $css);
    }

    public function test_login_view_inclui_links_vite_com_public(): void
    {
        config(['app.url' => 'https://omegaadm.feston.net.br']);

        URL::forceRootUrl('https://omegaadm.feston.net.br/public');
        Vite::createAssetPathsUsing(static fn (string $path, ?bool $secure = null): string => asset($path));

        $html = view('auth.login', ['errors' => new ViewErrorBag])->render();

        $this->assertStringContainsString('/public/build/assets/', $html);
        $this->assertStringContainsString('rel="stylesheet"', $html);
    }
}
