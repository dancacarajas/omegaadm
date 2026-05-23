<?php

namespace App\Support;

final class AssinaturaEmailImagem
{
    /**
     * Remove borda cinza externa do JPG de fundo (ex.: 2px nas laterais).
     *
     * @return array{x: int, y: int, width: int, height: int}|null
     */
    public static function detectarRecorteSemBorda(\GdImage $imagem): ?array
    {
        $largura = imagesx($imagem);
        $altura = imagesy($imagem);

        if ($largura < 10 || $altura < 10) {
            return null;
        }

        $centroX = (int) floor($largura / 2);
        $centroY = (int) floor($altura / 2);

        $topo = self::primeiraLinhaConteudo($imagem, $largura, $altura, $centroX, true);
        $esquerda = self::primeiraColunaConteudo($imagem, $largura, $altura, $centroY, true);
        $base = self::primeiraLinhaConteudo($imagem, $largura, $altura, $centroX, false);
        $direita = self::primeiraColunaConteudo($imagem, $largura, $altura, $centroY, false);

        if ($topo === null || $esquerda === null || $base === null || $direita === null) {
            return null;
        }

        $larguraRecorte = $direita - $esquerda + 1;
        $alturaRecorte = $base - $topo + 1;

        if ($larguraRecorte < 50 || $alturaRecorte < 20) {
            return null;
        }

        return [
            'x' => $esquerda,
            'y' => $topo,
            'width' => $larguraRecorte,
            'height' => $alturaRecorte,
        ];
    }

    public static function recortarSemBorda(\GdImage $imagem): \GdImage
    {
        $recorte = self::detectarRecorteSemBorda($imagem);
        if ($recorte === null) {
            return $imagem;
        }

        $recortada = imagecrop($imagem, $recorte);
        if ($recortada === false) {
            return $imagem;
        }

        return $recortada;
    }

    public static function caminhoFundoPublico(): string
    {
        return public_path('images/email/assinatura-eletronica-bg.jpg');
    }

    /** Garante arquivo de fundo sem borda cinza (regrava o JPG em public se necessário). */
    public static function garantirFundoSemBordaNoPublico(): void
    {
        $caminho = self::caminhoFundoPublico();
        if (! is_file($caminho)) {
            return;
        }

        $origem = @imagecreatefromjpeg($caminho);
        if ($origem === false) {
            return;
        }

        $recorte = self::detectarRecorteSemBorda($origem);
        if ($recorte === null || ($recorte['x'] <= 0 && $recorte['y'] <= 0
            && $recorte['width'] >= imagesx($origem) - 1 && $recorte['height'] >= imagesy($origem) - 1)) {
            imagedestroy($origem);

            return;
        }

        $semBorda = self::recortarSemBorda($origem);
        if ($semBorda !== $origem) {
            imagedestroy($origem);
        }

        imagejpeg($semBorda, $caminho, 98);
        imagedestroy($semBorda);
    }

    private static function pixelEhFundoClaro(\GdImage $imagem, int $x, int $y): bool
    {
        $cor = imagecolorat($imagem, $x, $y);
        $r = ($cor >> 16) & 255;
        $g = ($cor >> 8) & 255;
        $b = $cor & 255;

        return $r > 240 && $g > 240 && $b > 240;
    }

    private static function primeiraLinhaConteudo(
        \GdImage $imagem,
        int $largura,
        int $altura,
        int $amostraX,
        bool $doTopo,
    ): ?int {
        if ($doTopo) {
            for ($y = 0; $y < $altura; $y++) {
                if (self::pixelEhFundoClaro($imagem, $amostraX, $y)) {
                    return $y;
                }
            }

            return null;
        }

        for ($y = $altura - 1; $y >= 0; $y--) {
            if (self::pixelEhFundoClaro($imagem, $amostraX, $y)) {
                return $y;
            }
        }

        return null;
    }

    private static function primeiraColunaConteudo(
        \GdImage $imagem,
        int $largura,
        int $altura,
        int $amostraY,
        bool $daEsquerda,
    ): ?int {
        if ($daEsquerda) {
            for ($x = 0; $x < $largura; $x++) {
                if (self::pixelEhFundoClaro($imagem, $x, $amostraY)) {
                    return $x;
                }
            }

            return null;
        }

        for ($x = $largura - 1; $x >= 0; $x--) {
            if (self::pixelEhFundoClaro($imagem, $x, $amostraY)) {
                return $x;
            }
        }

        return null;
    }
}
