<?php

namespace App\Console\Commands;

use App\Support\PublicWebBase;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;

class AssetsDiagnosticoCommand extends Command
{
    protected $signature = 'assets:diagnostico
                            {--curl : HEAD nas URLs de CSS/JS do manifest (usa APP_URL)}';

    protected $description = 'Verifica manifest Vite, arquivos em public/build e URLs com /public (layout Hostinger)';

    public function handle(): int
    {
        if (is_file(public_path('hot'))) {
            $this->error('Arquivo public/hot presente — o Laravel usará o Vite dev server e o layout em produção ficará quebrado.');
            $this->line('Remova: del public\\hot (Windows) ou rm public/hot (Linux). Não faça deploy deste arquivo.');

            return self::FAILURE;
        }

        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            $this->error('Manifest ausente: public/build/manifest.json');
            $this->line('Rode: npm ci && npm run build');

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $entries = array_filter($manifest, fn ($chunk) => is_array($chunk) && isset($chunk['file']));

        $this->info('Manifest: '.count($entries).' entradas com arquivo');

        $missing = [];
        foreach ($entries as $chunk) {
            $relative = 'build/'.$chunk['file'];
            if (! is_file(public_path($relative))) {
                $missing[] = $relative;
            }
        }

        if ($missing !== []) {
            $this->error('Arquivos referenciados no manifest mas ausentes em disco:');
            foreach ($missing as $path) {
                $this->line("  • {$path}");
            }
            $this->line('Rode: npm run build');

            return self::FAILURE;
        }

        $this->info('Todos os arquivos do manifest existem em public/build/');

        $assinaturaAssets = [
            'images/email/assinatura-eletronica-bg.jpg',
            'fonts/Arial.ttf',
            'fonts/Arial-Bold.ttf',
        ];
        foreach ($assinaturaAssets as $relative) {
            if (! is_file(public_path($relative))) {
                $this->error('Arquivo ausente (assinatura eletrônica): public/'.$relative);

                return self::FAILURE;
            }
        }
        $this->info('Assets da assinatura eletrônica presentes em public/');

        $appUrl = rtrim((string) config('app.url'), '/');
        $this->line('APP_URL: '.$appUrl);
        $this->line('force_public_url: '.(config('app.force_public_url') ? 'true' : 'false'));

        $root = PublicWebBase::rootUrl(Request::create('/public/', 'GET'));
        if ($root === null && filter_var(config('app.force_public_url'), FILTER_VALIDATE_BOOLEAN)) {
            $root = $appUrl;
            if (! str_ends_with($root, '/public')) {
                $root .= '/public';
            }
        }
        if ($root !== null) {
            \Illuminate\Support\Facades\URL::forceRootUrl($root);
            Vite::createAssetPathsUsing(static fn (string $path, ?bool $secure = null): string => asset($path));
        }

        $cssUrl = Vite::asset('resources/css/app.css');
        $jsUrl = Vite::asset('resources/js/app.js');
        $this->line('Vite CSS: '.$cssUrl);
        $this->line('Vite JS:  '.$jsUrl);

        if (! str_contains($cssUrl, '/public/build/')) {
            $this->error('URL do CSS sem /public/build/ — layout em produção ficará sem Tailwind.');
            $this->line('Confira APP_FORCE_PUBLIC_URL e ForceRequestRootUrl / AppServiceProvider.');

            return self::FAILURE;
        }

        $request = Request::create('/public/rh/chamados-movimentacao/1', 'GET');
        $this->line('PublicWebBase (simulação /public/...): '.(PublicWebBase::shouldUse($request) ? 'SIM' : 'NÃO'));
        $this->line('rootUrl: '.(PublicWebBase::rootUrl($request) ?? '(null)'));

        $assinaturaUrls = [
            PublicWebBase::assetUrl('images/email/assinatura-eletronica-bg.jpg'),
            PublicWebBase::assetUrl('fonts/Arial.ttf'),
        ];
        $this->line('Assinatura BG: '.$assinaturaUrls[0]);
        $this->line('Assinatura fonte: '.$assinaturaUrls[1]);

        if ($this->option('curl')) {
            $this->verificarHttpExterno(array_merge([$cssUrl, $jsUrl], $assinaturaUrls));
        } else {
            $this->line('Dica: php artisan assets:diagnostico --curl');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $urls
     */
    private function verificarHttpExterno(array $urls): void
    {
        $this->newLine();
        $this->line('HTTP externo (assets):');

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(25)->head($url);
                $status = $response->status();
                $ok = $status >= 200 && $status < 400;
                $this->line('  ['.($ok ? 'OK' : 'ERRO')."] HTTP {$status} — {$url}");
            } catch (\Throwable $e) {
                $this->warn("  [falhou] {$url} — {$e->getMessage()}");
            }
        }
    }
}
