<?php

namespace Tests\Unit;

use App\Http\Middleware\ForceRequestRootUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ForceRequestRootUrlTest extends TestCase
{
    public function test_mantem_base_public_quando_script_e_public_index_mesmo_sem_public_no_uri(): void
    {
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
}
