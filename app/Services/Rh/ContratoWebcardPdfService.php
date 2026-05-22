<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Support\Rh\DocumentoBr;
use setasign\Fpdi\Tcpdf\Fpdi;

/** @internal TCPDF sem rodapé padrão */
final class ContratoWebcardFpdi extends Fpdi
{
    public function Footer(): void {}

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->setCreator('Omega286');
        $this->setAuthor('Omega Service');
    }
}

final class ContratoWebcardPdfService
{
    private const EMPRESA = 'OMEGA SERVICOS E MONTAGENS INDUSTRAIS LTDA';

    public function render(Colaborador $colaborador, ?string $email = null): string
    {
        $pdf = new ContratoWebcardFpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setMargins(0, 0, 0);
        $pdf->setAutoPageBreak(false);

        $pdf->AddPage();
        $pdf->setSourceFile($this->caminhoPapelTimbrado());
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 210, 297);

        $pdf->SetTextColor(0, 0, 0);
        $this->desenharFormulario($pdf, $colaborador, $email);

        return $pdf->Output('', 'S');
    }

    public function nomeArquivo(Colaborador $colaborador): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($colaborador->nome ?? 'colaborador')) ?? 'colaborador';
        $mat = filled($colaborador->matricula) ? $colaborador->matricula.'-' : '';

        return 'contrato-webcard-'.$mat.trim($slug, '-').'.pdf';
    }

    private function caminhoPapelTimbrado(): string
    {
        $path = resource_path('pdf/papel-timbrado.pdf');
        abort_unless(is_file($path), 500, 'Modelo de papel timbrado não encontrado.');

        return $path;
    }

    private function desenharFormulario(ContratoWebcardFpdi $pdf, Colaborador $colaborador, ?string $email): void
    {
        $nome = $this->e(trim((string) $colaborador->nome));
        $cpf = $this->e(DocumentoBr::cpf($colaborador->cpf));
        $matricula = $this->e(filled($colaborador->matricula) ? (string) $colaborador->matricula : '—');
        $emailExibir = $this->e(filled($email) ? trim((string) $email) : '—');

        $x = $this->pt(78);
        $largura = 136.0;
        $estiloJustificado = 'font-family:dejavusans;font-size:10pt;font-weight:normal;line-height:1.45;text-align:justify;text-align-last:left;color:#000;';
        $estiloEsquerda = 'font-family:dejavusans;font-size:10pt;font-weight:normal;line-height:1.45;text-align:left;color:#000;';

        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->SetXY(0, $this->pt(88.5));
        $pdf->Cell(210, 7, 'CONTRATO DE ADESÃO WEBCARD', 0, 0, 'C');

        $y = $this->pt(132);

        $empresa = $this->e(self::EMPRESA);

        // Um único parágrafo evita linha isolada ("colaboradores") com espaços esticados.
        $y = $this->html($pdf, $x, $y, $largura, <<<HTML
<p style="{$estiloJustificado}">Pelo instrumento particular de contrato de adesão do Cartão WEBCARD a Empresa {$empresa}, aqui denominada <b>CONTRATANTE</b> e os colaboradores <b>{$nome}</b> com seu respectivo CPF <b>{$cpf}</b>, MAT: <b>{$matricula}</b> E-mail <b>{$emailExibir}</b> aqui denominado <b>CONTRATADO</b>, firmam o presente contrato mediante as cláusulas abaixo:</p>
HTML);

        $y = $this->html($pdf, $x, $y + 1, $largura, <<<HTML
<p style="{$estiloJustificado}">{$this->destacarContratanteContratado('1º - O CONTRATADO autoriza a CONTRATANTE a descontar em folha de pagamento, os valores das mercadorias e serviços utilizados por ele e por seus dependentes, devidamente autorizado através deste contrato.')}</p>
HTML);

        $y = $this->html($pdf, $x, $y, $largura, <<<HTML
<p style="{$estiloEsquerda}">{$this->destacarContratanteContratado('2º - O limite de compras e serviços será estabelecido pela CONTRATANTE.')}</p>
HTML, 'L');

        $y = $this->html($pdf, $x, $y, $largura, <<<HTML
<p style="{$estiloJustificado}">{$this->destacarContratanteContratado('3º - Em caso de afastamento, licença sem vencimento ou rescisão, a CONTRATANTE está autorizada a consignar em folha ou rescisão os valores das compras e serviços.')}</p>
HTML);

        $centro = $x + ($largura / 2);
        $larguraAssinatura = 115.0;
        $xAssin = $centro - ($larguraAssinatura / 2);

        $yEmpresa = max($y + 14, $this->pt(298));
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetXY($xAssin, $yEmpresa);
        $pdf->Cell($larguraAssinatura, 5, str_repeat('_', 34), 0, 0, 'C');
        $pdf->SetXY($xAssin, $yEmpresa + 6);
        $pdf->MultiCell($larguraAssinatura, 5, 'OMEGA SERVIÇOS E MONTAGENS INDUSTRIAIS LTDA.', 0, 'C');

        $yProf = max($pdf->GetY() + 20, $this->pt(392));
        $pdf->SetXY($xAssin, $yProf);
        $pdf->Cell($larguraAssinatura, 5, str_repeat('_', 36), 0, 0, 'C');

        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->SetXY($x, $yProf + 6);
        $pdf->MultiCell($largura, 5, trim((string) $colaborador->nome), 0, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($largura, 5, 'NOME DO PROFISSIONAL', 0, 0, 'C');
    }

    private function html(ContratoWebcardFpdi $pdf, float $x, float $y, float $largura, string $html, string $alinhamento = 'J'): float
    {
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->setCellHeightRatio(1.45);
        $pdf->writeHTMLCell($largura, 0, $x, $y, $html, 0, 1, false, true, $alinhamento, true);

        return $pdf->GetY() + 0.5;
    }

    private function e(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function destacarContratanteContratado(string $texto): string
    {
        return str_replace(
            ['CONTRATANTE', 'CONTRATADO'],
            ['<b>CONTRATANTE</b>', '<b>CONTRATADO</b>'],
            $this->e($texto)
        );
    }

    private function pt(float $points): float
    {
        return round($points * 25.4 / 72, 2);
    }
}
