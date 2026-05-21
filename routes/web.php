<?php

use App\Models\Beneficio;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContratoAcoesRecomendadasController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\ContratoHistogramaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\MedicaoContratualController;
use App\Http\Controllers\MedicaoController;
use App\Http\Controllers\MedicaoFluxoFinanceiroController;
use App\Http\Controllers\PatrimonialController;
use App\Http\Controllers\PatrimonialHistogramaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PguDashboardController;
use App\Http\Controllers\RdoController;
use App\Http\Controllers\Rh\CartaoPontoController;
use App\Http\Controllers\Rh\BeneficioColaboradorController;
use App\Http\Controllers\Rh\BeneficioController;
use App\Http\Controllers\Rh\BeneficioExtratoController;
use App\Http\Controllers\Rh\ColaboradorController;
use App\Http\Controllers\Rh\ColaboradorMovimentacaoController;
use App\Http\Controllers\Rh\DashboardController as RhDashboardController;
use App\Http\Controllers\Rh\ApuracaoPontoController;
use App\Http\Controllers\Rh\FeriadoController;
use App\Http\Controllers\Rh\FrequenciaController;
use App\Http\Controllers\Rh\JustificativaTipoController;
use App\Http\Controllers\Rh\HorarioEscalaController;
use App\Http\Controllers\Rh\IndicadoresMensaisController;
use App\Http\Controllers\Rh\RecrutamentoController;
use App\Http\Controllers\Ponto\PontoColaboradorController;
use App\Http\Controllers\Tst\TstColaboradorController;
use App\Http\Controllers\Rh\RecrutamentoMassUpdateController;
use App\Http\Controllers\SesmtController;
use App\Http\Controllers\SsmaEpiEpcController;
use App\Http\Controllers\SsmaIndicadoresMensaisController;
use App\Http\Controllers\SsmaMeioAmbienteController;
use App\Http\Controllers\SsmaPlanoAcaoController;
use App\Http\Controllers\SsmaRegistroMensalController;
use App\Http\Controllers\SsmaRegistroMensalPrazoController;
use App\Http\Controllers\SsmaRiscoController;
use App\Http\Controllers\SsmaTstAtividadeController;
use App\Http\Controllers\SsmaTstRegistroController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\VeiculoFrotaController;
use App\Http\Controllers\VeiculoManutencaoController;
use App\Http\Controllers\VeiculoTelemetriaController;
use App\Services\PguPowerPointExportService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');

Route::middleware(['installed', 'guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/esqueci-senha', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/esqueci-senha', [AuthController::class, 'resetPassword'])->name('password.update.simple');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware(['installed', 'auth'])->name('logout');

Route::middleware(['installed'])->prefix('ponto')->name('ponto.')->group(function () {
    Route::get('/', fn () => redirect()->route('ponto.identificar'))->name('home');
    Route::get('/identificar', [PontoColaboradorController::class, 'showIdentificar'])->name('identificar');
    Route::post('/identificar', [PontoColaboradorController::class, 'identificar'])->name('identificar.store');

    Route::middleware('ponto.colaborador')->group(function () {
        Route::get('/app', [PontoColaboradorController::class, 'index'])->name('index');
        Route::post('/registrar', [PontoColaboradorController::class, 'registrar'])->name('registrar');
        Route::post('/sair', [PontoColaboradorController::class, 'sair'])->name('sair');
    });
});

Route::middleware(['installed'])->prefix('registro-tst')->name('tst-campo.')->group(function () {
    Route::get('/', fn () => redirect()->route('tst-campo.identificar'))->name('home');
    Route::get('/identificar', [TstColaboradorController::class, 'showIdentificar'])->name('identificar');
    Route::post('/identificar', [TstColaboradorController::class, 'identificar'])->name('identificar.store');

    Route::middleware('tst.colaborador')->group(function () {
        Route::get('/app', [TstColaboradorController::class, 'index'])->name('index');
        Route::post('/registrar', [TstColaboradorController::class, 'store'])->name('store');
        Route::post('/sair', [TstColaboradorController::class, 'sair'])->name('sair');
    });
});

Route::middleware(['installed'])->prefix('publico')->name('publico.')->group(function () {
    Route::redirect('/', '/publico/contratos/histograma');
    Route::get('contratos/histograma', [ContratoHistogramaController::class, 'index'])->name('contratos.histograma.index');
    Route::get('contratos/pgu-visao-completa', [PguDashboardController::class, 'index'])->name('dashboard.pgu');
    Route::get('contratos/apresentacao', [PguDashboardController::class, 'apresentacao'])->name('contratos.apresentacao');
    Route::get('exportar-pgu-powerpoint', [PguDashboardController::class, 'exportarPowerPoint'])->name('pgu.export.ppt');
    Route::get('debug-pgu-powerpoint', [PguDashboardController::class, 'debugExportarPowerPoint'])->name('pgu.export.ppt.debug');
});

Route::prefix('pgu-capture')
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        Authenticate::class,
    ])
    ->group(function () {
        Route::get('/health', [PguDashboardController::class, 'captureHealth'])->name('pgu.capture.health');
        Route::get('/cover', [PguDashboardController::class, 'captureCover'])->name('pgu.capture.cover');
        Route::get('/slide-1', [PguDashboardController::class, 'captureSlide1'])->name('pgu.capture.slide1');
        Route::get('/slide-2', [PguDashboardController::class, 'captureSlide2'])->name('pgu.capture.slide2');
        Route::get('/slide-3', [PguDashboardController::class, 'captureSlide3'])->name('pgu.capture.slide3');
        Route::get('/slide-4', [PguDashboardController::class, 'captureSlide4'])->name('pgu.capture.slide4');
        Route::get('/slide-5', [PguDashboardController::class, 'captureSlide5'])->name('pgu.capture.slide5');
    });

