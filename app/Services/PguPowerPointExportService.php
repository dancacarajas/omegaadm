<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Drawing\File as DrawingFile;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class PguPowerPointExportService
{
    public function debugAuxServer(): array
    {
        $server = null;
        try {
            $server = $this->bootAuxCaptureServer();
            $baseUrl = $server['base_url'];
            $health = @file_get_contents($baseUrl.'/pgu-capture/health');

            return [
                'ok' => trim((string) $health) === 'OK',
                'base_url' => $baseUrl,
                'port' => $server['port'],
                'health' => $health,
                'php_binary' => $this->resolvePhpCliBinary(),
            ];
        } finally {
            if (is_array($server) && ($server['process'] ?? null) instanceof Process && $server['process']->isRunning()) {
                $server['process']->stop(1);
            }
        }
    }

    public function export(Request $request): string
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '1024M');

        $exportId = (string) Str::uuid();
        $baseDir = storage_path("app/pgu-export/{$exportId}");
        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        Log::info('PGU PPT export started', [
            'export_id' => $exportId,
            'base_dir' => $baseDir,
            'query' => $request->query(),
        ]);

        try {
            $this->assertRuntime($baseDir);
            $query = $this->cleanQuery($request);
            $baseUrl = $this->resolveBaseUrl($request);
            $captureBaseUrl = $this->resolveCaptureBaseUrl($baseUrl);

            $captureToken = Str::random(64);
            Cache::put("pgu_capture_token:{$captureToken}", $query, now()->addMinutes(15));
            $query['capture_token'] = $captureToken;

            $browserUserDataDir = $baseDir.DIRECTORY_SEPARATOR.'browser-profile';
            if (! is_dir($browserUserDataDir)) {
                mkdir($browserUserDataDir, 0775, true);
            }

            $pngFiles = $this->captureSlides($captureBaseUrl, $query, $baseDir, $browserUserDataDir);
            $pptxPath = $this->buildPptx($pngFiles, $baseDir);

            if (! file_exists($pptxPath) || filesize($pptxPath) < 10000) {
                throw new \RuntimeException('Arquivo PPTX nao foi gerado corretamente ou ficou muito pequeno.');
            }

            Log::info('PGU PPT export finished', [
                'export_id' => $exportId,
                'pptx' => $pptxPath,
                'size' => filesize($pptxPath),
            ]);

            return $pptxPath;
        } catch (Throwable $e) {
            Log::error('PGU PPT export failed', [
                'export_id' => $exportId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function debugCapture(Request $request): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '1024M');

        $exportId = 'debug-'.now()->format('Ymd-His');
        $baseDir = storage_path("app/pgu-export/{$exportId}");
        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $this->assertRuntime($baseDir);

        $baseUrl = $this->resolveBaseUrl($request);
        $captureBaseUrl = $this->resolveCaptureBaseUrl($baseUrl);
        $query = $this->cleanQuery($request);
        $captureToken = Str::random(64);
        Cache::put("pgu_capture_token:{$captureToken}", $query, now()->addMinutes(15));
        $query['capture_token'] = $captureToken;
        $firstSlide = config('pgu_export.slides.0');
        $url = $this->buildSlideUrl($captureBaseUrl, (string) ($firstSlide['path'] ?? '/pgu-cover'), $query);
        $pngPath = $baseDir.DIRECTORY_SEPARATOR.'debug-cover.png';

        $browserUserDataDir = $baseDir.DIRECTORY_SEPARATOR.'browser-profile';
        if (! is_dir($browserUserDataDir)) {
            mkdir($browserUserDataDir, 0775, true);
        }

        $this->captureUrlToPng($url, $pngPath, $browserUserDataDir);

        return [
            'ok' => file_exists($pngPath),
            'url' => $url,
            'png' => $pngPath,
            'size' => file_exists($pngPath) ? filesize($pngPath) : 0,
            'chrome_path' => $this->resolveChromePath(),
            'base_dir' => $baseDir,
        ];
    }

    private function assertRuntime(string $baseDir): void
    {
        if (! is_dir($baseDir) || ! is_writable($baseDir)) {
            throw new \RuntimeException("Diretorio de exportacao sem permissao de escrita: {$baseDir}");
        }

        $node = $this->resolveCommandVersion(['node', '-v']);
        $npm = $this->resolveCommandVersion(['npm', '-v']);

        Log::info('PGU PPT runtime check', [
            'node' => $node,
            'npm' => $npm,
            'chrome_path' => $this->resolveChromePath(),
        ]);

        $nodeLower = strtolower($node);
        if ($node === '' || str_contains($nodeLower, 'not recognized') || str_contains($nodeLower, 'not found')) {
            throw new \RuntimeException('Node.js nao foi encontrado. Instale o Node.js e rode npm install.');
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function resolveCommandVersion(array $command): string
    {
        try {
            $process = new Process($command, base_path());
            $process->setTimeout(10);
            $process->run();

            $output = trim($process->getOutput().' '.$process->getErrorOutput());

            return $output;
        } catch (Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function cleanQuery(Request $request): array
    {
        return collect($request->only(['contrato', 'competencia', 'data_limite_etapa_2']))
            ->filter(fn ($v) => filled($v))
            ->all();
    }

    private function resolveBaseUrl(Request $request): string
    {
        $appUrl = config('app.url');
        if (filled($appUrl)) {
            return rtrim((string) $appUrl, '/');
        }

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }

    private function resolveCaptureBaseUrl(string $fallbackBaseUrl): string
    {
        $configured = config('pgu_export.capture_base_url');
        if (is_string($configured) && trim($configured) !== '') {
            $configured = rtrim($configured, '/');
            if ($this->isCaptureBaseUrlUsable($configured)) {
                return $configured;
            }

            Log::warning('PGU capture base URL unusable, falling back to request base URL', [
                'configured' => $configured,
                'fallback' => rtrim($fallbackBaseUrl, '/'),
            ]);
        }

        return rtrim($fallbackBaseUrl, '/');
    }

    private function isCaptureBaseUrlUsable(string $baseUrl): bool
    {
        $checks = [
            $baseUrl.'/pgu-capture/health',
            $baseUrl.'/build/manifest.json',
        ];

        foreach ($checks as $url) {
            if (! $this->httpIs200($url)) {
                return false;
            }
        }

        return true;
    }

    private function httpIs200(string $url): bool
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 3,
                    'ignore_errors' => true,
                    'follow_location' => 0,
                ],
            ]);

            @file_get_contents($url, false, $context);
            $statusLine = $http_response_header[0] ?? '';

            return str_contains($statusLine, ' 200 ');
        } catch (Throwable) {
            return false;
        }
    }

    private function captureSlides(string $baseUrl, array $query, string $baseDir, string $browserUserDataDir): array
    {
        $slides = config('pgu_export.slides', []);
        $pngFiles = [];

        foreach ($slides as $slide) {
            $key = (string) ($slide['key'] ?? 'slide');
            $path = (string) ($slide['path'] ?? '');
            $filename = (string) ($slide['filename'] ?? "{$key}.png");

            $url = $this->buildSlideUrl($baseUrl, $path, $query);
            $pngPath = $baseDir.DIRECTORY_SEPARATOR.$filename;

            Log::info('PGU PPT capturing slide', [
                'slide' => $key,
                'url' => $url,
                'png' => $pngPath,
            ]);

            Log::info('PGU capture URL', [
                'slide' => $key,
                'url' => $url,
                'png' => $pngPath,
            ]);

            $this->captureUrlToPng($url, $pngPath, $browserUserDataDir);

            if (! file_exists($pngPath)) {
                throw new \RuntimeException("PNG nao foi criado para o slide {$key}: {$pngPath}");
            }

            if (filesize($pngPath) < 15000) {
                throw new \RuntimeException("PNG do slide {$key} parece invalido ou vazio. Tamanho: ".filesize($pngPath));
            }

            $imgInfo = @getimagesize($pngPath) ?: [0, 0];
            Log::info('PGU PNG captured', [
                'slide' => $key,
                'png' => $pngPath,
                'size' => filesize($pngPath),
                'width' => (int) ($imgInfo[0] ?? 0),
                'height' => (int) ($imgInfo[1] ?? 0),
            ]);

            Log::info('PGU capture PNG created', [
                'slide' => $key,
                'png' => $pngPath,
                'exists' => file_exists($pngPath),
                'size' => file_exists($pngPath) ? filesize($pngPath) : null,
            ]);

            $pngFiles[] = $pngPath;
        }

        return $pngFiles;
    }

    private function buildSlideUrl(string $baseUrl, string $path, array $query): string
    {
        $url = rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    private function captureUrlToPng(string $url, string $pngPath, string $browserUserDataDir): void
    {
        $viewport = config('pgu_export.viewport', ['width' => 1366, 'height' => 768]);
        $timeoutSecs = (int) config('pgu_export.timeout', 180);
        $scale = max(1, (int) config('pgu_export.scale', 2));

        $shot = Browsershot::url($url)
            ->windowSize((int) ($viewport['width'] ?? 1366), (int) ($viewport['height'] ?? 768))
            ->deviceScaleFactor(1)
            ->setDelay(1200)
            ->timeout(max(1, $timeoutSecs))
            ->setOption('waitUntil', 'domcontentloaded')
            ->setOption('timeout', $timeoutSecs * 1000)
            ->setOption('protocolTimeout', $timeoutSecs * 1000)
            ->setOption('cacheEnabled', false)
            ->setOption('userDataDir', $browserUserDataDir)
            ->setOption('fullPage', false)
            ->setOption('omitBackground', false)
            ->setOption('args', [
                '--disable-dev-shm-usage',
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-gpu',
                '--hide-scrollbars',
                '--disable-service-worker',
                '--disable-application-cache',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding',
            ]);

        $chromePath = $this->resolveChromePath();
        if ($chromePath !== null) {
            $shot->setChromePath($chromePath);
        }

        try {
            $shot->save($pngPath);
        } catch (ProcessFailedException $e) {
            Log::error('PGU capture failed', [
                'url' => $url,
                'png' => $pngPath,
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Falha ao capturar slide no Chrome headless: '.$e->getMessage(), 0, $e);
        }
    }

    private function buildPptx(array $pngFiles, string $baseDir): string
    {
        $presentation = new PhpPresentation;
        $slideWidth = 1366;
        $slideHeight = 768;

        $presentation
            ->getLayout()
            ->setDocumentLayout(DocumentLayout::LAYOUT_CUSTOM, true)
            ->setCX($slideWidth, DocumentLayout::UNIT_PIXEL)
            ->setCY($slideHeight, DocumentLayout::UNIT_PIXEL);

        while ($presentation->getSlideCount() > 0) {
            $presentation->removeSlideByIndex(0);
        }

        foreach ($pngFiles as $index => $pngPath) {
            if (! file_exists($pngPath)) {
                throw new \RuntimeException("PNG nao encontrado para montagem do PPT: {$pngPath}");
            }

            if (filesize($pngPath) < 10000) {
                throw new \RuntimeException("PNG parece invalido ou vazio: {$pngPath}. Tamanho: ".filesize($pngPath));
            }

            $img = @getimagesize($pngPath) ?: [0, 0];
            Log::info('PGU PPT adding image to slide', [
                'index' => $index,
                'png' => $pngPath,
                'file_size' => filesize($pngPath),
                'image_width' => (int) ($img[0] ?? 0),
                'image_height' => (int) ($img[1] ?? 0),
                'slide_width_px' => $slideWidth,
                'slide_height_px' => $slideHeight,
            ]);

            $slide = $presentation->createSlide();
            $shape = new DrawingFile;
            $shape->setPath((string) realpath($pngPath));
            $shape->setName('PGU Slide');
            $shape->setDescription('PGU Slide Export');
            $shape->setResizeProportional(false);
            $shape->setOffsetX(0);
            $shape->setOffsetY(0);
            $shape->setWidth($slideWidth);
            $shape->setHeight($slideHeight);
            $slide->addShape($shape);
        }

        $pptxPath = $baseDir.DIRECTORY_SEPARATOR.'pgu-visao-executiva.pptx';
        IOFactory::createWriter($presentation, 'PowerPoint2007')->save($pptxPath);

        Log::info('PGU PPT created successfully', [
            'pptx' => $pptxPath,
            'size' => file_exists($pptxPath) ? filesize($pptxPath) : 0,
            'slides' => count($pngFiles),
        ]);

        return $pptxPath;
    }

    private function resolveChromePath(): ?string
    {
        $configured = config('pgu_export.chrome_path');
        if (is_string($configured) && $configured !== '' && file_exists($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{process: Process, base_url: string, port: int}
     */
    private function bootAuxCaptureServer(): array
    {
        $phpBinary = $this->resolvePhpCliBinary();
        if ($phpBinary === null) {
            throw new \RuntimeException('PHP CLI nao encontrado. Verifique PGU_EXPORT_PHP_BINARY ou PATH.');
        }

        $host = (string) config('pgu_export.capture_host', '127.0.0.1');
        $router = base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
        $publicPath = public_path();
        $ports = $this->auxCaptureCandidatePorts();

        Log::info('PGU PPT aux server boot started', [
            'php_binary' => $phpBinary,
            'php_version' => $this->safeShellOutput([$phpBinary, '-v']),
            'router' => $router,
            'public_path' => $publicPath,
            'base_path' => base_path(),
            'host' => $host,
            'ports' => $ports,
        ]);

        if (! file_exists($router)) {
            throw new \RuntimeException("Router Laravel nao encontrado: {$router}");
        }

        if (! is_dir($publicPath)) {
            throw new \RuntimeException("Pasta public nao encontrada: {$publicPath}");
        }

        $lastError = null;

        foreach ($ports as $port) {
            if ($this->isPortOpen($host, (int) $port)) {
                Log::warning('PGU PPT aux server port already in use', [
                    'host' => $host,
                    'port' => $port,
                ]);
                continue;
            }

            $command = [$phpBinary, '-S', "{$host}:{$port}", '-t', $publicPath, $router];

            Log::info('PGU PPT aux server trying port', [
                'port' => $port,
                'command' => implode(' ', array_map(fn ($v) => '"'.$v.'"', $command)),
                'working_directory' => base_path(),
            ]);

            $process = new Process($command, base_path());
            $process->setTimeout(null);
            $process->setIdleTimeout(null);

            try {
                $process->start();
                usleep(800000);

                if (! $process->isRunning()) {
                    $lastError = [
                        'port' => $port,
                        'exit_code' => $process->getExitCode(),
                        'stdout' => $process->getOutput(),
                        'stderr' => $process->getErrorOutput(),
                    ];
                    Log::error('PGU PPT aux server died immediately', $lastError);
                    continue;
                }

                $baseUrl = "http://{$host}:{$port}";
                $this->waitUntilCaptureServerIsReady($baseUrl, $process, $port);

                Log::info('PGU PPT aux server started successfully', [
                    'port' => $port,
                    'base_url' => $baseUrl,
                    'pid' => method_exists($process, 'getPid') ? $process->getPid() : null,
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);

                return [
                    'process' => $process,
                    'base_url' => $baseUrl,
                    'port' => $port,
                ];
            } catch (Throwable $e) {
                $lastError = [
                    'port' => $port,
                    'message' => $e->getMessage(),
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                    'running' => $process->isRunning(),
                    'exit_code' => $process->getExitCode(),
                ];
                Log::error('PGU PPT aux server failed on port', $lastError);

                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
        }

        Log::error('PGU PPT aux server failed on all ports', ['last_error' => $lastError]);

        throw new \RuntimeException(
            'Nao foi possivel iniciar o servidor auxiliar de captura para exportacao PPT. '
            .'Nenhuma porta disponivel no intervalo configurado. '
            .'Host: '.$host.'. '
            .'Portas testadas: '.implode(', ', $ports).'. '
            .'Ultimo erro: '.json_encode($lastError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return list<int>
     */
    private function auxCaptureCandidatePorts(): array
    {
        $fixedPort = config('pgu_export.capture_aux_port');
        if (! empty($fixedPort)) {
            return [(int) $fixedPort];
        }

        $start = (int) config('pgu_export.capture_aux_port_start', 56080);
        $end = (int) config('pgu_export.capture_aux_port_end', 56150);
        if ($start <= 0 || $end <= 0 || $end < $start) {
            $start = 56080;
            $end = 56150;
        }

        return range($start, $end);
    }

    private function resolvePhpCliBinary(): ?string
    {
        $candidates = [];
        $envPhp = env('PGU_EXPORT_PHP_BINARY');
        if ($envPhp) {
            $candidates[] = $envPhp;
        }
        $candidates = array_merge($candidates, [
            PHP_BINARY,
            'php',
            'C:\\laragon\\bin\\php\\php-8.2\\php.exe',
            'C:\\laragon\\bin\\php\\php-8.3\\php.exe',
            'C:\\xampp\\php\\php.exe',
            'C:\\Program Files\\PHP\\php.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            if (str_contains(strtolower(basename($candidate)), 'php-cgi')) {
                continue;
            }

            try {
                $process = new Process([$candidate, '-v'], base_path());
                $process->setTimeout(10);
                $process->run();
                $output = strtolower($process->getOutput().' '.$process->getErrorOutput());

                if ($process->isSuccessful() && str_contains($output, 'php')) {
                    Log::info('PGU PPT PHP CLI resolved', [
                        'candidate' => $candidate,
                        'output' => $process->getOutput(),
                    ]);
                    return $candidate;
                }
            } catch (Throwable $e) {
                Log::warning('PGU PPT PHP CLI candidate failed', [
                    'candidate' => $candidate,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function isPortOpen(string $host, int $port): bool
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if ($conn === false) {
            return false;
        }

        fclose($conn);

        return true;
    }

    private function canBindPort(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $server = @stream_socket_server(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if ($server === false) {
            Log::warning('PGU PPT aux port cannot bind', [
                'host' => $host,
                'port' => $port,
                'errno' => $errno,
                'errstr' => $errstr,
            ]);

            return false;
        }

        fclose($server);

        return true;
    }

    private function waitUntilCaptureServerIsReady(string $baseUrl, Process $process, int $port): void
    {
        $healthUrl = rtrim($baseUrl, '/').'/pgu-capture/health';
        $startedAt = microtime(true);
        $lastResponse = null;
        $lastError = null;

        while ((microtime(true) - $startedAt) < 20) {
            if (! $process->isRunning()) {
                throw new \RuntimeException(
                    'Servidor auxiliar morreu antes do healthcheck. Porta: '.$port
                    .' | Exit code: '.$process->getExitCode()
                    .' | STDOUT: '.$process->getOutput()
                    .' | STDERR: '.$process->getErrorOutput()
                );
            }
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 2,
                        'ignore_errors' => true,
                    ],
                ]);

                $result = @file_get_contents($healthUrl, false, $context);
                $lastResponse = $result;
                if (trim((string) $result) === 'OK') {
                    return;
                }
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }

            usleep(300000);
        }

        throw new \RuntimeException(
            'Servidor auxiliar iniciou, mas nao respondeu no healthcheck. URL: '.$healthUrl
            .' | Porta: '.$port
            .' | Ultima resposta: '.trim((string) $lastResponse)
            .' | Ultimo erro: '.$lastError
            .' | STDOUT: '.$process->getOutput()
            .' | STDERR: '.$process->getErrorOutput()
        );
    }

    private function safeShellOutput(array $command): string
    {
        try {
            $process = new Process($command, base_path());
            $process->setTimeout(10);
            $process->run();

            return trim($process->getOutput()."\n".$process->getErrorOutput());
        } catch (Throwable $e) {
            return 'Erro ao executar comando: '.$e->getMessage();
        }
    }
}

