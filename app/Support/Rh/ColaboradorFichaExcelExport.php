<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ficha individual do colaborador (campo × valor), alinhada às seções da tela de efetivo.
 */
final class ColaboradorFichaExcelExport
{
    public static function download(Colaborador $colaborador): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ficha');

        self::montarCabecalho($sheet, $colaborador);

        $row = 5;
        foreach (self::secoes($colaborador) as $secao) {
            $row = self::escreverSecao($sheet, $row, $secao['titulo'], $secao['campos']);
        }

        if ($colaborador->relationLoaded('movimentacoes') && $colaborador->movimentacoes->isNotEmpty()) {
            $row = self::escreverMovimentacoes($sheet, $row + 1, $colaborador);
        }

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(56);
        $sheet->freezePane('A5');

        $slug = Str::slug($colaborador->nome ?: 'colaborador');
        $matricula = preg_replace('/\D+/', '', (string) $colaborador->matricula) ?: $colaborador->id;
        $filename = 'ficha-cadastro-'.$matricula.'-'.$slug.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function montarCabecalho(Worksheet $sheet, Colaborador $colaborador): void
    {
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'OMEGA286 — Ficha de cadastro do colaborador');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1731']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue(
            'A2',
            ($colaborador->nome ?: 'Colaborador')
            .($colaborador->matricula ? ' · Matrícula '.$colaborador->matricula : '')
            .' · Exportado em '.now()->format('d/m/Y H:i')
        );
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '431020'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(8);
        $sheet->getRowDimension(4)->setRowHeight(8);
    }

    /**
     * @return list<array{titulo: string, campos: list<array{0: string, 1: string}>}>
     */
    private static function secoes(Colaborador $c): array
    {
        return array_map(function (array $secao) {
            $secao['campos'] = array_map(
                static fn (array $campo) => [$campo[0], $campo[1] !== '' ? $campo[1] : '—'],
                $secao['campos'],
            );

            return $secao;
        }, ColaboradorCadastroExportCampos::secoesFicha($c));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $campos
     */
    private static function escreverSecao(Worksheet $sheet, int $row, string $titulo, array $campos): int
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", $titulo);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1731']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue("A{$row}", 'Campo');
        $sheet->setCellValue("B{$row}", 'Valor');
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '6F1731'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E4E7']]],
        ]);
        $row++;

        foreach ($campos as $index => [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $fillRgb = $index % 2 === 0 ? 'FFFFFF' : 'FAFAFA';
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillRgb]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E4E7']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        return $row + 1;
    }

    private static function escreverMovimentacoes(Worksheet $sheet, int $row, Colaborador $colaborador): int
    {
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", '07 — Histórico de movimentações');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1731']],
        ]);
        $row++;

        foreach (['Data início', 'Data fim', 'Tipo', 'Situação', 'Resumo'] as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.$row, $header);
        }
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '6F1731']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7E9EE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E4E7']]],
        ]);
        $row++;

        foreach ($colaborador->movimentacoes as $mov) {
            $sheet->fromArray([
                ColaboradorCadastroExportCampos::data($mov->data_inicio) ?: '—',
                ColaboradorCadastroExportCampos::data($mov->data_fim) ?: '—',
                $mov->tipoLabel(),
                $mov->situacaoLabel(),
                $mov->resumoAlteracao(),
            ], null, 'A'.$row);
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E4E7']]],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
            ]);
            $row++;
        }

        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(48);

        return $row;
    }
}
