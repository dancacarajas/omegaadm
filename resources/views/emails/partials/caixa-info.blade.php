@php
    $titulo = $titulo ?? null;
    $burgundySoft = '#f7e9ee';
    $burgundy = '#6f1731';
    $black = '#111111';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background-color:{{ $burgundySoft }};border-radius:12px;border:1px solid #ecd5dc;">
    <tr>
        <td style="padding:16px 20px;">
            @if ($titulo)
                <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $burgundy }};">{{ $titulo }}</p>
            @endif
            <div style="font-size:14px;line-height:1.55;color:{{ $black }};">
                {{ $slot }}
            </div>
        </td>
    </tr>
</table>
