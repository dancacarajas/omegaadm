<?php

namespace App\Support\Almoxarifado;

use App\Models\Almoxarifado\MobilizacaoMaterial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MobilizacaoMateriaisExcelExport
{
    /** @var list<string> */
    private const HEADERS = [
        'Status',
        'Ação do dia',
        'Prioridade',
        'Contrato',
        'Disciplina',
        'Categoria',
        'Situação',
        'Situação SIGO',
        'Código',
        'Material',
        'Unidade',
        'Qtd necessária',
        'Qtd pedida SIGO',
        'Qtd em compra',
        'Qtd recebida',
        'Falta comprar',
        'Falta receber',
        'PM',
        'OC',
        'Fornecedor',
        'Comprador',
        'Previsão entrega',
        'Observação almoxarife',
        'Observação gestão',
    ];

    /**
     * @param  Collection<int, MobilizacaoMaterial>|SupportCollection<int, MobilizacaoMaterial>  $itens
     */
    public static function download(Collection|SupportCollection $itens, string $titulo = 'Mobilização de materiais'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Materiais');

        $sheet->setCellValue('A1', $titulo);
        $sheet->mergeCells('A1:U1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('6F1731');

        $headerRow = 3;
        foreach (self::HEADERS as $i => $label) {
            $col = self::colLetter($i + 1);
            $sheet->setCellValue($col.$headerRow, $label);
            $sheet->getStyle($col.$headerRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1731']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $row = $headerRow + 1;
        $labels = MobilizacaoMaterialStatus::labels();
        $prioLabels = MobilizacaoMaterialPrioridade::labels();

        foreach ($itens as $item) {
            $item->loadMissing(['contrato', 'categoria']);
            $values = [
                $labels[$item->status] ?? $item->status,
                $item->acao_do_dia,
                $prioLabels[$item->prioridade] ?? $item->prioridade,
                $item->contrato?->numero ?? '',
                $item->disciplina ?? '',
                $item->categoria_descricao ?? $item->categoria?->nome ?? '',
                $item->situacao_tratativa ?? '',
                $item->situacao_sigo_descricao ?? '',
                $item->codigo_material,
                $item->descricao_material,
                $item->unidade_medida,
                $item->quantidade_necessaria,
                $item->quantidade_pedida_sigo,
                $item->quantidade_em_compra,
                $item->quantidade_recebida,
                $item->saldo_a_comprar,
                $item->saldo_a_receber,
                $item->numero_pm,
                $item->numero_oc,
                $item->fornecedor,
                $item->comprador_responsavel,
                $item->previsao_entrega?->format('d/m/Y'),
                $item->observacao_almoxarife,
                $item->observacao_gestao,
            ];
            foreach ($values as $i => $value) {
                $sheet->setCellValue(self::colLetter($i + 1).$row, $value);
            }
            $row++;
        }

        foreach (range(1, count(self::HEADERS)) as $i) {
            $sheet->getColumnDimension(self::colLetter($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'mobilizacao-materiais-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
