@php
    $url = $url ?? '#';
    $texto = $texto ?? 'Acessar';
    $burgundy = '#6f1731';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
    <tr>
        <td align="center">
            <a href="{{ $url }}" target="_blank" rel="noopener"
               style="display:inline-block;background-color:{{ $burgundy }};color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:14px 28px;border-radius:10px;box-shadow:0 4px 12px rgba(111,23,49,0.25);">
                {{ $texto }}
            </a>
        </td>
    </tr>
</table>
