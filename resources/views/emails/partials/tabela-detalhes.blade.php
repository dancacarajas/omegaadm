@php
    $linhas = $linhas ?? [];
    $gray = '#6b6f76';
    $black = '#111111';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;border-collapse:collapse;">
    @foreach ($linhas as $rotulo => $valor)
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #ececef;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:{{ $gray }};width:38%;vertical-align:top;">
                {{ $rotulo }}
            </td>
            <td style="padding:10px 0 10px 12px;border-bottom:1px solid #ececef;font-size:14px;font-weight:600;color:{{ $black }};vertical-align:top;">
                {{ $valor }}
            </td>
        </tr>
    @endforeach
</table>
