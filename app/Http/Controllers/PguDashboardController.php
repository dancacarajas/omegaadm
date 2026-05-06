<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\PguDashboardApiController;
use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Services\PguPowerPointExportService;
use App\Models\ContratoHistogramaRecorte;
use App\Models\ContratoPguAcaoRecomendada;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
    public function exportarPowerPoint(Request $request, PguPowerPointExportService $service)
    {
        try {
            $pptxPath = $service->export($request);

            $contrato = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $request->query('contrato', 'geral'));
            $competencia = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $request->query('competencia', now()->format('Y-m')));
            $filename = "pgu-visao-executiva-{$contrato}-{$competencia}.pptx";

            return response()
                ->download($pptxPath, $filename)
                ->deleteFileAfterSend(! config('pgu_export.keep_files'));
        } catch (\Throwable $e) {
            Log::error('Erro ao exportar PowerPoint PGU', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Falha ao exportar PowerPoint PGU.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Falha ao exportar PowerPoint PGU: '.$e->getMessage());
        }
    }

    public function debugExportarPowerPoint(Request $request, PguPowerPointExportService $service)
    {
        try {
            return response()->json($service->debugCapture($request));
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function captureHealth()
    {
        return response('OK', 200);
    }

    public function captureCover(Request $request)
    {
        $this->mergeCapturePayloadIntoRequest($request);

        return $this->renderCapturePartial('dashboard.partials.pgu-cover-wrap', [], 'pgu0-apresentacao-embed w-full max-w-none px-0');
    }

    public function captureSlide1(Request $request)
    {
        $request = $this->mergeCapturePayloadIntoRequest($request);

        $contrato = (string) $request->query('contrato', '');
        $competencia = (string) $request->query('competencia', now()->format('Y-m'));
        $dataLimite = $request->query('data_limite_etapa_2');

        return $this->renderCapturePartial(
            'dashboard.partials.pgu-executive-slide-wrap',
            $this->buildPguExecutiveSlideVars($contrato, $competencia, $dataLimite),
            'pgu-apresentacao-embed w-full max-w-none px-0'
        );
    }

    public function captureSlide2(Request $request)
    {
        $request = $this->mergeCapturePayloadIntoRequest($request);

        $contrato = (string) $request->query('contrato', '');
        $competencia = (string) $request->query('competencia', now()->format('Y-m'));
        $dataLimite = $request->query('data_limite_etapa_2');

        return $this->renderCapturePartial(
            'dashboard.partials.pgu-slide-2-wrap',
            $this->buildPguSlide2Vars($contrato, $competencia, $dataLimite),
            'pgu2-apresentacao-embed w-full max-w-none px-0'
        );
    }

    public function captureSlide3(Request $request)
    {
        $request = $this->mergeCapturePayloadIntoRequest($request);

        $contrato = (string) $request->query('contrato', '');
        $competencia = (string) $request->query('competencia', now()->format('Y-m'));
        $dataLimite = $request->query('data_limite_etapa_2');

        return $this->renderCapturePartial(
            'dashboard.partials.pgu-slide-3-wrap',
            $this->buildPguSlide3Vars($contrato, $competencia, $dataLimite),
            'pgu3-apresentacao-embed w-full max-w-none px-0'
        );
    }

    public function captureSlide4(Request $request)
    {
        $request = $this->mergeCapturePayloadIntoRequest($request);

        $contrato = (string) $request->query('contrato', '');
        $competencia = (string) $request->query('competencia', now()->format('Y-m'));
        $dataLimite = $request->query('data_limite_etapa_2');

        return $this->renderCapturePartial(
            'dashboard.partials.pgu-slide-4-wrap',
            $this->buildPguSlide4Vars($contrato, $competencia, $dataLimite),
            'pgu4-apresentacao-embed w-full max-w-none px-0'
        );
    }

    public function captureSlide5(Request $request)
    {
        $request = $this->mergeCapturePayloadIntoRequest($request);

        $contrato = (string) $request->query('contrato', '');
        $competencia = (string) $request->query('competencia', now()->format('Y-m'));
        $dataLimite = $request->query('data_limite_etapa_2');

        return $this->renderCapturePartial(
            'dashboard.partials.pgu-slide-5-wrap',
            $this->buildPguSlide5Vars($contrato, $competencia, $dataLimite),
            'pgu5-apresentacao-embed w-full max-w-none px-0'
        );
    }

    public function debugCaptureCover(Request $request, PguPowerPointExportService $service)
    {
        try {
            return response()->json($service->debugCapture($request));
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function debugAuxServer(PguPowerPointExportService $service)
    {
        try {
            return response()->json($service->debugAuxServer());
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function validatePguCaptureToken(Request $request): array
    {
        $token = (string) $request->query('capture_token', '');
        if ($token === '') {
            abort(403, 'Token de captura ausente.');
        }

        $payload = Cache::get("pgu_capture_token:{$token}");
        if (! is_array($payload)) {
            abort(403, 'Token de captura invalido ou expirado.');
        }

        return $payload;
    }

    private function mergeCapturePayloadIntoRequest(Request $request): Request
    {
        $payload = $this->validatePguCaptureToken($request);

        foreach ($payload as $key => $value) {
            if ($value !== null && $value !== '') {
                $request->query->set((string) $key, $value);
            }
        }

        return $request;
    }

    private function renderCapturePartial(string $partialView, array $data = [], string $wrapperClass = '')
    {
        return response()->view('dashboard.pgu-capture-frame', [
            'capturePartialView' => $partialView,
            'captureData' => $data,
            'captureWrapperClass' => $wrapperClass,
        ]);
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
