@php
    $registrosDia = $registrosDia ?? [];
    $registro = $registrosDia[$colab->id] ?? null;
    $observacaoAtual = old("itens.{$colab->id}.observacao", $registro?->observacao ?? '');
    $qtdAnexos = (int) ($registro?->anexos_count ?? 0);
    $temJustificativa = trim((string) $observacaoAtual) !== '' || $qtdAnexos > 0;
    $anexosExistentes = $registro?->anexos ?? collect();
@endphp

<input
    type="hidden"
    name="itens[{{ $colab->id }}][observacao]"
    value="{{ $observacaoAtual }}"
    data-justificativa-input="{{ $colab->id }}"
>

<button
    type="button"
    data-justificativa-open
    data-colaborador-id="{{ $colab->id }}"
    data-colaborador-nome="{{ $colab->nome }}"
    data-justificativa-texto="{{ e($observacaoAtual) }}"
    data-anexos-count="{{ $qtdAnexos }}"
    @foreach ($anexosExistentes as $anexo)
        data-anexo-existente-{{ $loop->index }}-nome="{{ e($anexo->nome_original) }}"
        data-anexo-existente-{{ $loop->index }}-url="{{ route('presenca-obra.anexos.download', $anexo) }}"
    @endforeach
    class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition {{ $temJustificativa ? 'border-sky-300 bg-sky-50 text-sky-900' : 'border-zinc-200 bg-white text-brand-gray hover:border-brand-burgundy hover:text-brand-burgundy' }}"
>
    <i data-lucide="message-square-text" class="h-3.5 w-3.5"></i>
    <span data-justificativa-label>Justificativa{{ $temJustificativa ? ' ✓' : '' }}</span>
</button>
