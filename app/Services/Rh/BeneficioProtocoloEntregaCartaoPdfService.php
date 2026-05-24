<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\ColaboradorBeneficio;
use App\Support\Rh\ProtocoloWebcardPdfIcones;
use Illuminate\Support\Collection;

/** @internal TCPDF sem rodapé padrão */
final class BeneficioProtocoloEntregaCartaoFpdi extends \setasign\Fpdi\Tcpdf\Fpdi
{
    public function Footer(): void {}
}

/**
 * Layout A4 com coordenadas sequenciais (mm) — evita sobreposição de blocos.
 */
final class BeneficioProtocoloEntregaCartaoPdfService
{
    private const EMPRESA_ROTULO = 'OMEGA SERVICE';

    private const LINHAS_PRIMEIRA_PAGINA = 2;

    private const LINHAS_CONTINUACAO = 10;

    private const PAGE_W = 210.0;

    private const MARGIN_L = 18.0;

    private const CONTENT_W = 174.0;

    private const TABLE_L = 14.0;

    private const TABLE_W = 182.0;

    private const TABLE_HEAD_H = 12.0;

    private const TABLE_ROW_H = 13.0;

    private const GAP = 4.0;

    private const COL_NUM = 11.0;

    private const COL_COLAB = 68.0;

    private const COL_CONTRATO = 28.0;

    private const COL_DATA = 40.0;

    private const COL_ASS = 35.0;

    private const CONFIRM_H = 22.0;

    private const RESP_H = 52.0;

    private const FOOTER_ZONE_TOP = 268.0;

    /** @var array<string, string|null> */
    private array $icones = [];

    /**
     * @param  Collection<int, ColaboradorBeneficio>  $vinculos
     */
    public function render(
        Beneficio $beneficio,
        Collection $vinculos,
        ?string $entregadorNome = null,
        ?string $entregadorFuncao = null,
    ): string {
        $vinculos = $vinculos
            ->filter(fn (ColaboradorBeneficio $v) => $v->colaborador !== null)
            ->sortBy(fn (ColaboradorBeneficio $v) => mb_strtolower((string) $v->colaborador->nome))
            ->values();

        abort_if($vinculos->isEmpty(), 422, 'Selecione ao menos um colaborador para gerar o protocolo.');

        $linhas = $this->montarLinhas($vinculos);
        $contrato = $this->contratoRepresentativo($vinculos);
        $beneficioNome = trim((string) $beneficio->nome);
        $subtituloCartao = $beneficioNome !== '' ? mb_strtoupper($beneficioNome) : 'WEBCARD';
        $tituloDocumento = $beneficioNome !== ''
            ? 'PROTOCOLO DE RECEBIMENTO DE CARTÃO '.mb_strtoupper($beneficioNome)
            : 'PROTOCOLO DE RECEBIMENTO DE CARTÃO';

        $this->icones = ProtocoloWebcardPdfIcones::caminhosExistentes();
        $checkImg = $this->icones['check'] ?? null;

        $pdf = new BeneficioProtocoloEntregaCartaoFpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setMargins(0, 0, 0);
        $pdf->setAutoPageBreak(false);

        $paginas = $this->paginarLinhas($linhas);
        $totalPaginas = count($paginas);
        $ano = now()->format('Y');

        foreach ($paginas as $indice => $linhasPagina) {
            $pdf->AddPage();
            $this->aplicarPapelTimbrado($pdf);

            $primeira = $indice === 0;
            $ultima = $indice === $totalPaginas - 1;

            if ($primeira) {
                $y = $this->desenharPaginaPrincipal(
                    $pdf,
                    $subtituloCartao,
                    $contrato,
                    $tituloDocumento,
                    $linhasPagina,
                    $ano,
                    $ultima,
                    $checkImg,
                    $entregadorNome,
                    $entregadorFuncao,
                );
            } else {
                $y = 42.0;
                $y = $this->desenharContinuacao($pdf, $tituloDocumento, $y);
                $y = $this->desenharTabela($pdf, $linhasPagina, $ano, $y + self::GAP);
                if ($ultima) {
                    $y = $this->desenharConfirmacao($pdf, $checkImg, $y + self::GAP);
                    $this->desenharResponsavel($pdf, $entregadorNome, $entregadorFuncao, $ano, $y + self::GAP);
                }
            }
        }

        return $pdf->Output('', 'S');
    }

