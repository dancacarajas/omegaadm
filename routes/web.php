<?php

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
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PguDashboardController;
use App\Http\Controllers\RdoController;
use App\Http\Controllers\Rh\BeneficioColaboradorController;
use App\Http\Controllers\Rh\BeneficioController;
use App\Http\Controllers\Rh\ColaboradorController;
use App\Http\Controllers\Rh\DashboardController as RhDashboardController;
use App\Http\Controllers\Rh\FrequenciaController;
use App\Http\Controllers\Rh\HorarioEscalaController;
use App\Http\Controllers\Rh\RecrutamentoController;
use App\Http\Controllers\SesmtController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\VeiculoFrotaController;
use App\Http\Controllers\VeiculoManutencaoController;
use App\Http\Controllers\VeiculoTelemetriaController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');

Route::middleware(['installed', 'guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/esqueci-senha', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/esqueci-senha', [AuthController::class, 'resetPassword'])->name('password.update.simple');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware(['installed', 'auth'])->name('logout');

Route::middleware(['installed'])->prefix('publico')->name('publico.')->group(function () {
    Route::redirect('/', '/publico/contratos/histograma');
    Route::get('contratos/histograma', [ContratoHistogramaController::class, 'index'])->name('contratos.histograma.index');
    Route::get('contratos/pgu-visao-completa', [PguDashboardController::class, 'index'])->name('dashboard.pgu');
    Route::get('contratos/apresentacao', [PguDashboardController::class, 'apresentacao'])->name('contratos.apresentacao');
    Route::get('exportar-pgu-powerpoint', [PguDashboardController::class, 'exportarPowerPoint'])->name('pgu.export.ppt');
});

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
    Route::post('/sesmt/sincronizar', [SesmtController::class, 'sync'])->name('sesmt.sync');
    Route::put('/sesmt/tarefas/{tarefa}', [SesmtController::class, 'update'])->name('sesmt.tarefas.update');
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
        Route::post('frequencia/importar-afd', [FrequenciaController::class, 'importarAfd'])->name('frequencia.importar-afd');
        Route::post('frequencia/{registro}/marcacao', [FrequenciaController::class, 'marcacaoManual'])->name('frequencia.marcacao');
        Route::post('frequencia/{registro}/justificar', [FrequenciaController::class, 'justificar'])->name('frequencia.justificar');
        Route::resource('frequencia/horarios', HorarioEscalaController::class)
            ->except(['show'])
            ->parameters(['horarios' => 'horario_escala']);
        Route::resource('recrutamento', RecrutamentoController::class)->except('show');
        Route::resource('beneficios', BeneficioController::class);
        Route::post('beneficios/{beneficio}/colaboradores', [BeneficioColaboradorController::class, 'store'])->name('beneficios.colaboradores.store');
        Route::put('beneficios/{beneficio}/colaboradores/{vinculo}', [BeneficioColaboradorController::class, 'update'])->name('beneficios.colaboradores.update');
        Route::delete('beneficios/{beneficio}/colaboradores/{vinculo}', [BeneficioColaboradorController::class, 'destroy'])->name('beneficios.colaboradores.destroy');
        Route::get('efetivo/modelo-importacao', [ColaboradorController::class, 'modeloImportacao'])->name('efetivo.modelo-importacao');
        Route::post('efetivo/importar', [ColaboradorController::class, 'importar'])->name('efetivo.importar');
        Route::resource('efetivo', ColaboradorController::class)
            ->parameters(['efetivo' => 'colaborador']);
    });
});
