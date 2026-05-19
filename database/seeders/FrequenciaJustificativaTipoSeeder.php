<?php

namespace Database\Seeders;

use App\Models\FrequenciaJustificativaTipo;
use Illuminate\Database\Seeder;

class FrequenciaJustificativaTipoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['nome' => 'Á Compensar', 'categoria' => 'compensacao', 'ordem' => 10],
            ['nome' => 'À Disposição', 'categoria' => 'justificativa', 'ordem' => 20],
            ['nome' => 'Abono', 'categoria' => 'abono', 'ordem' => 30],
            ['nome' => 'Acordo Coletivo', 'categoria' => 'justificativa', 'ordem' => 40],
            ['nome' => 'Advertência', 'categoria' => 'justificativa', 'ordem' => 50],
            ['nome' => 'Ag. Manut. Veículo', 'categoria' => 'justificativa', 'ordem' => 60],
            ['nome' => 'Ag. Mobilização', 'categoria' => 'justificativa', 'ordem' => 70],
            ['nome' => 'Aguardando Crachá', 'categoria' => 'justificativa', 'ordem' => 80],
            ['nome' => 'Ajuste Banco', 'categoria' => 'justificativa', 'ordem' => 90, 'limpa_batidas' => false],
            ['nome' => 'Ajuste Horas', 'categoria' => 'justificativa', 'ordem' => 100, 'limpa_batidas' => false],
            ['nome' => 'Alvará', 'categoria' => 'justificativa', 'ordem' => 110],
            ['nome' => 'Ambientação', 'categoria' => 'justificativa', 'ordem' => 120],
            ['nome' => 'At. Médico', 'categoria' => 'atestado', 'ordem' => 130],
            ['nome' => 'Atestado de Óbito', 'categoria' => 'atestado', 'ordem' => 140],
            ['nome' => 'Atestado Médico', 'categoria' => 'atestado', 'ordem' => 150],
            ['nome' => 'Atividade Extra', 'categoria' => 'justificativa', 'ordem' => 160, 'limpa_batidas' => false],
            ['nome' => 'Atraso Ônibus', 'categoria' => 'justificativa', 'ordem' => 170],
            ['nome' => 'Compensação Diária', 'categoria' => 'compensacao', 'ordem' => 180],
            ['nome' => 'Folga', 'categoria' => 'folga', 'ordem' => 190],
            ['nome' => 'Mobilização SGC', 'categoria' => 'abono', 'ordem' => 200],
        ];

        foreach ($tipos as $item) {
            FrequenciaJustificativaTipo::query()->updateOrCreate(
                ['nome' => $item['nome']],
                [
                    'categoria' => $item['categoria'],
                    'ordem' => $item['ordem'],
                    'limpa_batidas' => $item['limpa_batidas'] ?? true,
                    'ativo' => true,
                ]
            );
        }
    }
}
