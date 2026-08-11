<?php

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $escala = HorarioEscala::query()->updateOrCreate(
            ['nome' => 'Motoristas - Micro-ônibus e Caminhonete'],
            [
                'tipo' => 'rotativa_dias_uteis',
                'ciclo_dias' => 2,
                'data_inicio_ciclo' => '2026-08-11',
                'status' => 'ativo',
            ],
        );

        HorarioEscalaDia::query()->updateOrCreate(
            [
                'horario_escala_id' => $escala->id,
                'dia_semana' => 1,
            ],
            [
                'entrada_1' => '07:30:00',
                'saida_1' => '12:00:00',
                'entrada_2' => '13:00:00',
                'saida_2' => '17:30:00',
                'almoco_livre' => false,
                'compensado' => false,
                'neutro' => false,
                'noturno' => false,
            ],
        );

        // Com data inicial em 11/08/2026, o motor atual ancora na segunda da semana.
        // Assim, terça 11/08 cai na posição 2 (offset 1).
        $duplaAOffset = 1; // 11/08: José Edivaldo + José Michael
        $duplaBOffset = 0; // 12/08: Lindelson + Ronivon

        $this->vincular($escala, ['Edivaldo', 'Michael'], $duplaAOffset);
        $this->vincular($escala, ['Lindelson', 'Ronivon'], $duplaBOffset);
    }

    public function down(): void
    {
        $escala = HorarioEscala::query()
            ->where('nome', 'Motoristas - Micro-ônibus e Caminhonete')
            ->first();

        if (! $escala) {
            return;
        }

        Colaborador::query()
            ->where('horario_escala_id', $escala->id)
            ->update([
                'horario_escala_id' => null,
                'horario_escala_ciclo_offset' => 0,
            ]);

        $escala->delete();
    }

    /**
     * @param  list<string>  $nomes
     */
    private function vincular(HorarioEscala $escala, array $nomes, int $offset): void
    {
        foreach ($nomes as $nome) {
            Colaborador::query()
                ->where('nome', 'like', "%{$nome}%")
                ->update([
                    'horario_escala_id' => $escala->id,
                    'horario_escala_ciclo_offset' => $offset,
                ]);
        }
    }
};
