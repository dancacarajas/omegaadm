<?php

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $escala = HorarioEscala::query()
            ->where('nome', 'Motoristas - Micro-ônibus e Caminhonete')
            ->orWhere('nome', 'Motoristas - Micro-Ã´nibus e Caminhonete')
            ->first();

        if (! $escala) {
            return;
        }

        $escala->update([
            'nome' => 'Motoristas - Micro-ônibus e Caminhonete',
            'tipo' => 'rotativa_veiculos',
            'ciclo_dias' => 4,
            'data_inicio_ciclo' => '2026-08-11',
            'status' => 'ativo',
        ]);

        $this->vincular($escala, ['Edivaldo'], 0);
        $this->vincular($escala, ['Michael'], 1);
        $this->vincular($escala, ['Lindelson'], 2);
        $this->vincular($escala, ['Ronivon'], 3);
    }

    public function down(): void
    {
        $escala = HorarioEscala::query()
            ->where('nome', 'Motoristas - Micro-ônibus e Caminhonete')
            ->first();

        if (! $escala) {
            return;
        }

        $escala->update([
            'tipo' => 'rotativa_dias_uteis',
            'ciclo_dias' => 2,
        ]);
    }

    /**
     * @param  list<string>  $termos
     */
    private function vincular(HorarioEscala $escala, array $termos, int $offset): void
    {
        foreach ($termos as $termo) {
            Colaborador::query()
                ->where('nome', 'like', "%{$termo}%")
                ->update([
                    'horario_escala_id' => $escala->id,
                    'horario_escala_ciclo_offset' => $offset,
                ]);
        }
    }
};
