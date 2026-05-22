<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

/**
 * Layout HTML transacional (Omega / identidade burgundy).
 */
final class EmailLayout
{
    /** @return array<string, mixed> */
    public static function dadosPadrao(): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $logoBranco = public_path('logo-email-branco.png');
        $logoPadrao = public_path('logo.png');
        $logoArquivo = is_file($logoBranco) ? 'logo-email-branco.png' : (is_file($logoPadrao) ? 'logo.png' : null);

        return [
            'appName' => config('mail.from.name') ?: config('app.name', 'Omega286'),
            'emailBrandName' => config('mail.brand_name', 'Omega Adm CT 286'),
            'appUrl' => $appUrl,
            'logoUrl' => $logoArquivo ? $appUrl.'/'.$logoArquivo : null,
            'logoBranco' => is_file($logoBranco) || is_file($logoPadrao),
            'fromAddress' => config('mail.from.address'),
            'ano' => now()->format('Y'),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function render(string $view, array $dados = []): string
    {
        return View::make($view, array_merge(self::dadosPadrao(), $dados))->render();
    }
}
