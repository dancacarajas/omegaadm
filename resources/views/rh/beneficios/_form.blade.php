@php
    $value = fn (string $field) => old($field, data_get($beneficio, $field));
    $inputClass = 'mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-brand-black outline-none transition placeholder:text-zinc-400 focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10';
    $textareaClass = 'mt-2 min-h-28 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-brand-black outline-none transition placeholder:text-zinc-400 focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10';
    $labelClass = 'text-[11px] font-bold uppercase tracking-wide text-brand-gray';
@endphp

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-sm text-brand-burgundy">
        <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5"></i>
        <div>
            <p class="font-bold">Revise os campos destacados.</p>
            <p class="mt-1 text-xs font-medium text-brand-burgundy/80">Preencha as informacoes obrigatorias para salvar o beneficio.</p>
        </div>
    </div>
@endif

<section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
    <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20">
            <i data-lucide="hand-heart" class="h-6 w-6"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Cadastro</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Dados do benefício</h2>
        </div>
    </div>

    <div class="grid gap-5 p-6 md:grid-cols-4">
        <label class="md:col-span-2">
            <span class="{{ $labelClass }}">Nome do benefício *</span>
            <input name="nome" value="{{ $value('nome') }}" required placeholder="Ex: Vale alimentação" class="{{ $inputClass }}">
            @error('nome') <span class="mt-1 block text-xs text-brand-burgundy">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="{{ $labelClass }}">Tipo</span>
            <input name="tipo" value="{{ $value('tipo') }}" placeholder="Saúde, alimentação..." class="{{ $inputClass }}">
        </label>
        <label>
            <span class="{{ $labelClass }}">Código</span>
            <input name="codigo" value="{{ $value('codigo') }}" placeholder="BEN-001" class="{{ $inputClass }}">
        </label>
        <label class="md:col-span-2">
            <span class="{{ $labelClass }}">Fornecedor</span>
            <input name="fornecedor" value="{{ $value('fornecedor') }}" placeholder="Operadora ou fornecedor" class="{{ $inputClass }}">
        </label>
        <label id="beneficio-campo-valor">
            <span class="{{ $labelClass }}">Valor</span>
            <input type="number" step="0.01" min="0" name="valor" id="beneficio-valor-input" value="{{ $value('valor') }}" placeholder="0,00" class="{{ $inputClass }}">
            <p class="mt-1 text-[11px] leading-snug text-brand-gray">
                Benefícios com valor fixo na folha (ex.: plano com mensalidade fixa). Deixe em branco ou <strong>0</strong> se o valor for calculado por regra.
            </p>
            <p id="beneficio-valor-hint-webcard" class="mt-2 hidden rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-[11px] leading-snug text-violet-950">
                <strong>WebCard:</strong> não use este campo para o limite por solicitação. O sistema calcula
                <strong>30% do salário</strong> de cada colaborador (ficha do efetivo), com teto mensal de
                <strong>R$ 1.500,00</strong>. Após salvar, configure em
                <strong>RH → Benefícios → Extrato → Configuração/Regras</strong> e gere o extrato por colaborador.
            </p>
        </label>
        <label>
            <span class="{{ $labelClass }}">Periodicidade</span>
            <input name="periodicidade" value="{{ $value('periodicidade') }}" placeholder="Mensal" class="{{ $inputClass }}">
        </label>
        <label>
            <span class="{{ $labelClass }}">Status</span>
            <select name="status" class="{{ $inputClass }}">
                @foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo', 'suspenso' => 'Suspenso'] as $option => $label)
                    <option value="{{ $option }}" @selected($value('status') === $option)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="md:col-span-3">
            <span class="{{ $labelClass }}">Elegibilidade</span>
            <textarea name="elegibilidade" placeholder="Regras para quem pode receber este benefício" class="{{ $textareaClass }}">{{ $value('elegibilidade') }}</textarea>
        </label>
        <label class="md:col-span-2">
            <span class="{{ $labelClass }}">Descrição</span>
            <textarea name="descricao" placeholder="Descrição geral do benefício" class="{{ $textareaClass }}">{{ $value('descricao') }}</textarea>
        </label>
        <label class="md:col-span-2">
            <span class="{{ $labelClass }}">Observações</span>
            <textarea name="observacoes" placeholder="Informações internas" class="{{ $textareaClass }}">{{ $value('observacoes') }}</textarea>
        </label>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const hint = document.getElementById('beneficio-valor-hint-webcard');
    const inputValor = document.getElementById('beneficio-valor-input');
    const campos = ['nome', 'tipo', 'codigo'].map((n) => document.querySelector('[name="' + n + '"]')).filter(Boolean);
    if (!hint || campos.length === 0) return;

    function pareceWebcard() {
        const texto = campos.map((el) => (el.value || '').toLowerCase()).join(' ');
        return /\bweb\s*card\b|webcard|adiantamento\s+salarial/.test(texto);
    }

    function atualizar() {
        const ativo = pareceWebcard();
        hint.classList.toggle('hidden', !ativo);
        if (inputValor && ativo && (inputValor.value === '' || parseFloat(inputValor.value) > 0)) {
            inputValor.placeholder = '0 — regra no extrato';
        }
    }

    campos.forEach((el) => el.addEventListener('input', atualizar));
    atualizar();
})();
</script>
@endpush
