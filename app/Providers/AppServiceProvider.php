<?php

namespace App\Providers;

use App\Models\Colaborador;
use App\Services\ConfiguracaoEmailService;
use App\Models\ColaboradorBeneficio;
use App\Models\ColaboradorMovimentacao;
use App\Models\SsmaAmbientalRegistro;
use App\Observers\ColaboradorObserver;
use App\Support\Rh\MovimentacaoDebugTrace;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            app(ConfiguracaoEmailService::class)->aplicarConfiguracaoRuntime();
        } catch (\Throwable) {
            // Tabela ainda não migrada ou ambiente de instalação.
        }

        Colaborador::observe(ColaboradorObserver::class);

        Route::bind('ambiental', function (string $value): SsmaAmbientalRegistro {
            return SsmaAmbientalRegistro::query()->findOrFail((int) $value);
        });

        Route::bind('vinculo', function (string $value, $route): ColaboradorBeneficio {
            $query = ColaboradorBeneficio::query()->whereKey((int) $value);

            $beneficio = $route->parameter('beneficio');
            if ($beneficio !== null) {
                $beneficioId = is_object($beneficio) ? $beneficio->getKey() : (int) $beneficio;
                $query->where('beneficio_id', $beneficioId);
            }

            $vinculo = $query->first();

            if ($vinculo === null) {
                abort(404, 'Vínculo não encontrado para este benefício.');
            }

            return $vinculo;
        });

        Route::bind('movimentacao', function (string $value, $route): ColaboradorMovimentacao {
            MovimentacaoDebugTrace::log('BIND MOVIMENTACAO', [
                'value' => $value,
                'params' => $route->parameters(),
            ]);

            $query = ColaboradorMovimentacao::query()->whereKey((int) $value);

            $colaborador = $route->parameter('colaborador');
            if ($colaborador !== null) {
                $colaboradorId = is_object($colaborador) ? $colaborador->getKey() : (int) $colaborador;
                $query->where('colaborador_id', $colaboradorId);
            }

            $movimentacao = $query->first();

            if ($movimentacao === null) {
                MovimentacaoDebugTrace::log('BIND MOVIMENTACAO FALHOU', [
                    'value' => $value,
                    'params' => $route->parameters(),
                    'filtrou_colaborador' => $colaborador !== null,
                ]);
                abort(404, 'Movimentação não encontrada para este colaborador.');
            }

            MovimentacaoDebugTrace::log('BIND MOVIMENTACAO OK', [
                'movimentacao_id' => $movimentacao->id,
                'colaborador_id' => $movimentacao->colaborador_id,
            ]);

            return $movimentacao;
        });
    }
}
