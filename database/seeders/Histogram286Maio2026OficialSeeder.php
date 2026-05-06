<?php

namespace Database\Seeders;

use App\Models\ContratoHistogramaLinha;
use App\Models\ContratoHistogramaRecorte;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remove o recorte incorreto no localhost e recria o histograma oficial (print maio/2026).
 *
 * php artisan db:seed --class=Histogram286Maio2026OficialSeeder
 */
class Histogram286Maio2026OficialSeeder extends Seeder
{
    private const CONTRATO = '286';

    private const COMPETENCIA = '2026-05-01';

    public function run(): void
    {
        $competencia = Carbon::createFromFormat('Y-m-d', self::COMPETENCIA)->startOfMonth();

        DB::transaction(function () use ($competencia) {
            ContratoHistogramaLinha::query()
                ->where('contrato', self::CONTRATO)
                ->whereDate('competencia', $competencia)
                ->delete();

            ContratoHistogramaRecorte::query()
                ->where('contrato', self::CONTRATO)
                ->whereDate('competencia', $competencia)
                ->delete();

            foreach ($this->linhasOficiais() as $row) {
                ContratoHistogramaLinha::create([
                    'contrato' => self::CONTRATO,
                    'competencia' => $competencia->toDateString(),
                    'tipo_linha' => $row['tipo_linha'],
                    'ordem' => $row['ordem'],
                    'item_codigo' => $row['item_codigo'],
                    'descricao' => $row['descricao'],
                    'unidade' => 'Unid.',
                    'mobilizacao' => 0,
                    'pre_pgu' => $row['pre_pgu'],
                    'pgu' => $row['pgu'],
                    'pos_pgu' => $row['pos_pgu'],
                    'desmobilizacao' => 0,
                ]);
            }
        });
    }

