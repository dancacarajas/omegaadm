<table class="hdr-box">
    <tr>
        <td rowspan="2" class="hdr-logo">
            @if ($logoBase64 ?? null)
                <img src="{{ $logoBase64 }}" alt="Omega Service">
            @else
                <strong style="font-size: 10px; color: #6f1731;">OMEGA SERVICE</strong>
            @endif
        </td>
        <td rowspan="2" class="hdr-titulo">{{ $pdfTitulo }}</td>
        <td colspan="2" class="hdr-rg">{{ $pdfCodigo }}</td>
    </tr>
    <tr>
        <td class="hdr-rev">REV {{ $pdfRev }}</td>
        <td class="hdr-data">DATA:{{ $pdfData->format('d/m/Y') }}</td>
    </tr>
</table>
