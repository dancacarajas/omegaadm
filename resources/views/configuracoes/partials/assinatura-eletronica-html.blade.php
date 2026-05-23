@php
    use App\Services\EmailAssinaturaService as Ass;

    $w = Ass::LARGURA_PX;
    $h = Ass::ALTURA_PX;
    $fs = Ass::TEXTO_FONT_SIZE_PX;
    $lh = Ass::TEXTO_LINE_HEIGHT_PX;
    $linha = "padding:0;margin:0;font-family:Arial,sans-serif;font-size:{$fs}px;line-height:{$lh}px;mso-line-height-rule:exactly;color:#000000;text-transform:none;";
    $linhaBold = $linha.'font-weight:bold;';
    $linhaNormal = $linha.'font-weight:normal;';
@endphp
<table cellpadding="0" cellspacing="0" border="0" width="{{ $w }}" height="{{ $h }}" style="border-collapse:collapse;width:{{ $w }}px;height:{{ $h }}px;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td width="{{ $w }}" height="{{ $h }}" valign="top" style="width:{{ $w }}px;height:{{ $h }}px;padding:{{ Ass::TEXTO_PADDING_TOP_PX }}px 0 0 {{ Ass::TEXTO_PADDING_LEFT_PX }}px;background-image:url('{{ $bgUrl }}');background-repeat:no-repeat;background-size:{{ $w }}px {{ $h }}px;font-family:Arial,sans-serif;font-size:{{ $fs }}px;line-height:{{ $lh }}px;">
            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:{{ $fs }}px;line-height:{{ $lh }}px;">
                <tr>
                    <td style="{{ $linhaBold }}">Atenciosamente,</td>
                </tr>
                @if ($dados['nome'] !== '')
                    <tr>
                        <td style="{{ $linhaNormal }}">{{ $dados['nome'] }}</td>
                    </tr>
                @endif
                @if ($dados['funcao'] !== '')
                    <tr>
                        <td style="{{ $linhaNormal }}">{{ $dados['funcao'] }}</td>
                    </tr>
                @endif
                @if ($dados['contrato'] !== '')
                    <tr>
                        <td style="{{ $linhaNormal }}">{{ $dados['contrato'] }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="{{ $linhaNormal }}">{{ $localFixo }}</td>
                </tr>
                <tr>
                    <td style="{{ $linhaNormal }}">{{ $linhaTelefone }}</td>
                </tr>
                @if ($dados['email'] !== '')
                    <tr>
                        <td style="{{ $linhaNormal }}">E-mail: {{ $dados['email'] }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
