<?php

namespace App\Providers;

use App\Models\ColaboradorBeneficio;
use App\Models\SsmaAmbientalRegistro;
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
    }
}
