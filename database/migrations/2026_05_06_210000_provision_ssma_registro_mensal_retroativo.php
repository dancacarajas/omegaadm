<?php

use App\Services\SsmaRegistroMensalProvisioner;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $meses = collect([
            Carbon::create(2026, 5, 1)->startOfMonth(),
            Carbon::now()->startOfMonth(),
        ])->unique(fn (Carbon $c) => $c->format('Y-m'));

        foreach ($meses as $mes) {
            SsmaRegistroMensalProvisioner::provision($mes);
        }
    }

    public function down(): void
    {
        //
    }
};
