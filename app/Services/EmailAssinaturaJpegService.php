<?php

namespace App\Services;

use RuntimeException;

/**
 * Gera JPEG da assinatura a partir do BG original em alta resolução (sem captura de tela do DOM).
 */
final class EmailAssinaturaJpegService
{
    public const EXPORT_SCALE = 4;

    public const JPEG_QUALITY = 98;

    public function __construct(
        private readonly EmailAssinaturaService $assinaturaService,
    ) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function render(array $dados): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
            throw new RuntimeException('Extensão GD com FreeType é necessária para exportar JPEG.');
        }

        $brutos = $this->assinaturaService->normalizar($dados);
        $exibicao = $this->assinaturaService->formatarParaAssinatura($brutos);

        $scale = self::EXPORT_SCALE;
        $largura = EmailAssinaturaService::LARGURA_PX * $scale;
        $altura = EmailAssinaturaService::ALTURA_PX * $scale;

        $imagem = $this->criarCanvasComFundo($largura, $altura);
        $cor = imagecolorallocate($imagem, 0, 0, 0);

        $fonteNormal = resource_path('fonts/Arial.ttf');
        $fonteNegrito = resource_path('fonts/Arial-Bold.ttf');
        if (! is_file($fonteNormal) || ! is_file($fonteNegrito)) {
            imagedestroy($imagem);
            throw new RuntimeException('Fontes Arial não encontradas em resources/fonts.');
        }

        $tamanhoRegular = (int) round(EmailAssinaturaService::TEXTO_FONT_SIZE_PX * $scale);
        $tamanhoNegrito = (int) round($tamanhoRegular * EmailAssinaturaService::TEXTO_FONT_SIZE_BOLD_RATIO);
        $x = (int) round(EmailAssinaturaService::TEXTO_PADDING_LEFT_PX * $scale);
        $y = $this->baselineInicial($tamanhoRegular, $fonteNormal, $scale);

        foreach ($this->linhas($exibicao, $brutos['telefone']) as $linha) {
            $bold = $linha['bold'];
            $fonte = $bold ? $fonteNegrito : $fonteNormal;
            $tamanho = $bold ? $tamanhoNegrito : $tamanhoRegular;
            imagettftext($imagem, $tamanho, 0, $x, $y, $cor, $fonte, $linha['texto']);
            $y = $this->proximaBaseline($y, $tamanhoRegular, $fonteNormal, $scale);
        }

        ob_start();
        imagejpeg($imagem, null, self::JPEG_QUALITY);
        $binario = (string) ob_get_clean();
        imagedestroy($imagem);

        if ($binario === '') {
            throw new RuntimeException('Falha ao gerar JPEG da assinatura.');
        }

        return $binario;
    }

    private function baselineInicial(int $tamanhoRegular, string $fonteNormal, int $scale): int
    {
        $bbox = imagettfbbox($tamanhoRegular, 0, $fonteNormal, 'Ay');
        $paddingTop = (int) round(EmailAssinaturaService::TEXTO_PADDING_TOP_PX * $scale);

        return $paddingTop - (int) $bbox[7];
    }

    private function proximaBaseline(int $yAtual, int $tamanhoRegular, string $fonteNormal, int $scale): int
    {
        $bbox = imagettfbbox($tamanhoRegular, 0, $fonteNormal, 'Ay');
        $alturaGlyph = (int) ($bbox[1] - $bbox[7]);
        $espacoExtra = (int) round(
            (EmailAssinaturaService::TEXTO_LINE_HEIGHT_PX - EmailAssinaturaService::TEXTO_FONT_SIZE_PX) * $scale
        );

        return $yAtual + $alturaGlyph + max($espacoExtra, (int) round(1 * $scale));
    }

    private function criarCanvasComFundo(int $largura, int $altura): \GdImage
    {
        $caminhoBg = public_path('images/email/assinatura-eletronica-bg.jpg');
        if (! is_file($caminhoBg)) {
            throw new RuntimeException('Imagem de fundo da assinatura não encontrada.');
        }

        $origem = @imagecreatefromjpeg($caminhoBg);
        if ($origem === false) {
            throw new RuntimeException('Não foi possível ler o fundo da assinatura.');
        }

        $canvas = imagecreatetruecolor($largura, $altura);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        imagecopyresampled(
            $canvas,
            $origem,
            0,
            0,
            0,
            0,
            $largura,
            $altura,
            imagesx($origem),
            imagesy($origem)
        );

        imagedestroy($origem);

        return $canvas;
    }

    /**
     * @param  array{nome: string, funcao: string, contrato: string, telefone: string, email: string}  $exibicao
     * @return list<array{texto: string, bold: bool}>
     */
    private function linhas(array $exibicao, string $telefoneBruto): array
    {
        $linhas = [
            ['texto' => 'Atenciosamente,', 'bold' => true],
        ];

        if ($exibicao['nome'] !== '') {
            $linhas[] = ['texto' => $exibicao['nome'], 'bold' => false];
        }
        if ($exibicao['funcao'] !== '') {
            $linhas[] = ['texto' => $exibicao['funcao'], 'bold' => false];
        }
        if ($exibicao['contrato'] !== '') {
            $linhas[] = ['texto' => $exibicao['contrato'], 'bold' => false];
        }

        $linhas[] = ['texto' => EmailAssinaturaService::LOCAL_FIXO, 'bold' => false];
        $linhas[] = ['texto' => $this->assinaturaService->linhaTelefone($telefoneBruto), 'bold' => false];

        if ($exibicao['email'] !== '') {
            $linhas[] = ['texto' => 'E-mail: '.$exibicao['email'], 'bold' => false];
        }

        return $linhas;
    }
}
