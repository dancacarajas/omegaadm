<?php

use App\Http\Controllers\Rh\ColaboradorController;
use App\Http\Controllers\Rh\BeneficioController;
use App\Http\Controllers\Rh\BeneficioColaboradorController;
use App\Http\Controllers\Rh\DashboardController as RhDashboardController;
use App\Http\Controllers\Rh\FrequenciaController;
use App\Http\Controllers\Rh\RecrutamentoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PatrimonialController;
use App\Http\Controllers\RdoController;
use App\Http\Controllers\SesmtController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VeiculoController;
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

Route::middleware(['installed', 'auth'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('contratos', ContratoController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('perfis', PerfilController::class);

    Route::post('/veiculos/solicitacoes', [VeiculoController::class, 'storeSolicitacao'])->name('veiculos.solicitacoes.store');
    Route::get('/veiculos/solicitacoes/{solicitacao}/edit', [VeiculoController::class, 'editSolicitacao'])->name('veiculos.solicitacoes.edit');
    Route::put('/veiculos/solicitacoes/{solicitacao}', [VeiculoController::class, 'updateSolicitacao'])->name('veiculos.solicitacoes.update');
    Route::delete('/veiculos/solicitacoes/{solicitacao}', [VeiculoController::class, 'destroySolicitacao'])->name('veiculos.solicitacoes.destroy');
    Route::resource('veiculos', VeiculoController::class);
    Route::put('/veiculos/mobilizacao/{mobilizacao}', [VeiculoController::class, 'updateMobilizacao'])->name('veiculos.mobilizacao.update');
    Route::get('/sesmt', [SesmtController::class, 'index'])->name('sesmt.index');
    Route::post('/sesmt/sincronizar', [SesmtController::class, 'sync'])->name('sesmt.sync');
    Route::put('/sesmt/tarefas/{tarefa}', [SesmtController::class, 'update'])->name('sesmt.tarefas.update');
    Route::resource('patrimonial', PatrimonialController::class)
        ->parameters(['patrimonial' => 'patrimonio']);
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
