<?php

namespace App\Support\Medicao;

use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Folha de ponto da presença na obra: P = presente, F = falta.
 */
final class PresencaObraFolhaExcelExport
{
    private const BORDO = '6F1731';

    private const PRESENTE_FILL = 'D1FAE5';

    private const PRESENTE_FONT = '047857';

    private const FALTA_FILL = 'FEE2E2';

    private const FALTA_FONT = 'B91C1C';

    /**
     * @param  Collection<int, Colaborador>  $colaboradores
     * @param  list<Carbon>  $dias
     * @param  array<int, array<string, string>>  $marcacoes
     */
    public static function download(
        Collection $colaboradores,
        array $dias,
        array $marcacoes,
        Carbon $inicio,
        Carbon $fim,
        ?string $centroCusto = null
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Presença na obra');

        $fixedCols = 4;
        $dayCount = count($dias);
        $totalCols = $fixedCols + $dayCount + 2;
        $lastCol = self::colLetter($totalCols);

        self::montarCabecalho($sheet, $lastCol, $inicio, $fim, $centroCusto, $colaboradores->count());

        $headerRow = 6;
        $subHeaderRow = 7;
        $fixedHeaders = ['Matrícula', 'Nome', 'Cargo', 'Centro de custo'];

        foreach ($fixedHeaders as $i => $label) {
            $col = self::colLetter($i + 1);
            $sheet->mergeCells("{$col}{$headerRow}:{$col}{$subHeaderRow}");
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }

        foreach ($dias as $i => $dia) {
            $col = self::colLetter($fixedCols + $i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $dia->format('d'));
            $sheet->setCellValue("{$col}{$subHeaderRow}", self::diaSemanaCurto($dia));
        }

        $totPCol = self::colLetter($fixedCols + $dayCount + 1);
        $totFCol = self::colLetter($fixedCols + $dayCount + 2);
        $sheet->mergeCells("{$totPCol}{$headerRow}:{$totPCol}{$subHeaderRow}");
        $sheet->mergeCells("{$totFCol}{$headerRow}:{$totFCol}{$subHeaderRow}");
        $sheet->setCellValue("{$totPCol}{$headerRow}", 'Tot. P');
        $sheet->setCellValue("{$totFCol}{$headerRow}", 'Tot. F');

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$subHeaderRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::BORDO],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDO],
                ],
            ],
        ]);
        $sheet->getStyle("A{$headerRow}:D{$subHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($headerRow)->setRowHeight(18);
        $sheet->getRowDimension($subHeaderRow)->setRowHeight(16);

        $row = $subHeaderRow + 1;
        foreach ($colaboradores as $index => $colaborador) {
            $sheet->setCellValue('A'.$row, $colaborador->matricula ?: '');
            $sheet->setCellValue('B'.$row, $colaborador->nome);
            $sheet->setCellValue('C'.$row, $colaborador->cargo ?: '');
            $sheet->setCellValue('D'.$row, $colaborador->centro_custo ?: '');

            $totP = 0;
            $totF = 0;

            foreach ($dias as $i => $dia) {
                $col = self::colLetter($fixedCols + $i + 1);
                $ymd = $dia->toDateString();
                $valor = $marcacoes[(int) $colaborador->id][$ymd] ?? '';

                $sheet->setCellValue("{$col}{$row}", $valor);

                if ($valor === 'P') {
                    $totP++;
                    $sheet->getStyle("{$col}{$row}")->applyFromArray(self::estiloCelula('P'));
                } elseif ($valor === 'F') {
                    $totF++;
                    $sheet->getStyle("{$col}{$row}")->applyFromArray(self::estiloCelula('F'));
                }
            }

            $sheet->setCellValue("{$totPCol}{$row}", $totP > 0 ? $totP : '');
            $sheet->setCellValue("{$totFCol}{$row}", $totF > 0 ? $totF : '');

            $fillRgb = $index % 2 === 0 ? 'FFFFFF' : 'F7E9EE';
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillRgb],
                ],
            ]);
            $sheet->getStyle("{$totPCol}{$row}:{$totFCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Calibri', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E4E4E7'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getStyle("E{$row}:{$totFCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $lastDataRow = max($subHeaderRow, $row - 1);
        if ($lastDataRow > $subHeaderRow) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$lastDataRow}");
        }

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(34);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(16);
        foreach (range($fixedCols + 1, $fixedCols + $dayCount) as $i) {
            $sheet->getColumnDimension(self::colLetter($i))->setWidth(5);
        }
        $sheet->getColumnDimension($totPCol)->setWidth(8);
        $sheet->getColumnDimension($totFCol)->setWidth(8);

        $sheet->freezePane('E'.($subHeaderRow + 1));

        $filename = 'presenca-obra-'.$inicio->format('Ymd').'-'.$fim->format('Ymd').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function montarCabecalho(
        Worksheet $sheet,
        string $lastCol,
        Carbon $inicio,
        Carbon $fim,
        ?string $centroCusto,
        int $totalColaboradores
    ): void {
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'OMEGA286 — Presença na obra (folha de ponto)');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BORDO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $periodo = 'Período: '.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y');
        if ($centroCusto !== null && trim($centroCusto) !== '') {
            $periodo .= ' · Centro de custo: '.$centroCusto;
        }
        $periodo .= ' · '.$totalColaboradores.' colaborador(es)';

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $periodo);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '431020'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
        ]);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', 'Legenda: P = Presente · F = Falta · célula vazia = sem confirmação no dia · Emitido em '.now()->format('d/m/Y H:i'));
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::BORDO], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(8);
        $sheet->getRowDimension(5)->setRowHeight(8);
    }

    /**
     * @return array<string, mixed>
     */
    private static function estiloCelula(string $tipo): array
    {
        if ($tipo === 'P') {
            return [
                'font' => ['bold' => true, 'color' => ['rgb' => self::PRESENTE_FONT], 'name' => 'Calibri'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PRESENTE_FILL]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        return [
            'font' => ['bold' => true, 'color' => ['rgb' => self::FALTA_FONT], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::FALTA_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private static function diaSemanaCurto(Carbon $dia): string
    {
        return match ((int) $dia->dayOfWeekIso) {
            1 => 'SEG',
            2 => 'TER',
            3 => 'QUA',
            4 => 'QUI',
            5 => 'SEX',
            6 => 'SAB',
            default => 'DOM',
        };
    }

    private static function colLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)).$letter;
            $col = intdiv($col, 26);
        }

        return $letter;
    }
}