Route::get('/debug-pgu-capture-cover', [PguDashboardController::class, 'debugCaptureCover'])
    ->name('pgu.debug.capture.cover');
Route::get('/debug-pgu-aux-server', [PguDashboardController::class, 'debugAuxServer'])
    ->name('pgu.debug.aux-server');
Route::get('/debug-pgu-last-export-files', function () {
    $base = storage_path('app/pgu-export');
    if (! is_dir($base)) {
        return response()->json([
            'ok' => false,
            'message' => 'Nenhum diretorio de exportacao encontrado.',
        ]);
    }

    $dirs = collect(File::directories($base))
        ->sortByDesc(function (string $dir) {
            return @filemtime($dir) ?: 0;
        })
        ->values();

    if ($dirs->isEmpty()) {
        return response()->json([
            'ok' => false,
            'message' => 'Nenhum diretorio de exportacao encontrado.',
        ]);
    }

    $latest = $dirs->first();
    $files = collect(File::files($latest))
        ->map(function ($file) {
            $path = $file->getPathname();
            $dimensions = null;
            if (strtolower($file->getExtension()) === 'png') {
                $img = @getimagesize($path);
                if (is_array($img)) {
                    $dimensions = [
                        'width' => (int) ($img[0] ?? 0),
                        'height' => (int) ($img[1] ?? 0),
                    ];
                }
            }

            return [
                'name' => $file->getFilename(),
                'path' => $path,
                'size' => filesize($path),
                'dimensions' => $dimensions,
            ];
        })
        ->values();

    return response()->json([
        'ok' => true,
        'latest_dir' => $latest,
        'files' => $files,
    ]);
})->name('pgu.debug.last-export-files');

Route::get('/debug-pgu-run-export', function (Request $request, PguPowerPointExportService $service) {
    $debugRequest = Request::create('/debug-pgu-run-export', 'GET', [
        'contrato' => (string) $request->query('contrato', '312'),
        'competencia' => (string) $request->query('competencia', now()->format('Y-m')),
        'data_limite_etapa_2' => $request->query('data_limite_etapa_2'),
    ]);

    $pptx = $service->export($debugRequest);
    $dir = dirname($pptx);
    $files = collect(File::files($dir))
        ->map(function ($file) {
            $path = $file->getPathname();
            $isPng = strtolower($file->getExtension()) === 'png';
            $img = $isPng ? @getimagesize($path) : null;
            $hash = @hash_file('sha256', $path) ?: null;

            return [
                'name' => $file->getFilename(),
                'size' => filesize($path),
                'sha256' => $hash,
                'dimensions' => is_array($img) ? ['width' => (int) ($img[0] ?? 0), 'height' => (int) ($img[1] ?? 0)] : null,
            ];
        })
        ->values();

    return response()->json([
        'ok' => true,
        'dir' => $dir,
        'files' => $files,
    ]);
})->name('pgu.debug.run-export');

