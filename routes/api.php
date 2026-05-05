<?php

use App\Http\Controllers\Api\PguDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::get('/pgu/dashboard', [PguDashboardApiController::class, 'index'])->name('api.pgu.dashboard');
