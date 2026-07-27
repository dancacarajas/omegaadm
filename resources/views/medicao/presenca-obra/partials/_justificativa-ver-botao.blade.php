@php
    $observacao = trim((string) ($registro->observacao ?? ''));
    $qtdAnexos = (int) ($registro->anexos_count ?? $registro->anexos?->count() ?? 0);
    $temAnexo = $qtdAnexos > 0;
    $temJustificativa = $observacao !== '' || $temAnexo;
    $classeBotao = $temAnexo
        ? 'border-amber-300 bg-amber-50 text-amber-900 hover:border-amber-400'
        : ($temJustificativa ? 'border-sky-300 bg-sky-50 text-sky-900 hover:border-sky-400' : 'border-zinc-200 bg-white text-brand-gray hover:border-brand-burgundy hover:text-brand-burgundy');
    $labelBotao = 'Justificativa';
    if ($temJustificativa) {
        $labelBotao .= ' ✓';
        if ($temAnexo) {
            $labelBotao .= ' ('.$qtdAnexos.' '.($qtdAnexos === 1 ? 'anexo' : 'anexos').')';
        }
    }
@endphp

<button
    type="button"
    data-justificativa-ver-open
    data-colaborador-nome="{{ $registro->colaborador?->nome }}"
    data-observacao="{{ e($observacao) }}"
    @foreach ($registro->anexos ?? [] as $anexo)
        data-anexo-existente-{{ $loop->index }}-nome="{{ e($anexo->nome_original) }}"
        data-anexo-existente-{{ $loop->index }}-url="{{ route('medicao.presenca-obra.anexos.visualizar', $anexo) }}"
    @endforeach
    class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition {{ $classeBotao }}"
>
    <i data-lucide="message-square-text" class="h-3.5 w-3.5"></i>
    <span>{{ $labelBotao }}</span>
</button>
