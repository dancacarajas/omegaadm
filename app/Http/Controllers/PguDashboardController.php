<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\PguDashboardApiController;
use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Models\ContratoHistogramaRecorte;
use App\Models\ContratoPguAcaoRecomendada;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Drawing\File;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PguDashboardController extends Controller
{
    use ContratosHistogramaCatalog;

    private function isPublicRoute(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'publico.');
    }

    public function index(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu', [
            'contratos' => $contratos,
            'contratoDefault' => $contrato,
            'competenciaDefault' => $competencia,
            'dataLimiteDefault' => $dataLimite,
            'layout' => $this->isPublicRoute($request) ? 'layouts.public-contratos' : 'layouts.app',
        ]);
    }

    /**
     * Modo apresentação (abas estilo slides) — item de menu Contrato › Apresentação.
     */
    public function apresentacao(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        $slideVars = $this->buildPguExecutiveSlideVars($contrato, $competencia, $dataLimite);

        return view('dashboard.pgu-apresentacao', [
            'contratos' => $contratos,
            'contratoDefault' => $contrato,
            'competenciaDefault' => $competencia,
            'dataLimiteDefault' => $dataLimite,
            'pguExecutiveSlide' => $slideVars,
            'pguSlide2' => $this->buildPguSlide2Vars($contrato, $competencia, $dataLimite),
            'pguSlide3' => $this->buildPguSlide3Vars($contrato, $competencia, $dataLimite),
            'pguSlide4' => $this->buildPguSlide4Vars($contrato, $competencia, $dataLimite),
            'pguSlide5' => $this->buildPguSlide5Vars($contrato, $competencia, $dataLimite),
            'layout' => $this->isPublicRoute($request) ? 'layouts.public-contratos' : 'layouts.app',
            'exportPptRouteName' => $this->isPublicRoute($request) ? 'publico.pgu.export.ppt' : 'pgu.export.ppt',
        ]);
    }

    /**
     * Slide executivo isolado (/pgu-slide) — mesmos dados reais da apresentação.
     */
    public function pguSlide(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu-slide', [
            'pguExecutiveSlide' => $this->buildPguExecutiveSlideVars($contrato, $competencia, $dataLimite),
        ]);
    }

    /**
     * Slide 2 isolado (/pgu-slide-2) — funções com PGU 100%.
     */
    public function pguSlide2(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu-slide-2', [
            'pguSlide2' => $this->buildPguSlide2Vars($contrato, $competencia, $dataLimite),
        ]);
    }

    /**
     * Slide 3 isolado (/pgu-slide-3) — principais gargalos por função.
     */
    public function pguSlide3(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu-slide-3', [
            'pguSlide3' => $this->buildPguSlide3Vars($contrato, $competencia, $dataLimite),
        ]);
    }

    /**
     * Slide 4 isolado (/pgu-slide-4) — concentração do problema (Pareto).
     */
    public function pguSlide4(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu-slide-4', [
            'pguSlide4' => $this->buildPguSlide4Vars($contrato, $competencia, $dataLimite),
        ]);
    }

    /**
     * Slide 5 isolado (/pgu-slide-5) — plano de ação executivo.
     */
    public function pguSlide5(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu-slide-5', [
            'pguSlide5' => $this->buildPguSlide5Vars($contrato, $competencia, $dataLimite),
        ]);
    }

    /**
     * Exporta os slides PGU para PPT preservando o visual via screenshots PNG.
     */
    public function exportarPowerPoint(Request $request)
    {
        $params = array_filter([
            'contrato' => $request->input('contrato'),
            'competencia' => $request->input('competencia'),
            'data_limite_etapa_2' => $request->input('data_limite_etapa_2'),
        ], fn ($v) => $v !== null && $v !== '');

        $baseUrl = $request->getSchemeAndHttpHost();
        $captureBaseUrl = $baseUrl;
        $cookieHeader = $this->buildCaptureCookieHeader((string) $request->header('cookie', ''));
        $tmpDir = storage_path('app/pgu-export/'.Str::uuid()->toString());
        $captureServer = null;

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        try {
            [$captureBaseUrl, $captureServer] = $this->bootAuxCaptureServer();
        } catch (\Throwable $e) {
            Log::error('Falha ao iniciar servidor auxiliar para exportação PPT.', [
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Falha ao exportar PPT: não foi possível iniciar o servidor auxiliar de captura.'
            );
        }

        $slides = [
            'cover' => '/pgu-cover',
            'slide-1' => '/pgu-slide',
            'slide-2' => '/pgu-slide-2',
            'slide-3' => '/pgu-slide-3',
            'slide-4' => '/pgu-slide-4',
            'slide-5' => '/pgu-slide-5',
        ];

        $pngPaths = [];
        $chromePath = $this->resolveChromeExecutablePath();
        foreach ($slides as $key => $path) {
            $url = $captureBaseUrl.$path.(filled($params) ? '?'.http_build_query($params) : '');
            $pngPath = $tmpDir.DIRECTORY_SEPARATOR.$key.'.png';
            $pngPaths[] = $pngPath;

            $shot = Browsershot::url($url)
                ->windowSize(1366, 768)
                ->deviceScaleFactor(2)
                ->setDelay(500)
                ->setOption('waitUntil', 'domcontentloaded')
                ->setOption('timeout', 120000)
                ->setOption('fullPage', false)
                ->setOption('omitBackground', false)
                ->setOption('args', ['--disable-dev-shm-usage', '--no-sandbox']);

            if ($chromePath !== null) {
                $shot->setChromePath($chromePath);
            }

            if ($cookieHeader !== '') {
                $shot->setExtraHttpHeaders(['Cookie' => $cookieHeader]);
            }

            try {
                $shot->save($pngPath);
            } catch (ProcessFailedException $e) {
                Log::error('Falha ao capturar slide para exportação PPT.', [
                    'slide' => $key,
                    'url' => $url,
                    'chrome_path' => $chromePath,
                    'error' => $e->getMessage(),
                ]);

                if ($captureServer instanceof Process) {
                    $captureServer->stop(1);
                }

                return back()->with(
                    'error',
                    'Falha ao exportar PPT: o carregamento dos slides excedeu o tempo limite no Chrome headless. Tente novamente em alguns segundos.'
                );
            }
        }

        $presentation = new PhpPresentation;
        $presentation->removeSlideByIndex(0);
        $layout = new DocumentLayout;
        $layout->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $presentation->setLayout($layout);
        $slideWidth = (int) round($presentation->getLayout()->getCX(DocumentLayout::UNIT_PIXEL));
        $slideHeight = (int) round($presentation->getLayout()->getCY(DocumentLayout::UNIT_PIXEL));

        foreach ($pngPaths as $imgPath) {
            $slide = $presentation->createSlide();
            $shape = new File;
            $shape->setPath($imgPath);
            $shape->setOffsetX(0);
            $shape->setOffsetY(0);
            $shape->setResizeProportional(false);
            $shape->setWidth($slideWidth);
            $shape->setHeight($slideHeight);
            $slide->addShape($shape);
        }

        $pptxPath = $tmpDir.DIRECTORY_SEPARATOR.'pgu-visao-executiva.pptx';
        IOFactory::createWriter($presentation, 'PowerPoint2007')->save($pptxPath);

        $safeContrato = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $params['contrato'] ?? $request->input('contrato', 'contrato'));
        $safeCompetencia = preg_replace('/[^0-9\-]+/', '_', $params['competencia'] ?? $request->input('competencia', 'competencia'));
        $downloadName = trim((string) ("pgu-visao-executiva-{$safeContrato}-{$safeCompetencia}.pptx"), '-');

        $response = response()->download($pptxPath, $downloadName)->deleteFileAfterSend(true);

        if ($captureServer instanceof Process) {
            $captureServer->stop(1);
        }

        return $response;
    }

    /**
     * @return array{0: string, 1: Process|null}
     */
    private function bootAuxCaptureServer(): array
    {
        $host = '127.0.0.1';
        $phpBin = $this->resolvePhpCliBinary();
        $tmpProcessDir = storage_path('app/tmp/process');

        if ($phpBin === null) {
            throw new \RuntimeException('Não foi possível localizar o binário CLI do PHP para iniciar o servidor auxiliar.');
        }

        if (! is_dir($tmpProcessDir)) {
            mkdir($tmpProcessDir, 0775, true);
        }

        $routerScript = base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
        $ports = $this->auxCaptureCandidatePorts($host);

        foreach ($ports as $port) {
            $process = new Process([$phpBin, '-S', "{$host}:{$port}", '-t', 'public', $routerScript], base_path());
            $process->setEnv(array_merge($_ENV, [
                'TMP' => $tmpProcessDir,
                'TEMP' => $tmpProcessDir,
                'TMPDIR' => $tmpProcessDir,
            ]));
            $process->disableOutput();
            $process->start();

            $started = false;
            for ($attempt = 0; $attempt < 40; $attempt++) {
                if ($this->isPortOpen($host, $port)) {
                    $started = true;
                    break;
                }

                if (! $process->isRunning()) {
                    break;
                }

                usleep(200000);
            }

            if ($started) {
                return ["http://{$host}:{$port}", $process];
            }

            if ($process->isRunning()) {
                $process->stop(1);
            }
        }

        throw new \RuntimeException('Não foi possível iniciar o servidor auxiliar de captura para exportação PPT.');
    }

    /**
     * @return list<int>
     */
    private function auxCaptureCandidatePorts(string $host): array
    {
        $ports = [];
        for ($port = 33000; $port <= 33100; $port++) {
            if (! $this->isPortOpen($host, $port)) {
                $ports[] = $port;
            }
        }

        for ($port = 2081; $port <= 2100; $port++) {
            if (! $this->isPortOpen($host, $port)) {
                $ports[] = $port;
            }
        }

        if ($ports === []) {
            throw new \RuntimeException('Nenhuma porta livre encontrada para servidor auxiliar.');
        }

        return $ports;
    }

    private function resolvePhpCliBinary(): ?string
    {
        $candidates = [];
        if (defined('PHP_BINARY')) {
            $candidates[] = PHP_BINARY;
            $candidates[] = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php.exe';
        }
        $candidates[] = 'php';

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if ($candidate === 'php') {
                return $candidate;
            }

            if (! file_exists($candidate)) {
                continue;
            }

            $name = strtolower(basename($candidate));
            if ($name === 'php-cgi.exe') {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function buildCaptureCookieHeader(string $rawCookieHeader): string
    {
        if ($rawCookieHeader === '') {
            return '';
        }

        $chunks = array_filter(array_map('trim', explode(';', $rawCookieHeader)));
        $allowed = [];

        foreach ($chunks as $chunk) {
            $eqPos = strpos($chunk, '=');
            if ($eqPos === false) {
                continue;
            }

            $name = substr($chunk, 0, $eqPos);
            if (
                str_starts_with($name, 'omega286-session')
                || str_starts_with($name, 'remember_web_')
                || $name === 'XSRF-TOKEN'
            ) {
                $allowed[] = $chunk;
            }
        }

        return implode('; ', $allowed);
    }

    private function isPortOpen(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if ($connection === false) {
            return false;
        }

        fclose($connection);

        return true;
    }

    private function resolveChromeExecutablePath(): ?string
    {
        $userProfile = getenv('USERPROFILE');
        if (! is_string($userProfile) || $userProfile === '') {
            $userProfile = 'C:\\Users\\Administrator';
        }

        $candidates = [
            env('PUPPETEER_EXECUTABLE_PATH'),
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Chromium\\Application\\chrome.exe',
        ];

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                return $path;
            }
        }

        $cacheRoot = rtrim($userProfile, '\\/').'\\.cache\\puppeteer';
        $globs = [
            $cacheRoot.'\\chrome\\win64-*\\chrome-win64\\chrome.exe',
            $cacheRoot.'\\chrome-headless-shell\\win64-*\\chrome-headless-shell-win64\\chrome-headless-shell.exe',
        ];

        foreach ($globs as $pattern) {
            $matches = glob($pattern);
            if (is_array($matches) && count($matches) > 0) {
                rsort($matches);

                return $matches[0];
            }
        }

        return null;
    }

    /**
     * @return array<string, float|int|string>
     */
    private function buildPguExecutiveSlideVars(string $contrato, string $competencia, ?string $dataLimite): array
    {
        $compLabel = $competencia;
        try {
            $compLabel = Carbon::createFromFormat('Y-m', $competencia)->format('m/Y');
        } catch (\Throwable) {
        }

        $empty = fn (string $msg) => [
            'totalFuncoes' => 0,
            'concluidas' => 0,
            'pendentes' => 0,
            'avancoGeral' => 0.0,
            'percentualFuncoesConcluidas' => 0.0,
            'percentualFuncoesPendentes' => 0.0,
            'pguInsightText' => $msg,
        ];

        if ($contrato === '') {
            return $empty('Nenhum contrato está disponível ou selecionado; não há dados de PGU para exibir.');
        }

        try {
            $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                'contrato' => $contrato,
                'competencia' => $competencia,
                'data_limite_etapa_2' => $dataLimite,
            ], fn ($v) => $v !== null && $v !== ''));
            $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);
        } catch (\Throwable) {
            return $empty('Não foi possível carregar o histograma PGU para o contrato '.$contrato.' na competência '.$compLabel.'.');
        }

        $summary = $payload['summary'] ?? [];
        $total = (int) ($summary['total_functions'] ?? 0);
        $concl = (int) ($summary['completed_functions'] ?? 0);
        $pend = max(0, $total - $concl);
        $avanco = (float) ($summary['overall_progress'] ?? 0);
        $avancoLabel = number_format($avanco, 1, ',', '').'%';

        $pctConcl = $total > 0 ? round(($concl / $total) * 100, 1) : 0.0;
        $pctPend = $total > 0 ? round(($pend / $total) * 100, 1) : 0.0;

        if ($total === 0) {
            $pguInsightText = 'Não há funções no histograma PGU para o contrato '.$contrato.' na competência '.$compLabel.'.';
        } elseif ($pend > $concl) {
            $pguInsightText = 'A maioria das funções ainda apresenta pendências, com avanço médio consolidado de '.$avancoLabel.' e '.$concl.' função(ões) integralmente concluídas.';
        } else {
            $pguInsightText = 'Avanço médio consolidado de '.$avancoLabel.', com '.$concl.' função(ões) integralmente concluídas e '.$pend.' com pendência ou PGU não informado.';
        }

        return [
            'totalFuncoes' => $total,
            'concluidas' => $concl,
            'pendentes' => $pend,
            'avancoGeral' => $avanco,
            'percentualFuncoesConcluidas' => $pctConcl,
            'percentualFuncoesPendentes' => $pctPend,
            'pguInsightText' => $pguInsightText,
        ];
    }

    /**
     * @return array<string, float|int|string|array<int, string>>
     */
    private function buildPguSlide2Vars(string $contrato, string $competencia, ?string $dataLimite): array
    {
        $empty = [
            'totalFuncoes' => 0,
            'funcoes100' => [],
            'qtdFuncoes100' => 0,
            'qtdDemaisFuncoes' => 0,
            'percentual100' => 0.0,
            'percentualDemais' => 0.0,
            'pgu2InsightText' => 'Nenhuma função com cobertura integral no recorte selecionado.',
        ];

        if ($contrato === '') {
            return $empty;
        }

        try {
            $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                'contrato' => $contrato,
                'competencia' => $competencia,
                'data_limite_etapa_2' => $dataLimite,
            ], fn ($v) => $v !== null && $v !== ''));
            $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);
        } catch (\Throwable) {
            return $empty;
        }

        $summary = $payload['summary'] ?? [];
        $total = (int) ($summary['total_functions'] ?? 0);

        $funcoes100Todas = collect($payload['funcoes_pgu_100'] ?? [])
            ->map(fn ($row) => trim((string) ($row['funcao'] ?? $row['function'] ?? '')))
            ->filter(fn ($nome) => $nome !== '')
            ->unique()
            ->values()
            ->all();

        $funcoes100 = collect($funcoes100Todas)->take(6)->all();
        $qtd100 = (int) ($summary['completed_functions'] ?? count($funcoes100Todas));
        $qtdDemais = max(0, $total - $qtd100);
        $pct100 = $total > 0 ? round(($qtd100 / $total) * 100, 1) : 0.0;
        $pctDemais = $total > 0 ? round(($qtdDemais / $total) * 100, 1) : 0.0;
        $pct100Label = number_format($pct100, 1, ',', '').'%';

        $insight = $qtd100 > 0
            ? 'As funções já com cobertura integral no PGU representam '.$pct100Label.' do total monitorado, indicando frentes estabilizadas no processo.'
            : 'Nenhuma função atingiu cobertura integral no PGU neste recorte.';

        return [
            'totalFuncoes' => $total,
            'funcoes100' => $funcoes100,
            'qtdFuncoes100' => $qtd100,
            'qtdDemaisFuncoes' => $qtdDemais,
            'percentual100' => $pct100,
            'percentualDemais' => $pctDemais,
            'pgu2InsightText' => $insight,
        ];
    }

    /**
     * @return array<string, int|string|array<int, array<string, int|string|bool>>>
     */
    private function buildPguSlide3Vars(string $contrato, string $competencia, ?string $dataLimite): array
    {
        $empty = [
            'gargalos' => [],
            'outrasFuncoes' => ['ranking' => 0, 'funcao' => 'Outras funções', 'pendencias' => 0],
            'funcoesComPendencia' => 0,
            'pgu3InsightText' => 'Sem pendências relevantes no recorte selecionado.',
        ];

        if ($contrato === '') {
            return $empty;
        }

        try {
            $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                'contrato' => $contrato,
                'competencia' => $competencia,
                'data_limite_etapa_2' => $dataLimite,
            ], fn ($v) => $v !== null && $v !== ''));
            $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);
        } catch (\Throwable) {
            return $empty;
        }

        $summary = $payload['summary'] ?? [];
        $rankingRaw = collect($payload['ranking_executivo'] ?? [])
            ->filter(fn ($row) => ((int) ($row['pending'] ?? 0)) > 0)
            ->values();

        $top5 = $rankingRaw
            ->reject(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')
            ->take(5)
            ->values();

        $gargalos = $top5->map(function ($row, $idx) {
            return [
                'ranking' => $idx + 1,
                'funcao' => trim((string) ($row['funcao'] ?? '')),
                'pendencias' => (int) ($row['pending'] ?? 0),
                'destaque' => $idx === 0,
            ];
        })->all();

        $outrasCalculadas = $rankingRaw
            ->reject(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')
            ->slice(5)
            ->sum(fn ($row) => (int) ($row['pending'] ?? 0));

        $outrasAgregado = (int) ($rankingRaw
            ->first(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')['pending'] ?? 0);

        $outras = max($outrasAgregado, (int) $outrasCalculadas);
        $maior = collect($gargalos)->sortByDesc('pendencias')->first();

        $insight = 'Sem pendências relevantes no recorte selecionado.';
        if ($maior && ($maior['pendencias'] ?? 0) > 0) {
            $insight = 'O maior gargalo está concentrado em '.$maior['funcao'].', com '.$maior['pendencias'].' pendências. As cinco principais funções concentram a maior parte do backlog e devem ser priorizadas no plano de ação.';
        }

        return [
            'gargalos' => $gargalos,
            'outrasFuncoes' => [
                'ranking' => 6,
                'funcao' => 'Outras funções',
                'pendencias' => $outras,
            ],
            'funcoesComPendencia' => (int) ($rankingRaw
                ->reject(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')
                ->count()),
            'pgu3InsightText' => $insight,
        ];
    }

    /**
     * @return array<string, int|float|string|array<int, array<string, int|string>>>
     */
    private function buildPguSlide4Vars(string $contrato, string $competencia, ?string $dataLimite): array
    {
        $empty = [
            'paretoItems' => [],
            'totalPendencias' => 0,
            'pgu4InsightText' => 'Sem dados suficientes para análise de concentração no recorte selecionado.',
        ];

        if ($contrato === '') {
            return $empty;
        }

        try {
            $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                'contrato' => $contrato,
                'competencia' => $competencia,
                'data_limite_etapa_2' => $dataLimite,
            ], fn ($v) => $v !== null && $v !== ''));
            $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);
        } catch (\Throwable) {
            return $empty;
        }

        $summary = $payload['summary'] ?? [];
        $paretoRaw = collect($payload['pareto_executivo'] ?? [])->values();
        if ($paretoRaw->isEmpty()) {
            return $empty;
        }

        $toLabelLines = function (string $funcao): array {
            $parts = preg_split('/\s+/', trim($funcao)) ?: [];
            if (count($parts) <= 2) {
                return [trim($funcao)];
            }
            $mid = (int) ceil(count($parts) / 2);

            return [
                implode(' ', array_slice($parts, 0, $mid)),
                implode(' ', array_slice($parts, $mid)),
            ];
        };

        $paretoItems = $paretoRaw->map(function ($row, $idx) use ($toLabelLines) {
            $funcao = trim((string) ($row['funcao'] ?? ''));
            $pending = (int) ($row['pending'] ?? 0);
            $tipo = (string) ($row['tipo_pendencia'] ?? '');
            $type = 'wine-light';
            if ($idx === 0) {
                $type = 'main';
            } elseif ($tipo === 'agregado' || str_contains(mb_strtolower($funcao), 'outras')) {
                $type = 'gray';
            } elseif ($idx === 1) {
                $type = 'wine-soft';
            }

            return [
                'funcao' => $funcao,
                'label_lines' => $toLabelLines($funcao),
                'pendencias' => $pending,
                'accumulated' => round((float) ($row['accumulated'] ?? 0), 1),
                'type' => $type,
            ];
        })->all();

        $totalPendencias = (int) ($summary['total_pending'] ?? collect($paretoItems)->sum('pendencias'));
        $totalTop5 = (int) collect($paretoItems)->take(5)->sum('pendencias');
        $maiorItem = collect($paretoItems)->sortByDesc('pendencias')->first();
        $concentracaoTop5 = $totalPendencias > 0 ? round(($totalTop5 / $totalPendencias) * 100, 1) : 0.0;
        $fmt = fn (float $v) => number_format($v, 1, ',', '').'%';

        $insight = 'Sem dados suficientes para análise de concentração no recorte selecionado.';
        if ($maiorItem && (int) ($maiorItem['pendencias'] ?? 0) > 0) {
            $insight = 'As cinco principais funções concentram '.$fmt($concentracaoTop5).' das pendências. O maior impacto está em '.$maiorItem['funcao'].', que sozinho responde por '.$maiorItem['pendencias'].' registros e deve ser priorizado no plano de ação.';
        }

        return [
            'paretoItems' => $paretoItems,
            'totalPendencias' => $totalPendencias,
            'pgu4InsightText' => $insight,
        ];
    }

    /**
     * @return array<string, int|string|array<int, array<string, int|string>>>
     */
    private function buildPguSlide5Vars(string $contrato, string $competencia, ?string $dataLimite): array
    {
        $empty = [
            'acoes' => [],
            'totalPendenciasPriorizadas' => 0,
            'totalFuncoesCriticas' => 0,
            'ritmoAcompanhamento' => '24h',
            'pgu5FocusText' => 'Sem funções críticas com pendências para o recorte selecionado.',
        ];

        if ($contrato === '') {
            return $empty;
        }

        try {
            $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                'contrato' => $contrato,
                'competencia' => $competencia,
                'data_limite_etapa_2' => $dataLimite,
            ], fn ($v) => $v !== null && $v !== ''));
            $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);
        } catch (\Throwable) {
            return $empty;
        }

        $ranking = collect($payload['ranking_executivo'] ?? [])
            ->reject(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')
            ->filter(fn ($row) => ((int) ($row['pending'] ?? 0)) > 0)
            ->groupBy(fn ($row) => trim((string) ($row['funcao'] ?? '')))
            ->map(function ($rows, $funcao) {
                return [
                    'funcao' => $funcao,
                    'pending' => (int) collect($rows)->sum(fn ($r) => (int) ($r['pending'] ?? 0)),
                ];
            })
            ->sortByDesc('pending')
            ->take(5)
            ->values();

        if ($ranking->isEmpty()) {
            return $empty;
        }

        $iconPool = ['tools', 'welding', 'scaffold', 'shield', 'bolt'];
        $defaultOwnerByIcon = [
            'tools' => 'Gestão PGU',
            'welding' => 'RH + Operação',
            'scaffold' => 'Mobilização',
            'shield' => 'SSMA',
            'bolt' => 'Administração',
        ];
        $defaultActionByIcon = [
            'tools' => 'Força-tarefa documental e validação diária',
            'welding' => 'Revisar pendências por colaborador',
            'scaffold' => 'Priorizar regularização por lote',
            'shield' => 'Conferência técnica dos requisitos',
            'bolt' => 'Fechar documentação pendente',
        ];

        $competenciaDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();

        $acoesCadastradasPorFuncao = ContratoPguAcaoRecomendada::query()
            ->where('contrato', $contrato)
            ->whereDate('competencia', $competenciaDate)
            ->select(['funcao', 'acao_recomendada', 'responsavel'])
            ->get()
            ->keyBy(fn ($row) => trim((string) $row->funcao));

        $acoes = $ranking->map(function ($row, $idx) use ($iconPool, $defaultOwnerByIcon, $defaultActionByIcon, $acoesCadastradasPorFuncao) {
            $icon = $iconPool[$idx] ?? 'tools';
            $pend = (int) ($row['pending'] ?? 0);
            $funcao = trim((string) ($row['funcao'] ?? ''));
            $riscoTipo = $pend >= 50 ? 'critical' : ($pend >= 30 ? 'high' : 'attention');
            $risco = $riscoTipo === 'critical' ? 'Crítico' : ($riscoTipo === 'high' ? 'Alto' : 'Atenção');
            $cadastro = $acoesCadastradasPorFuncao->get($funcao);
            $acao = trim((string) ($cadastro?->acao_recomendada ?? ''));
            $responsavel = trim((string) ($cadastro?->responsavel ?? ''));

            return [
                'funcao' => $funcao,
                'pendencias' => $pend,
                'risco' => $risco,
                'risco_tipo' => $riscoTipo,
                'acao' => $acao !== '' ? $acao : $defaultActionByIcon[$icon],
                'responsavel' => $responsavel !== '' ? $responsavel : $defaultOwnerByIcon[$icon],
                'icon' => $icon,
            ];
        })->all();

        $totalPendencias = (int) collect($acoes)->sum('pendencias');
        $totalFuncoes = count($acoes);
        $focus = 'Foco inicial nas cinco funções críticas pode destravar '.$totalPendencias.' pendências e acelerar o avanço consolidado do PGU.';

        return [
            'acoes' => $acoes,
            'totalPendenciasPriorizadas' => $totalPendencias,
            'totalFuncoesCriticas' => $totalFuncoes,
            'ritmoAcompanhamento' => '24h',
            'pgu5FocusText' => $focus,
        ];
    }
}
