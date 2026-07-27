<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Carbon\Carbon;
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
        $statusLabel = match ($c->status) {
            'ativo' => 'Ativo',
            'afastado' => 'Afastado INSS',
            'desligado' => 'Desligado',
            default => $c->status ? ucfirst((string) $c->status) : '',
        };

        $mobilizacaoLabel = match ($c->mobilizacao_status) {
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
            'pendente' => 'Pendente',
            default => $c->mobilizacao_status ? ucfirst(str_replace('_', ' ', (string) $c->mobilizacao_status)) : 'Pendente',
        };

        return [
            [
                'titulo' => '01 — Identificação',
                'campos' => [
                    ['Nome completo', self::txt($c->nome)],
                    ['Matrícula', self::txt($c->matricula)],
                    ['CPF', self::txt($c->cpf)],
                    ['RG', self::txt($c->rg)],
                    ['Telefone', self::txt($c->telefone)],
                    ['E-mail', self::txt($c->email)],
                    ['Status no sistema', $statusLabel],
                    ['Presença na obra liberada', $c->presenca_obra_liberado ? 'Sim' : 'Não'],
                ],
            ],
            [
                'titulo' => '02 — Dados pessoais',
                'campos' => [
                    ['Data de nascimento', self::data($c->data_nascimento)],
                    ['Local de nascimento', self::txt($c->local_nascimento)],
                    ['UF de nascimento', self::txt($c->uf_nascimento)],
                    ['Nacionalidade', self::txt($c->nacionalidade)],
                    ['Estado civil', self::txt($c->estado_civil)],
                    ['Cônjuge', self::txt($c->conjuge)],
                    ['Sexo', self::txt($c->sexo)],
                    ['Cor', self::txt($c->cor)],
                    ['Grau de instrução', self::txt($c->grau_instrucao)],
                    ['Nome do pai', self::txt($c->filiacao_pai)],
                    ['Nome da mãe', self::txt($c->filiacao_mae)],
                ],
            ],
            [
                'titulo' => '03 — Documentos e endereço',
                'campos' => [
                    ['Carteira profissional', self::txt($c->carteira_profissional)],
                    ['Série CTPS', self::txt($c->serie_ctps)],
                    ['Data CTPS', self::data($c->data_ctps)],
                    ['Vencimento CTPS', self::data($c->vencimento_ctps)],
                    ['PIS', self::txt($c->pis)],
                    ['Título de eleitor', self::txt($c->titulo_eleitor)],
                    ['Zona / Seção', collect([$c->zona_eleitoral, $c->secao_eleitoral])->filter()->join(' / ') ?: '—'],
                    ['Identidade', self::txt($c->carteira_identidade)],
                    ['Emissão identidade', self::data($c->emissao_identidade)],
                    ['Órgão emissor', self::txt($c->orgao_emissor)],
                    ['Endereço', trim(collect([$c->endereco, $c->numero])->filter()->join(', ')) ?: '—'],
                    ['Bairro', self::txt($c->bairro)],
                    ['Cidade / UF', trim(collect([$c->cidade, $c->estado])->filter()->join(' — ')) ?: '—'],
                    ['CEP', self::txt($c->cep)],
                ],
            ],
            [
                'titulo' => '04 — Contrato e jornada',
                'campos' => [
                    ['Tipo de contrato', self::txt($c->tipo_contrato)],
                    ['Centro de custo', self::txt($c->centro_custo)],
                    ['Cargo', self::txt($c->cargo)],
                    ['CBO', self::txt($c->cbo)],
                    ['Departamento', self::txt($c->departamento)],
                    ['Jornada semanal', self::txt($c->jornada_semanal)],
                    ['Horário (texto)', self::txt($c->horario)],
                    ['Escala cadastrada', self::txt($c->horarioEscala?->nome)],
                    ['Local de trabalho', self::txt($c->local_trabalho)],
                ],
            ],
            [
                'titulo' => '05 — Admissão e contatos',
                'campos' => [
                    ['Data de admissão', self::data($c->data_admissao)],
                    ['Opção FGTS', self::data($c->data_opcao_fgts)],
                    ['Data de demissão', self::data($c->data_demissao)],
                    ['Forma de pagamento', self::txt($c->forma_pagamento)],
                    ['Salário inicial', MoedaBr::format(filled($c->salario_inicial) ? (float) $c->salario_inicial : null) ?: '—'],
                    ['Almoço', self::txt($c->almoco)],
                    ['Dependentes', self::txt($c->dependentes)],
                    ['Emergência — Nome', self::txt($c->contato_emergencia_nome)],
                    ['Emergência — Telefone', self::txt($c->contato_emergencia_telefone)],
                    ['Emergência — Parentesco', self::txt($c->contato_emergencia_parentesco)],
                    ['Observações', self::txt($c->observacoes)],
                ],
            ],
            [
                'titulo' => '06 — Mobilização SGC Vale',
                'campos' => [
                    ['Status', $mobilizacaoLabel],
                    ['Data postagem SGC', self::data($c->sgc_data_postagem)],
                    ['Nº solicitação', self::txt($c->sgc_numero_solicitacao)],
                    ['Data aprovação', self::data($c->sgc_data_aprovacao)],
                    ['Entrega do crachá', self::data($c->sgc_data_entrega_cracha)],
                    ['Observações SGC', self::txt($c->sgc_observacoes)],
                ],
            ],
        ];
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
                self::data($mov->data_inicio),
                self::data($mov->data_fim),
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

    private static function txt(mixed $value): string
    {
        return filled($value) ? trim((string) $value) : '—';
    }

    private static function data(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