Route::middleware(['installed', 'auth', 'perfil.rota'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('contratos/histograma', [ContratoHistogramaController::class, 'index'])->name('contratos.histograma.index');
    Route::get('contratos/acoes-recomendadas', [ContratoAcoesRecomendadasController::class, 'index'])->name('contratos.acoes-recomendadas.index');
    Route::post('contratos/acoes-recomendadas', [ContratoAcoesRecomendadasController::class, 'salvar'])->name('contratos.acoes-recomendadas.salvar');
    Route::get('contratos/histograma/status-cronograma', fn () => redirect()->route('dashboard.pgu'))->name('contratos.histograma.status-cronograma');
    Route::get('/dashboard/pgu', [PguDashboardController::class, 'index'])->name('dashboard.pgu');
    Route::get('contratos/apresentacao', [PguDashboardController::class, 'apresentacao'])->name('contratos.apresentacao');
    Route::get('/pgu-cover', function () {
        return view('dashboard.pgu-cover');
    })->name('pgu.cover');
    Route::get('/pgu-slide', [PguDashboardController::class, 'pguSlide'])->name('pgu.slide');
    Route::get('/pgu-slide-2', [PguDashboardController::class, 'pguSlide2'])->name('pgu.slide.2');
    Route::get('/pgu-slide-3', [PguDashboardController::class, 'pguSlide3'])->name('pgu.slide.3');
    Route::get('/pgu-slide-4', [PguDashboardController::class, 'pguSlide4'])->name('pgu.slide.4');
    Route::get('/pgu-slide-5', [PguDashboardController::class, 'pguSlide5'])->name('pgu.slide.5');
    Route::get('/exportar-pgu-powerpoint', [PguDashboardController::class, 'exportarPowerPoint'])->name('pgu.export.ppt');
    Route::get('/debug-pgu-powerpoint', [PguDashboardController::class, 'debugExportarPowerPoint'])->name('pgu.export.ppt.debug');
    Route::post('contratos/histograma', [ContratoHistogramaController::class, 'salvar'])->name('contratos.histograma.salvar');
    Route::resource('contratos', ContratoController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('perfis', PerfilController::class);

    Route::post('/veiculos/solicitacoes', [VeiculoController::class, 'storeSolicitacao'])->name('veiculos.solicitacoes.store');
    Route::get('/veiculos/solicitacoes/{solicitacao}/edit', [VeiculoController::class, 'editSolicitacao'])->name('veiculos.solicitacoes.edit');
    Route::put('/veiculos/solicitacoes/{solicitacao}', [VeiculoController::class, 'updateSolicitacao'])->name('veiculos.solicitacoes.update');
    Route::delete('/veiculos/solicitacoes/{solicitacao}', [VeiculoController::class, 'destroySolicitacao'])->name('veiculos.solicitacoes.destroy');
    Route::get('veiculos/frota', [VeiculoFrotaController::class, 'index'])->name('veiculos.frota.index');
    Route::resource('veiculos/manutencoes', VeiculoManutencaoController::class)
        ->except(['index', 'show'])
        ->names('veiculos.manutencoes')
        ->parameters(['manutencoes' => 'manutencao']);
    Route::resource('veiculos/telemetria', VeiculoTelemetriaController::class)
        ->except(['show'])
        ->names('veiculos.telemetria');
    Route::resource('veiculos', VeiculoController::class);
    Route::put('/veiculos/mobilizacao/{mobilizacao}', [VeiculoController::class, 'updateMobilizacao'])->name('veiculos.mobilizacao.update');
    Route::get('/sesmt', [SesmtController::class, 'index'])->name('sesmt.index');
    Route::get('/sesmt/indicadores-mensais/painel-executivo', [SsmaIndicadoresMensaisController::class, 'painelExecutivo'])
        ->name('sesmt.indicadores-mensais.painel-executivo');
    Route::get('/sesmt/indicadores-mensais', [SsmaIndicadoresMensaisController::class, 'index'])->name('sesmt.indicadores-mensais.index');
    Route::get('/sesmt/plano-acao', [SsmaPlanoAcaoController::class, 'index'])->name('sesmt.plano-acao.index');
    Route::get('/sesmt/plano-acao/novo', [SsmaPlanoAcaoController::class, 'create'])->name('sesmt.plano-acao.create');
    Route::post('/sesmt/plano-acao', [SsmaPlanoAcaoController::class, 'store'])->name('sesmt.plano-acao.store');
    Route::get('/sesmt/plano-acao/{plano}/editar', [SsmaPlanoAcaoController::class, 'edit'])->name('sesmt.plano-acao.edit');
    Route::put('/sesmt/plano-acao/{plano}', [SsmaPlanoAcaoController::class, 'update'])->name('sesmt.plano-acao.update');
    Route::delete('/sesmt/plano-acao/{plano}', [SsmaPlanoAcaoController::class, 'destroy'])->name('sesmt.plano-acao.destroy');
    Route::get('/sesmt/riscos', [SsmaRiscoController::class, 'index'])->name('sesmt.riscos.index');
    Route::get('/sesmt/riscos/novo', [SsmaRiscoController::class, 'create'])->name('sesmt.riscos.create');
    Route::post('/sesmt/riscos', [SsmaRiscoController::class, 'store'])->name('sesmt.riscos.store');
    Route::get('/sesmt/riscos/{risco}/editar', [SsmaRiscoController::class, 'edit'])->name('sesmt.riscos.edit');
    Route::put('/sesmt/riscos/{risco}', [SsmaRiscoController::class, 'update'])->name('sesmt.riscos.update');
    Route::delete('/sesmt/riscos/{risco}', [SsmaRiscoController::class, 'destroy'])->name('sesmt.riscos.destroy');
    Route::get('/sesmt/epi-epc', [SsmaEpiEpcController::class, 'index'])->name('sesmt.epi-epc.index');
    Route::get('/sesmt/epi-epc/epi/novo', [SsmaEpiEpcController::class, 'createEpi'])->name('sesmt.epi-epc.epi.create');
    Route::post('/sesmt/epi-epc/epi', [SsmaEpiEpcController::class, 'storeEpi'])->name('sesmt.epi-epc.epi.store');
    Route::get('/sesmt/epi-epc/epi/{epi}/editar', [SsmaEpiEpcController::class, 'editEpi'])->name('sesmt.epi-epc.epi.edit');
    Route::put('/sesmt/epi-epc/epi/{epi}', [SsmaEpiEpcController::class, 'updateEpi'])->name('sesmt.epi-epc.epi.update');
    Route::delete('/sesmt/epi-epc/epi/{epi}', [SsmaEpiEpcController::class, 'destroyEpi'])->name('sesmt.epi-epc.epi.destroy');
    Route::get('/sesmt/epi-epc/epc/novo', [SsmaEpiEpcController::class, 'createEpc'])->name('sesmt.epi-epc.epc.create');
    Route::post('/sesmt/epi-epc/epc', [SsmaEpiEpcController::class, 'storeEpc'])->name('sesmt.epi-epc.epc.store');
    Route::get('/sesmt/epi-epc/epc/{epc}/editar', [SsmaEpiEpcController::class, 'editEpc'])->name('sesmt.epi-epc.epc.edit');
    Route::put('/sesmt/epi-epc/epc/{epc}', [SsmaEpiEpcController::class, 'updateEpc'])->name('sesmt.epi-epc.epc.update');
    Route::delete('/sesmt/epi-epc/epc/{epc}', [SsmaEpiEpcController::class, 'destroyEpc'])->name('sesmt.epi-epc.epc.destroy');
    Route::get('/sesmt/registros-tst/atividades', [SsmaTstAtividadeController::class, 'index'])->name('sesmt.registros-tst.atividades.index');
    Route::get('/sesmt/registros-tst/atividades/novo', [SsmaTstAtividadeController::class, 'create'])->name('sesmt.registros-tst.atividades.create');
    Route::post('/sesmt/registros-tst/atividades', [SsmaTstAtividadeController::class, 'store'])->name('sesmt.registros-tst.atividades.store');
    Route::get('/sesmt/registros-tst/atividades/{atividade}/editar', [SsmaTstAtividadeController::class, 'edit'])->name('sesmt.registros-tst.atividades.edit');
    Route::put('/sesmt/registros-tst/atividades/{atividade}', [SsmaTstAtividadeController::class, 'update'])->name('sesmt.registros-tst.atividades.update');
    Route::delete('/sesmt/registros-tst/atividades/{atividade}', [SsmaTstAtividadeController::class, 'destroy'])->name('sesmt.registros-tst.atividades.destroy');
    Route::get('/sesmt/registros-tst', [SsmaTstRegistroController::class, 'index'])->name('sesmt.registros-tst.registros.index');
    Route::get('/sesmt/registros-tst/novo', [SsmaTstRegistroController::class, 'create'])->name('sesmt.registros-tst.registros.create');
    Route::post('/sesmt/registros-tst', [SsmaTstRegistroController::class, 'store'])->name('sesmt.registros-tst.registros.store');
    Route::get('/sesmt/registros-tst/fotos/{foto}', [SsmaTstRegistroController::class, 'foto'])->name('sesmt.registros-tst.fotos.show');
    Route::get('/sesmt/registros-tst/{registro}', [SsmaTstRegistroController::class, 'show'])->name('sesmt.registros-tst.registros.show');
    Route::get('/sesmt/registros-tst/{registro}/editar', [SsmaTstRegistroController::class, 'edit'])->name('sesmt.registros-tst.registros.edit');
    Route::put('/sesmt/registros-tst/{registro}', [SsmaTstRegistroController::class, 'update'])->name('sesmt.registros-tst.registros.update');
    Route::delete('/sesmt/registros-tst/{registro}', [SsmaTstRegistroController::class, 'destroy'])->name('sesmt.registros-tst.registros.destroy');
    Route::get('/sesmt/meio-ambiente', [SsmaMeioAmbienteController::class, 'index'])->name('sesmt.meio-ambiente.index');
    Route::get('/sesmt/meio-ambiente/novo', [SsmaMeioAmbienteController::class, 'create'])->name('sesmt.meio-ambiente.create');
    Route::post('/sesmt/meio-ambiente', [SsmaMeioAmbienteController::class, 'store'])->name('sesmt.meio-ambiente.store');
    Route::get('/sesmt/meio-ambiente/{ambiental}/editar', [SsmaMeioAmbienteController::class, 'edit'])->name('sesmt.meio-ambiente.edit');
    Route::put('/sesmt/meio-ambiente/{ambiental}', [SsmaMeioAmbienteController::class, 'update'])->name('sesmt.meio-ambiente.update');
    Route::delete('/sesmt/meio-ambiente/{ambiental}', [SsmaMeioAmbienteController::class, 'destroy'])->name('sesmt.meio-ambiente.destroy');
    Route::get('/sesmt/registro-mensal/prazos', [SsmaRegistroMensalPrazoController::class, 'index'])->name('sesmt.registros.prazos.index');
    Route::get('/sesmt/registro-mensal/prazos/novo', [SsmaRegistroMensalPrazoController::class, 'create'])->name('sesmt.registros.prazos.create');
    Route::post('/sesmt/registro-mensal/prazos', [SsmaRegistroMensalPrazoController::class, 'store'])->name('sesmt.registros.prazos.store');
    Route::get('/sesmt/registro-mensal/prazos/{prazo}/editar', [SsmaRegistroMensalPrazoController::class, 'edit'])->name('sesmt.registros.prazos.edit');
    Route::put('/sesmt/registro-mensal/prazos/{prazo}', [SsmaRegistroMensalPrazoController::class, 'update'])->name('sesmt.registros.prazos.update');
    Route::delete('/sesmt/registro-mensal/prazos/{prazo}', [SsmaRegistroMensalPrazoController::class, 'destroy'])->name('sesmt.registros.prazos.destroy');
    Route::get('/sesmt/registro-mensal', [SsmaRegistroMensalController::class, 'index'])->name('sesmt.registros.index');
    Route::get('/sesmt/registro-mensal/novo', [SsmaRegistroMensalController::class, 'create'])->name('sesmt.registros.create');
    Route::post('/sesmt/registro-mensal', [SsmaRegistroMensalController::class, 'store'])->name('sesmt.registros.store');
    Route::get('/sesmt/registro-mensal/{registro}/previa', [SsmaRegistroMensalController::class, 'preview'])->name('sesmt.registros.preview');
    Route::get('/sesmt/registro-mensal/{registro}/editar', [SsmaRegistroMensalController::class, 'edit'])->name('sesmt.registros.edit');
    Route::put('/sesmt/registro-mensal/{registro}', [SsmaRegistroMensalController::class, 'update'])->name('sesmt.registros.update');
    Route::post('/sesmt/sincronizar', [SesmtController::class, 'sync'])->name('sesmt.sync');
    Route::put('/sesmt/tarefas/{tarefa}', [SesmtController::class, 'update'])->name('sesmt.tarefas.update');
    Route::get('patrimonial/histograma', [PatrimonialHistogramaController::class, 'index'])->name('patrimonial.histograma.index');
    Route::post('patrimonial/histograma', [PatrimonialHistogramaController::class, 'salvar'])->name('patrimonial.histograma.salvar');
    Route::get('patrimonial/{patrimonio}/fluxo', [PatrimonialController::class, 'fluxo'])->name('patrimonial.fluxo.edit');
    Route::put('patrimonial/{patrimonio}/fluxo', [PatrimonialController::class, 'salvarFluxo'])->name('patrimonial.fluxo.update');
    Route::resource('patrimonial', PatrimonialController::class)
        ->parameters(['patrimonial' => 'patrimonio']);
    Route::get('/medicao', [MedicaoController::class, 'index'])->name('medicao.index');
    Route::resource('/medicao/contratual', MedicaoContratualController::class)
        ->except(['show'])
        ->names('medicao.contratual')
        ->parameters(['contratual' => 'contratual']);
    Route::get('/medicao/fluxo-financeiro', [MedicaoFluxoFinanceiroController::class, 'index'])->name('medicao.fluxo-financeiro.index');
    Route::get('/rdo/exportar/excel', [RdoController::class, 'exportExcel'])->name('rdo.export.excel');
    Route::get('/rdo/exportar/pdf', [RdoController::class, 'exportPdf'])->name('rdo.export.pdf');
    Route::get('/rdo/{rdo}/pdf', [RdoController::class, 'pdf'])->name('rdo.pdf');
    Route::resource('rdo', RdoController::class)->only(['index', 'create', 'store', 'show']);

    Route::prefix('rh')->name('rh.')->group(function () {
        Route::get('/', RhDashboardController::class)->name('dashboard');
        Route::get('frequencia', [FrequenciaController::class, 'index'])->name('frequencia.index');
        Route::get('frequencia/extrato-faltas', [FrequenciaController::class, 'extratoFaltas'])->name('frequencia.extrato-faltas');
        Route::get('frequencia/apuracao', [ApuracaoPontoController::class, 'index'])->name('frequencia.apuracao.index');
        Route::post('frequencia/apuracao/justificativa', [ApuracaoPontoController::class, 'aplicarJustificativa'])->name('frequencia.apuracao.justificativa');
        Route::post('frequencia/apuracao/marcacao', [ApuracaoPontoController::class, 'salvarMarcacao'])->name('frequencia.apuracao.marcacao');
        Route::post('frequencia/apuracao/{registro}/limpar', [ApuracaoPontoController::class, 'limparMarcacoes'])->name('frequencia.apuracao.limpar');
        Route::post('frequencia/apuracao/{registro}/remover-justificativa', [ApuracaoPontoController::class, 'removerJustificativa'])->name('frequencia.apuracao.remover-justificativa');
        Route::resource('frequencia/justificativa-tipos', JustificativaTipoController::class)
            ->except(['show'])
            ->parameters(['justificativa-tipos' => 'justificativa_tipo'])
            ->names([
                'index' => 'frequencia.justificativa-tipos.index',
                'create' => 'frequencia.justificativa-tipos.create',
                'store' => 'frequencia.justificativa-tipos.store',
                'edit' => 'frequencia.justificativa-tipos.edit',
                'update' => 'frequencia.justificativa-tipos.update',
                'destroy' => 'frequencia.justificativa-tipos.destroy',
            ]);
        Route::resource('frequencia/feriados', FeriadoController::class)
            ->except(['show'])
            ->parameters(['feriados' => 'feriado'])
            ->names([
                'index' => 'frequencia.feriados.index',
                'create' => 'frequencia.feriados.create',
                'store' => 'frequencia.feriados.store',
                'edit' => 'frequencia.feriados.edit',
                'update' => 'frequencia.feriados.update',
                'destroy' => 'frequencia.feriados.destroy',
            ]);
        Route::post('frequencia/importar-afd', [FrequenciaController::class, 'importarAfd'])->name('frequencia.importar-afd');
        Route::post('frequencia/importar-csv', [FrequenciaController::class, 'importarCsv'])->name('frequencia.importar-csv');
        Route::get('frequencia/exportar-afd', [FrequenciaController::class, 'exportarAfd'])->name('frequencia.exportar-afd');
        Route::get('frequencia/cartao-ponto/colaboradores', [CartaoPontoController::class, 'colaboradores'])->name('frequencia.cartao-ponto.colaboradores');
        Route::get('frequencia/cartao-ponto/pdf', [CartaoPontoController::class, 'pdf'])->name('frequencia.cartao-ponto.pdf');
        Route::post('frequencia/{registro}/marcacao', [FrequenciaController::class, 'marcacaoManual'])->name('frequencia.marcacao');
        Route::post('frequencia/{registro}/limpar-marcacoes', [FrequenciaController::class, 'limparMarcacoes'])->name('frequencia.limpar-marcacoes');
        Route::post('frequencia/{registro}/justificar', [FrequenciaController::class, 'justificar'])->name('frequencia.justificar');
        Route::resource('frequencia/horarios', HorarioEscalaController::class)
            ->except(['show'])
            ->parameters(['horarios' => 'horario_escala']);
        Route::get('recrutamento/painel-preenchimento', [RecrutamentoController::class, 'painelPreenchimento'])
            ->name('recrutamento.painel-preenchimento');
        Route::get('recrutamento/painel-preenchimento/exportar-excel', [RecrutamentoController::class, 'exportPainelPreenchimentoExcel'])
            ->name('recrutamento.painel-preenchimento.export-excel');
        Route::get('recrutamento/atualizacao-massa', [RecrutamentoMassUpdateController::class, 'index'])
            ->name('recrutamento.atualizacao-massa');
        Route::post('recrutamento/atualizacao-massa/aplicar', [RecrutamentoMassUpdateController::class, 'apply'])
            ->name('recrutamento.atualizacao-massa.aplicar');
        Route::resource('recrutamento', RecrutamentoController::class)->except('show');
        Route::get('beneficios/extrato/config', [BeneficioExtratoController::class, 'config'])->name('beneficios.extrato.config');
        Route::post('beneficios/extrato/config', [BeneficioExtratoController::class, 'salvarConfig'])->name('beneficios.extrato.config.salvar');
        Route::get('beneficios/extrato/regras', [BeneficioExtratoController::class, 'regras'])->name('beneficios.extrato.regras');
        Route::post('beneficios/extrato/regras/{beneficio}', [BeneficioExtratoController::class, 'salvarRegraBeneficio'])
            ->whereNumber('beneficio')
            ->name('beneficios.extrato.regras.salvar');
        Route::get('beneficios/extrato/gerar', [BeneficioExtratoController::class, 'gerar'])->name('beneficios.extrato.gerar');
        Route::resource('beneficios', BeneficioController::class)->except(['show']);
        // Gestão: GET e POST na mesma URL (/rh/beneficios/{id}) — {beneficio} só numérico (não captura "create")
        Route::match(['get', 'post'], 'beneficios/{beneficio}', [BeneficioController::class, 'show'])
            ->whereNumber('beneficio')
            ->name('beneficios.show');
        // Legado (URLs antigas em cache do navegador)
        Route::post('beneficios/{beneficio}/vinculos', [BeneficioColaboradorController::class, 'store'])->whereNumber('beneficio');
        Route::post('beneficios/{beneficio}/vinculos/{vinculo}', [BeneficioColaboradorController::class, 'manage'])->whereNumber('beneficio');
        Route::post('beneficios/{beneficio}/colaboradores', [BeneficioColaboradorController::class, 'store'])->whereNumber('beneficio');
        Route::post('beneficios/{beneficio}/colaboradores/{vinculo}', [BeneficioColaboradorController::class, 'manage'])->whereNumber('beneficio');
        Route::post('beneficios/{beneficio}/colaboradores/{vinculo}/salvar', [BeneficioColaboradorController::class, 'manage'])->whereNumber('beneficio');
        Route::post('beneficios/{beneficio}/colaboradores/{vinculo}/excluir', [BeneficioColaboradorController::class, 'manage'])->whereNumber('beneficio');
        Route::get('beneficios/{beneficio}/vinculos', function (Beneficio $beneficio) {
            return redirect()->route('rh.beneficios.show', $beneficio);
        })->whereNumber('beneficio');
        Route::get('beneficios/{beneficio}/colaboradores/{vinculo}/{legado?}', function (Beneficio $beneficio) {
            return redirect()->route('rh.beneficios.show', $beneficio);
        })->whereNumber('beneficio')->where('legado', 'salvar|excluir');
        Route::get('beneficios/{beneficio}/colaboradores/{vinculo}', function (Beneficio $beneficio) {
            return redirect()->route('rh.beneficios.show', $beneficio);
        })->whereNumber('beneficio');
        Route::get('efetivo/movimentacoes', [ColaboradorMovimentacaoController::class, 'index'])->name('efetivo.movimentacoes.index');
        // Gestão: GET+POST na mesma URL, sob efetivo/ (mesmo prefixo da listagem — evita 404 em /public/)
        Route::match(['get', 'post'], 'efetivo/movimentacao/{movimentacao}', [ColaboradorMovimentacaoController::class, 'editar'])
            ->whereNumber('movimentacao')
            ->name('efetivo.movimentacoes.edit');
        Route::get('movimentacoes/{movimentacao}', fn (\App\Models\ColaboradorMovimentacao $movimentacao) => redirect()->route('rh.efetivo.movimentacoes.edit', $movimentacao, 301))
            ->whereNumber('movimentacao');
        Route::get('movimentacoes/{movimentacao}/editar', fn (\App\Models\ColaboradorMovimentacao $movimentacao) => redirect()->route('rh.efetivo.movimentacoes.edit', $movimentacao, 301))
            ->whereNumber('movimentacao');
        Route::get('efetivo/{colaborador}/movimentacoes/criar', [ColaboradorMovimentacaoController::class, 'create'])
            ->whereNumber('colaborador')
            ->name('efetivo.movimentacoes.create');
        Route::post('efetivo/{colaborador}/movimentacoes', [ColaboradorMovimentacaoController::class, 'store'])
            ->whereNumber('colaborador')
            ->name('efetivo.movimentacoes.store');
        Route::get('efetivo/{colaborador}/movimentacoes/{movimentacao}/editar', function (
            \App\Models\Colaborador $colaborador,
            \App\Models\ColaboradorMovimentacao $movimentacao
        ) {
            abort_unless($movimentacao->colaborador_id === $colaborador->id, 404);

            return redirect()->route('rh.efetivo.movimentacoes.edit', $movimentacao, 301);
        })->whereNumber(['colaborador', 'movimentacao'])->name('efetivo.movimentacoes.edit.legado');
        Route::delete('efetivo/{colaborador}/movimentacoes/{movimentacao}', [ColaboradorMovimentacaoController::class, 'destroy'])
            ->whereNumber(['colaborador', 'movimentacao'])
            ->name('efetivo.movimentacoes.destroy');
        Route::get('efetivo/exportar-excel', [ColaboradorController::class, 'exportarExcel'])->name('efetivo.exportar-excel');
        Route::get('efetivo/modelo-importacao', [ColaboradorController::class, 'modeloImportacao'])->name('efetivo.modelo-importacao');
        Route::post('efetivo/importar', [ColaboradorController::class, 'importar'])->name('efetivo.importar');
        Route::post('efetivo/excluir-massa', [ColaboradorController::class, 'destroyMassa'])->name('efetivo.excluir-massa');
        Route::post('efetivo/{colaborador}/foto', [ColaboradorController::class, 'updateFoto'])->name('efetivo.foto.update');
        Route::get('efetivo/{colaborador}/foto', [ColaboradorController::class, 'showFoto'])->name('efetivo.foto.show');
        Route::resource('efetivo', ColaboradorController::class)
            ->parameters(['efetivo' => 'colaborador']);
        Route::get('indicadores-mensais/painel-executivo', [IndicadoresMensaisController::class, 'painelExecutivo'])->name('indicadores-mensais.painel-executivo');
        Route::get('indicadores-mensais', [IndicadoresMensaisController::class, 'index'])->name('indicadores-mensais.index');
    });
});