    public function nomeArquivo(Beneficio $beneficio, int $quantidade): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($beneficio->nome ?? 'beneficio')) ?: 'beneficio';

        return 'protocolo-entrega-'.trim($slug, '-').'-'.$quantidade.'-colab-'.now()->format('Ymd_His').'.pdf';
    }

    /**
     * @param  list<array{numero: int, nome: string, contrato: string}>  $linhas
     */
    private function desenharPaginaPrincipal(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        string $subtituloCartao,
        string $contrato,
        string $tituloDocumento,
        array $linhas,
        string $ano,
        bool $ultima,
        ?string $checkImg,
        ?string $entregadorNome,
        ?string $entregadorFuncao,
    ): float {
        $y = 40.0;
        $y = $this->desenharTitulos($pdf, $y, $subtituloCartao);
        $y = $this->desenharInfo($pdf, $y + self::GAP, $contrato, $tituloDocumento);
        $y = $this->desenharDeclaracao($pdf, $y + self::GAP);
        $y = $this->desenharTabela($pdf, $linhas, $ano, $y + self::GAP);

        if ($ultima) {
            $y = $this->desenharConfirmacao($pdf, $checkImg, $y + self::GAP);
            $this->desenharResponsavel($pdf, $entregadorNome, $entregadorFuncao, $ano, $y + self::GAP);
        }

        return $y;
    }

    private function desenharTitulos(BeneficioProtocoloEntregaCartaoFpdi $pdf, float $y, string $subtituloCartao): float
    {
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetFont('helvetica', '', 20);
        $pdf->SetXY(0, $y);
        $pdf->Cell(self::PAGE_W, 7, 'Protocolo de Recebimento', 0, 0, 'C');

        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetXY(0, $y + 9);
        $pdf->Cell(self::PAGE_W, 9, 'de Cartão '.$subtituloCartao, 0, 0, 'C');

        return $y + 20;
    }

    private function desenharInfo(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        float $y,
        string $contrato,
        string $tituloDocumento,
    ): float {
        $rowH = 12.0;
        $this->desenharLinhaInfo($pdf, $y, $this->icones['empresa'] ?? null, 'Empresa:', self::EMPRESA_ROTULO, 'E');
        $this->desenharLinhaInfo($pdf, $y + $rowH, $this->icones['contrato'] ?? null, 'Contrato:', $contrato, 'C');
        $this->desenharLinhaInfo($pdf, $y + (2 * $rowH), $this->icones['documento'] ?? null, 'Documento:', $tituloDocumento, 'D');

        return $y + (3 * $rowH) + 1;
    }

    private function desenharIconeEmCaixa(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        float $x,
        float $y,
        float $largura,
        float $altura,
        ?string $caminhoIcone,
        ?string $fallbackLetra = null,
    ): void {
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->SetLineWidth(0.3);
        $pdf->RoundedRect($x, $y, $largura, $altura, 1.2, '1111', 'D');

        if ($caminhoIcone !== null) {
            $pad = 1.0;
            $pdf->Image($caminhoIcone, $x + $pad, $y + $pad, $largura - (2 * $pad), $altura - (2 * $pad));
        } elseif ($fallbackLetra !== null) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(17, 17, 17);
            $pdf->SetXY($x, $y + 0.6);
            $pdf->Cell($largura, $altura - 0.5, $fallbackLetra, 0, 0, 'C');
        }
    }

    private function desenharLinhaInfo(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        float $top,
        ?string $caminhoIcone,
        string $rotulo,
        string $valor,
        ?string $fallbackLetra = null,
    ): void {
        $this->desenharIconeEmCaixa($pdf, self::MARGIN_L, $top + 1.5, 9, 8, $caminhoIcone, $fallbackLetra);

        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetXY(self::MARGIN_L + 14, $top + 2.5);
        $pdf->Cell(32, 5, $rotulo, 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 10.5);
        $pdf->SetXY(self::MARGIN_L + 48, $top + 2.5);
        $pdf->Cell(120, 5, $valor, 0, 0, 'L');

        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(self::MARGIN_L, $top + 12, self::MARGIN_L + self::CONTENT_W, $top + 12);
    }

    private function desenharDeclaracao(BeneficioProtocoloEntregaCartaoFpdi $pdf, float $y): float
    {
        $texto = 'Declaro, para os devidos fins, que recebi o cartão disponibilizado pela empresa, estando ciente de que o referido cartão ficará sob minha responsabilidade a partir da data de recebimento registrada neste protocolo.';

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetXY(self::MARGIN_L, $y);
        $pdf->MultiCell(self::CONTENT_W, 5.2, $texto, 0, 'L', false, 1);

        return $pdf->GetY() + 2;
    }

    /**
     * @param  list<array{numero: int, nome: string, contrato: string}>  $linhas
     */
    private function desenharTabela(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        array $linhas,
        string $ano,
        float $top,
    ): float {
        if ($linhas === []) {
            return $top;
        }

        $pdf->SetDrawColor(17, 17, 17);
        $pdf->SetTextColor(17, 17, 17);
        $x = self::TABLE_L;
        $y = $top;
        $cols = [self::COL_NUM, self::COL_COLAB, self::COL_CONTRATO, self::COL_DATA, self::COL_ASS];

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetLineWidth(0.35);
        $cx = $x;
        $headers = [
            ['text' => 'Nº', 'lines' => 1],
            ['text' => 'COLABORADOR', 'lines' => 1],
            ['text' => 'CONTRATO', 'lines' => 1],
            ['text' => 'RECEBIMENTO', 'lines' => 1],
            ['text' => 'ASSINATURA', 'lines' => 1],
        ];

        foreach ($cols as $i => $w) {
            $pdf->Rect($cx, $y, $w, self::TABLE_HEAD_H, 'D');
            $pdf->SetXY($cx + 1, $y + ($headers[$i]['lines'] === 2 ? 1 : 2.5));
            $pdf->MultiCell($w - 2, 4, $headers[$i]['text'], 0, 'C', false, 1);
            $cx += $w;
        }

        $y += self::TABLE_HEAD_H;
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetLineWidth(0.25);
        $pdf->SetDrawColor(100, 100, 100);

        foreach ($linhas as $linha) {
            $cx = $x;
            $cells = [
                (string) $linha['numero'],
                $linha['nome'],
                $linha['contrato'],
                '_____/_____/'.$ano,
                '',
            ];
            $aligns = ['C', 'L', 'C', 'C', 'C'];

            foreach ($cols as $i => $w) {
                $pdf->Rect($cx, $y, $w, self::TABLE_ROW_H, 'D');
                $pdf->SetXY($cx + 1.5, $y + 3.5);
                $pdf->Cell($w - 3, 5, $cells[$i], 0, 0, $aligns[$i]);
                $cx += $w;
            }
            $y += self::TABLE_ROW_H;
        }

        return $y;
    }

    private function desenharConfirmacao(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        ?string $checkImg,
        float $top,
    ): float {
        $left = self::MARGIN_L;
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->SetLineWidth(0.35);
        $this->desenharIconeEmCaixa($pdf, $left, $top, 14, 14, $checkImg);

        $texto = 'Declaro ainda que conferi o recebimento do cartão acima identificado e assumo a responsabilidade pelo seu uso, guarda e conservação, conforme as orientações da empresa.';

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetXY($left + 18, $top + 1.5);
        $pdf->MultiCell(self::CONTENT_W - 20, 5.2, $texto, 0, 'L', false, 1);

        return max($pdf->GetY() + 2, $top + self::CONFIRM_H);
    }

    private function desenharResponsavel(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        ?string $nome,
        ?string $funcao,
        string $ano,
        float $top,
    ): float {
        if ($top + self::RESP_H > self::FOOTER_ZONE_TOP) {
            $top = self::FOOTER_ZONE_TOP - self::RESP_H - 2;
        }

        $left = self::TABLE_L;
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->SetLineWidth(0.35);
        $pdf->Rect($left, $top, self::TABLE_W, self::RESP_H, 'D');

        $this->desenharIconeEmCaixa($pdf, $left + 6, $top + 5, 8, 8, $this->icones['pessoa'] ?? null, 'P');

        $pdf->SetFont('helvetica', 'B', 11.5);
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetXY($left + 16, $top + 7);
        $pdf->Cell(120, 5, 'RESPONSÁVEL PELA ENTREGA', 0, 0, 'L');
        $pdf->Line($left + 16, $top + 12.5, $left + 96, $top + 12.5);

        $nomeTxt = filled($nome) ? trim((string) $nome) : '';
        $funcaoTxt = filled($funcao) ? trim((string) $funcao) : '';

        $this->desenharCampoResponsavel($pdf, $left + 8, $top + 18, 'Nome:', $nomeTxt);
        $this->desenharCampoResponsavel($pdf, $left + 8, $top + 28, 'Cargo/Função:', $funcaoTxt);
        $this->desenharCampoResponsavel($pdf, $left + 8, $top + 38, 'Assinatura:', '');
        $this->desenharCampoResponsavel($pdf, $left + 8, $top + 46, 'Data:', '_____/_____/'.$ano, false);

        return $top + self::RESP_H;
    }

    private function desenharCampoResponsavel(
        BeneficioProtocoloEntregaCartaoFpdi $pdf,
        float $x,
        float $y,
        string $rotulo,
        string $valor,
        bool $linha = true,
    ): void {
        $pdf->SetFont('helvetica', '', 10.5);
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetXY($x, $y);
        $pdf->Cell(32, 5, $rotulo, 0, 0, 'L');

        if ($linha) {
            $pdf->SetDrawColor(90, 90, 90);
            $pdf->SetLineWidth(0.2);
            $pdf->Line($x + 34, $y + 4.5, $x + 165, $y + 4.5);
            if ($valor !== '') {
                $pdf->SetXY($x + 34, $y);
                $pdf->Cell(130, 5, $valor, 0, 0, 'L');
            }
        } else {
            $pdf->SetXY($x + 34, $y);
            $pdf->Cell(130, 5, $valor, 0, 0, 'L');
        }
    }

    private function desenharContinuacao(BeneficioProtocoloEntregaCartaoFpdi $pdf, string $tituloDocumento, float $y): float
    {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(17, 17, 17);
        $pdf->SetXY(0, $y);
        $pdf->Cell(self::PAGE_W, 6, $tituloDocumento.' (continuação)', 0, 0, 'C');

        return $y + 8;
    }

    /**
     * @param  Collection<int, ColaboradorBeneficio>  $vinculos
     * @return list<array{numero: int, nome: string, contrato: string}>
     */
    private function montarLinhas(Collection $vinculos): array
    {
        $linhas = [];
        $numero = 1;

        foreach ($vinculos as $vinculo) {
            $colaborador = $vinculo->colaborador;
            $linhas[] = [
                'numero' => $numero,
                'nome' => mb_strtoupper((string) $colaborador->nome),
                'contrato' => $this->formatarContrato((string) ($colaborador->centro_custo ?? '')),
            ];
            $numero++;
        }

        return $linhas;
    }

    /**
     * @param  list<array{numero: int, nome: string, contrato: string}>  $linhas
     * @return list<list<array{numero: int, nome: string, contrato: string}>>
     */
    private function paginarLinhas(array $linhas): array
    {
        if ($linhas === []) {
            return [[]];
        }

        $paginas = [];
        $paginas[] = array_slice($linhas, 0, self::LINHAS_PRIMEIRA_PAGINA);
        $restante = array_slice($linhas, self::LINHAS_PRIMEIRA_PAGINA);

        while ($restante !== []) {
            $paginas[] = array_slice($restante, 0, self::LINHAS_CONTINUACAO);
            $restante = array_slice($restante, self::LINHAS_CONTINUACAO);
        }

        return $paginas;
    }

    /**
     * @param  Collection<int, ColaboradorBeneficio>  $vinculos
     */
    private function contratoRepresentativo(Collection $vinculos): string
    {
        $primeiro = $vinculos->first()?->colaborador;
        if ($primeiro === null) {
            return '—';
        }

        return $this->formatarContrato((string) ($primeiro->centro_custo ?? ''));
    }

    private function formatarContrato(string $centroCusto): string
    {
        $centro = trim($centroCusto);
        if ($centro === '') {
            return '—';
        }

        if (preg_match('/^\d+$/', $centro)) {
            return 'CT '.$centro;
        }

        if (preg_match('/^ct\s*\d+/iu', $centro)) {
            return mb_strtoupper($centro);
        }

        return mb_strtoupper($centro);
    }

    private function aplicarPapelTimbrado(BeneficioProtocoloEntregaCartaoFpdi $pdf): void
    {
        $pdf->setSourceFile($this->caminhoPapelTimbrado());
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 210, 297);
        $pdf->SetTextColor(17, 17, 17);
    }

    private function caminhoPapelTimbrado(): string
    {
        $path = resource_path('pdf/papel-timbrado.pdf');
        abort_unless(is_file($path), 500, 'Modelo de papel timbrado não encontrado.');

        return $path;
    }
}
