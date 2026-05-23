<?php

namespace App\Support\Pdf;

use Dompdf\Options;

final class DompdfArial
{
    public const FAMILY = 'Arial';

    public static function fontDir(): string
    {
        return resource_path('fonts');
    }

    public static function applyOptions(Options $options): void
    {
        $dir = self::fontDir();
        $cache = storage_path('fonts');
        if (! is_dir($cache)) {
            mkdir($cache, 0755, true);
        }
        $options->set('fontDir', $dir);
        $options->set('fontCache', $cache);
        $options->set('defaultFont', self::FAMILY);
        $options->set('isFontSubsettingEnabled', true);
    }

    public static function fontFaceCss(): string
    {
        $dir = str_replace('\\', '/', self::fontDir());

        return <<<CSS
        @font-face {
            font-family: 'Arial';
            font-style: normal;
            font-weight: normal;
            src: url("{$dir}/Arial.ttf") format("truetype");
        }
        @font-face {
            font-family: 'Arial';
            font-style: normal;
            font-weight: bold;
            src: url("{$dir}/Arial-Bold.ttf") format("truetype");
        }
        CSS;
    }
}
