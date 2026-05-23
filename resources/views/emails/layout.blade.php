@php
    $burgundy = '#6f1731';
    $burgundyDark = '#431020';
    $burgundySoft = '#f7e9ee';
    $gray = '#6b6f76';
    $black = '#111111';
    $bgPage = '#f6f6f7';
    $appName = $appName ?? config('app.name', 'Omega286');
    $appUrl = $appUrl ?? config('app.url');
    $ano = $ano ?? now()->format('Y');
    $preheader = trim($__env->yieldContent('preheader'));
    $tituloEmail = trim($__env->yieldContent('titulo', 'Notificação'));
    $headerTagline = $headerTagline ?? 'Portal de gestão contratual';
    $exibirRodape = $exibirRodape ?? true;
    $corpoEstiloBorda = $exibirRodape
        ? 'border-left:1px solid #e8e8ea;border-right:1px solid #e8e8ea;'
        : 'border-left:1px solid #e8e8ea;border-right:1px solid #e8e8ea;border-bottom:1px solid #e8e8ea;border-radius:0 0 16px 16px;';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $tituloEmail }} — {{ $appName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:{{ $bgPage }};font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    @if (!empty($preview))
        <div style="background:#1e3a5f;color:#fff;text-align:center;padding:10px 16px;font-size:13px;font-weight:600;">
            Pré-visualização do layout — este aviso não aparece nos e-mails enviados.
        </div>
    @endif

    @if ($preheader !== '')
        <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $bgPage }};">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,{{ $burgundy }} 0%,{{ $burgundyDark }} 100%);border-radius:16px 16px 0 0;padding:28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="Omega Service" width="180" style="display:block;max-width:180px;height:auto;border:0;{{ empty($logoBranco) ? 'filter:brightness(0) invert(1);-webkit-filter:brightness(0) invert(1);' : '' }}">
                                        @else
                                            <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">Omega Service</p>
                                        @endif
                                        <p style="margin:8px 0 0;font-size:11px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.75);">
                                            {{ $headerTagline }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Faixa do assunto --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 32px;border-left:1px solid #e8e8ea;border-right:1px solid #e8e8ea;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding:24px 0 8px;border-bottom:3px solid {{ $burgundy }};">
                                        <h1 style="margin:0;font-size:20px;font-weight:700;line-height:1.35;color:{{ $black }};">
                                            @yield('titulo', 'Notificação')
                                        </h1>
                                        @hasSection('subtitulo')
                                            <p style="margin:10px 0 0;font-size:14px;line-height:1.5;color:{{ $gray }};">
                                                @yield('subtitulo')
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Corpo --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:8px 32px 28px;{{ $corpoEstiloBorda }}">
                            @yield('conteudo')
                        </td>
                    </tr>

                    @if ($exibirRodape)
                        {{-- Rodapé --}}
                        <tr>
                            <td style="background-color:{{ $burgundySoft }};border-radius:0 0 16px 16px;padding:24px 32px;border:1px solid #ecd5dc;border-top:none;">
                                <p style="margin:0 0 8px;font-size:12px;line-height:1.6;color:{{ $gray }};text-align:center;">
                                    Esta mensagem foi enviada automaticamente por <strong style="color:{{ $burgundy }};">{{ $emailBrandName ?? 'Omega Adm CT 286' }}</strong>.
                                </p>
                                <p style="margin:0;font-size:11px;line-height:1.5;color:{{ $gray }};text-align:center;">
                                    @if (!empty($fromAddress))
                                        Remetente: {{ $fromAddress }} ·
                                    @endif
                                    © {{ $ano }} Omega Service. Não responda diretamente a este e-mail se não for solicitado.
                                </p>
                                @if (!empty($appUrl))
                                    <p style="margin:12px 0 0;text-align:center;">
                                        <a href="{{ $appUrl }}" style="font-size:11px;font-weight:600;color:{{ $burgundy }};text-decoration:underline;">{{ parse_url($appUrl, PHP_URL_HOST) ?: $appUrl }}</a>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endif

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
