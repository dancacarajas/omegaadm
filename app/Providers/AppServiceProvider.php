<?php

namespace App\Providers;

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
    }
}
