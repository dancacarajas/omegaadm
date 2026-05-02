<?php

use App\Models\Veiculo;
use App\Models\VeiculoMobilizacao;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Veiculo::query()->select('id')->chunkById(100, function ($veiculos) {
            foreach ($veiculos as $veiculo) {
                VeiculoMobilizacao::firstOrCreate([
                    'veiculo_id' => $veiculo->id,
                    'etapa' => 'VEICULO',
                ]);
            }
        });
    }

    public function down(): void
    {
        VeiculoMobilizacao::query()
            ->where('etapa', 'VEICULO')
            ->delete();
    }
};
