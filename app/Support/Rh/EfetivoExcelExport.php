<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Planilha de efetivo com identidade visual Omega (bordô #6F1731).
 */
final class EfetivoExcelExport
{
    /**
     * @param  Collection<int, Colaborador>|SupportCollection<int, Colaborador>  $colaboradores
     * @param  array<string, mixed>  $resumo
     */
    public static function download(Collection|SupportCollection $colaboradores, array $resumo = []): StreamedResponse
    {
        $headers = ColaboradorCadastroExportCampos::headersPlanilhaEfetivo();
        $colCount = count($headers);
        $lastCol = self::colLetter($colCount);
        $statusColIndex = ColaboradorCadastroExportCampos::indiceColunaStatus() + 1;
        $statusColLetter = self::colLetter($statusColIndex);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Efetivo');

        self::montarCabecalhoRelatorio($sheet, $resumo, $lastCol);

        $headerRow = 6;

        foreach ($headers as $i => $label) {
            $sheet->setCellValue(self::colLetter($i + 1).$headerRow, $label);
        }

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6F1731'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '6F1731'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        $row = $headerRow + 1;
        foreach ($colaboradores as $index => $colaborador) {
            $sheet->fromArray([ColaboradorCadastroExportCampos::linhaPlanilhaEfetivo($colaborador)], null, 'A'.$row);

            $fillRgb = $index % 2 === 0 ? 'FFFFFF' : 'F7E9EE';
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillRgb],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E4E4E7'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            $statusCell = $statusColLetter.$row;
            $status = (string) $colaborador->status;
            if ($status === 'desligado') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('71717A');
            } elseif ($status === 'afastado') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('B45309');
                $sheet->getStyle($statusCell)->getFont()->setBold(true);
            } else {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('6F1731');
                $sheet->getStyle($statusCell)->getFont()->setBold(true);
            }

            $row++;
        }

        $lastDataRow = max($headerRow, $row - 1);
        if ($lastDataRow > $headerRow) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$lastDataRow}");
        }

        foreach (range(1, $colCount) as $i) {
            $letter = self::colLetter($i);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $width = $sheet->getColumnDimension($letter)->getWidth();
            if ($width > 42) {
                $sheet->getColumnDimension($letter)->setWidth(42);
            }
            if ($width < 10) {
                $sheet->getColumnDimension($letter)->setWidth(10);
            }
        }

        $sheet->freezePane('A'.($headerRow + 1));

        $filename = 'efetivo-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $resumo
     */
    private static function montarCabecalhoRelatorio(Worksheet $sheet, array $resumo, string $lastCol): void
    {
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'OMEGA286 — Cadastro de Efetivo (completo)');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1731']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Exportado em '.now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '431020'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
        ]);

        $ativos = (int) ($resumo['efetivo_operacional'] ?? $resumo['ativos'] ?? 0);
        $total = (int) ($resumo['cadastros_total'] ?? 0);
        $desligados = (int) ($resumo['desligados'] ?? 0);
        $afastados = (int) ($resumo['afastados'] ?? 0);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue(
            'A3',
            "Resumo: {$ativos} ativo(s) · {$total} cadastro(s) · {$desligados} desligado(s) · {$afastados} afastado(s) INSS"
        );
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '6F1731'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(8);
        $sheet->getRowDimension(5)->setRowHeight(8);
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