    /**
     * @return list<array{tipo_linha: string, ordem: int, item_codigo: string|null, descricao: string, pre_pgu: float, pgu: float, pos_pgu: float}>
     */
    private function linhasOficiais(): array
    {
        return [
            ['tipo_linha' => 'grupo', 'ordem' => 1, 'item_codigo' => '1', 'descricao' => 'MAO DE OBRA', 'pre_pgu' => 203, 'pgu' => 358, 'pos_pgu' => 62],
            ['tipo_linha' => 'grupo', 'ordem' => 2, 'item_codigo' => '1.1', 'descricao' => 'EQUIPE INDIRETA', 'pre_pgu' => 46.5, 'pgu' => 65.5, 'pos_pgu' => 23],
            ['tipo_linha' => 'item', 'ordem' => 3, 'item_codigo' => '1.1.1', 'descricao' => 'Gestor', 'pre_pgu' => 1, 'pgu' => 1, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 4, 'item_codigo' => '1.1.2', 'descricao' => 'Supervisor de mecânica', 'pre_pgu' => 2, 'pgu' => 4, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 5, 'item_codigo' => '1.1.3', 'descricao' => 'Supervisor de elétrica', 'pre_pgu' => 1, 'pgu' => 1, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 6, 'item_codigo' => '1.1.4', 'descricao' => 'Engenheiro de Campo', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 7, 'item_codigo' => '1.1.5', 'descricao' => 'Médico', 'pre_pgu' => 0.5, 'pgu' => 0.5, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 8, 'item_codigo' => '1.1.6', 'descricao' => 'Engenheiro de Segurança', 'pre_pgu' => 1, 'pgu' => 1, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 9, 'item_codigo' => '1.1.7', 'descricao' => 'Técnico de segurança', 'pre_pgu' => 10, 'pgu' => 16, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 10, 'item_codigo' => '1.1.8', 'descricao' => 'Técnico de planejamento', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 11, 'item_codigo' => '1.1.9', 'descricao' => 'Almoxarife', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 12, 'item_codigo' => '1.1.10', 'descricao' => 'Auxiliar Almoxarife', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 13, 'item_codigo' => '1.1.11', 'descricao' => 'Técnico de qualidade', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 14, 'item_codigo' => '1.1.12', 'descricao' => 'Encarregado Administrativo', 'pre_pgu' => 1, 'pgu' => 1, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 15, 'item_codigo' => '1.1.13', 'descricao' => 'Assistente Administrativo', 'pre_pgu' => 1, 'pgu' => 1, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 16, 'item_codigo' => '1.1.14', 'descricao' => 'Operador de caminhão Munck', 'pre_pgu' => 10, 'pgu' => 16, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 17, 'item_codigo' => '1.1.15', 'descricao' => 'Operador de Equipamentos', 'pre_pgu' => 2, 'pgu' => 4, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 18, 'item_codigo' => '1.1.16', 'descricao' => 'Técnico de materiais', 'pre_pgu' => 1, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 19, 'item_codigo' => '1.1.17', 'descricao' => 'Motorista leve', 'pre_pgu' => 6, 'pgu' => 8, 'pos_pgu' => 8],
            ['tipo_linha' => 'grupo', 'ordem' => 20, 'item_codigo' => '1.2', 'descricao' => 'EQUIPE DIRETA', 'pre_pgu' => 156, 'pgu' => 292, 'pos_pgu' => 39],
            ['tipo_linha' => 'item', 'ordem' => 21, 'item_codigo' => '1.2.1', 'descricao' => 'Encarregado Elétrica', 'pre_pgu' => 4, 'pgu' => 4, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 22, 'item_codigo' => '1.2.2', 'descricao' => 'Eletricista força controle', 'pre_pgu' => 16, 'pgu' => 16, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 23, 'item_codigo' => '1.2.3', 'descricao' => 'Eletricista Montador', 'pre_pgu' => 24, 'pgu' => 24, 'pos_pgu' => 4],
            ['tipo_linha' => 'item', 'ordem' => 24, 'item_codigo' => '1.2.4', 'descricao' => 'Ajudante', 'pre_pgu' => 18, 'pgu' => 18, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 25, 'item_codigo' => '1.2.5', 'descricao' => 'Técnico de instrumentação', 'pre_pgu' => 2, 'pgu' => 2, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 26, 'item_codigo' => '1.2.6', 'descricao' => 'Mecânico Montador', 'pre_pgu' => 16, 'pgu' => 0, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 27, 'item_codigo' => '1.2.7', 'descricao' => 'Caldereiro', 'pre_pgu' => 8, 'pgu' => 0, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 28, 'item_codigo' => '1.2.8', 'descricao' => 'Soldador Especializado', 'pre_pgu' => 8, 'pgu' => 0, 'pos_pgu' => 0],
            ['tipo_linha' => 'item', 'ordem' => 29, 'item_codigo' => '1.2.9', 'descricao' => 'Oficial de Civil', 'pre_pgu' => 8, 'pgu' => 8, 'pos_pgu' => 1],
            ['tipo_linha' => 'item', 'ordem' => 30, 'item_codigo' => '1.2.10', 'descricao' => 'Encarregado Mecânica', 'pre_pgu' => 2, 'pgu' => 12, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 31, 'item_codigo' => '1.2.10', 'descricao' => 'Encarregado Andaime', 'pre_pgu' => 2, 'pgu' => 8, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 32, 'item_codigo' => '1.2.11', 'descricao' => 'Ajudante', 'pre_pgu' => 12, 'pgu' => 48, 'pos_pgu' => 10],
            ['tipo_linha' => 'item', 'ordem' => 33, 'item_codigo' => '1.2.12', 'descricao' => 'Mecânico Ajustador', 'pre_pgu' => 2, 'pgu' => 8, 'pos_pgu' => 4],
            ['tipo_linha' => 'item', 'ordem' => 34, 'item_codigo' => '1.2.12', 'descricao' => 'Mecânico Montador', 'pre_pgu' => 18, 'pgu' => 80, 'pos_pgu' => 4],
            ['tipo_linha' => 'item', 'ordem' => 35, 'item_codigo' => '1.2.12', 'descricao' => 'Montador de Andaime', 'pre_pgu' => 8, 'pgu' => 24, 'pos_pgu' => 4],
            ['tipo_linha' => 'item', 'ordem' => 36, 'item_codigo' => '1.2.13', 'descricao' => 'Caldereiro', 'pre_pgu' => 4, 'pgu' => 16, 'pos_pgu' => 2],
            ['tipo_linha' => 'item', 'ordem' => 37, 'item_codigo' => '1.2.14', 'descricao' => 'Soldador Especializado', 'pre_pgu' => 4, 'pgu' => 24, 'pos_pgu' => 2],
        ];
    }
}
