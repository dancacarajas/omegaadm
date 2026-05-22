@extends('emails.layout')

@section('preheader', 'Um novo registro TST foi concluído no sistema.')

@section('titulo', 'Registro TST concluído')

@section('subtitulo')
    {{ $registro->colaborador?->nome ?? 'Colaborador' }} · {{ $registro->data?->format('d/m/Y') ?? '—' }}
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $descricao = \Illuminate\Support\Str::limit(strip_tags((string) ($registro->descricao ?? '')), 500);
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Um registro de atividade TST foi enviado e está disponível para consulta no painel SSMA.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => array_filter([
            'Colaborador' => $registro->colaborador?->nome ?? '—',
            'Matrícula' => $registro->colaborador?->matricula ?? null,
            'Atividade' => $registro->atividade?->nome ?? 'Não informada',
            'Data do registro' => $registro->data?->format('d/m/Y') ?? '—',
            'Origem' => $origemLabel ?? '—',
            'Registrado por' => $registradoPor ?? '—',
            'ID do registro' => '#'.$registro->id,
        ]),
    ])

    @if ($descricao !== '')
        <p style="margin:16px 0 0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:{{ $gray }};">Descrição</p>
        <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:{{ $black }};white-space:pre-wrap;">{{ $descricao }}</p>
    @endif

    @include('emails.partials.botao', [
        'url' => $urlRegistro ?? '#',
        'texto' => 'Abrir registro no sistema',
    ])
@endsection
