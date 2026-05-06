@extends($layout ?? 'layouts.app')

@section('title', 'Apresentação PGU - Omega286')
@section('eyebrow', 'Contrato')
@section('page-title', 'Apresentação PGU')

@section('content')
<div
    class="min-h-screen bg-pgu-bg px-2 py-2 sm:px-4"
    data-pgu-apresentacao
    data-contrato="{{ $contratoDefault }}"
    data-competencia="{{ $competenciaDefault }}"
    data-data-limite="{{ $dataLimiteDefault }}"
    data-export-url="{{ route($exportPptRouteName ?? 'pgu.export.ppt') }}"
    x-data="pguApresentacaoShell()"
    x-init="init()"
>
    <div
        :class="modoApresentacao ? 'max-w-none space-y-0' : 'mx-auto max-w-[1600px] space-y-5'"
        class="space-y-5"
    >
        <x-pgu.page-header
            title="Apresentação PGU"
            subtitle="Modo reunião: cada aba é um slide — visão geral, vitórias, gargalos, concentração e plano de ação."
            x-show="!isModoApresentacaoAtivo()"
        >
            <select x-model="contrato" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
                @foreach ($contratos as $contrato)
                    <option value="{{ $contrato }}">{{ $contrato }}</option>
                @endforeach
            </select>
            <input type="month" x-model="competencia" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <input type="date" x-model="dataLimite" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <button type="button" @click="refresh()" class="inline-flex items-center gap-2 rounded-xl bg-pgu-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                Atualizar
            </button>
            <button type="button" @click="exportPpt()" :disabled="exportandoPpt" :class="exportandoPpt ? 'cursor-not-allowed opacity-70' : ''" class="inline-flex items-center gap-2 rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm font-semibold text-pgu-ink shadow-sm transition hover:border-pgu-primary hover:text-pgu-primary">
                <i data-lucide="file-down" class="h-4 w-4"></i>
                <span x-show="!exportandoPpt">Exportar para PPT</span>
                <span x-show="exportandoPpt" x-cloak x-text="exportProgressLabel || 'Gerando PPT...'"></span>
            </button>
            <button
                type="button"
                @click="toggleModoApresentacao()"
                class="inline-flex items-center gap-2 rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm font-semibold text-pgu-ink shadow-sm transition hover:border-pgu-primary hover:text-pgu-primary"
            >
                <i data-lucide="monitor-up" class="h-4 w-4"></i>
                <span>Modo Apresentação</span>
            </button>
        </x-pgu.page-header>

        <div :class="modoApresentacao ? 'space-y-0' : 'space-y-5'" class="space-y-5">
            <nav
                x-show="!isModoApresentacaoAtivo()"
                class="sticky top-0 z-30 rounded-2xl border border-pgu-border bg-white/95 px-2 py-2 shadow-sm backdrop-blur-md sm:px-3"
                aria-label="Slides da apresentação PGU"
            >
                <div class="flex gap-1.5 overflow-x-auto pb-1" role="tablist">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'capa'"
                        @click="setAbaApresentacao('capa')"
                        :class="abaApresentacao === 'capa' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">00</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Capa</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'geral'"
                        @click="setAbaApresentacao('geral')"
                        :class="abaApresentacao === 'geral' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">01</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Visão Geral PGU</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'funcoes100'"
                        @click="setAbaApresentacao('funcoes100')"
                        :class="abaApresentacao === 'funcoes100' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">02</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Funções com PGU 100%</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'gargalos'"
                        @click="setAbaApresentacao('gargalos')"
                        :class="abaApresentacao === 'gargalos' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">03</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Principais Gargalos</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'concentracao'"
                        @click="setAbaApresentacao('concentracao')"
                        :class="abaApresentacao === 'concentracao' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">04</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Concentração do Problema</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="abaApresentacao === 'plano'"
                        @click="setAbaApresentacao('plano')"
                        :class="abaApresentacao === 'plano' ? 'border-pgu-primary bg-teal-50 text-pgu-primary shadow-sm' : 'border-transparent bg-slate-50 text-pgu-muted hover:border-pgu-border hover:bg-white'"
                        class="flex min-w-[10.5rem] shrink-0 flex-col items-start gap-0.5 rounded-xl border-2 px-3 py-2.5 text-left transition"
                    >
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-pgu-subtle">05</span>
                        <span class="text-xs font-semibold leading-tight text-pgu-ink sm:text-sm">Plano de Ação Executivo</span>
                    </button>
                </div>
            </nav>

            {{-- Slide executivo (CSS dedicado pgu-slide.css — SVG manual, 16:9 / 1366×768) --}}
            <div
                data-pgu-slide-stage
                :class="modoApresentacao ? 'fixed inset-0 z-[90] overflow-auto rounded-none bg-black shadow-none' : 'w-full overflow-hidden rounded-3xl bg-[#ececec] shadow-md'"
                class="w-full overflow-hidden rounded-3xl bg-[#ececec] shadow-md"
            >
                <div
                    x-show="abaApresentacao === 'capa'"
                    x-cloak
                    class="pgu0-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-cover-wrap')
                </div>

                <div
                    x-show="abaApresentacao === 'geral'"
                    x-cloak
                    class="pgu-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-executive-slide-wrap', $pguExecutiveSlide ?? [])
                </div>

                <div
                    x-show="abaApresentacao === 'funcoes100'"
                    x-cloak
                    class="pgu2-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-slide-2-wrap', $pguSlide2 ?? [])
                </div>

                <div
                    x-show="abaApresentacao === 'gargalos'"
                    x-cloak
                    class="pgu3-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-slide-3-wrap', $pguSlide3 ?? [])
                </div>

                <div
                    x-show="abaApresentacao === 'concentracao'"
                    x-cloak
                    class="pgu4-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-slide-4-wrap', $pguSlide4 ?? [])
                </div>

                <div
                    x-show="abaApresentacao === 'plano'"
                    x-cloak
                    class="pgu5-apresentacao-embed w-full max-w-none px-0"
                >
                    @include('dashboard.partials.pgu-slide-5-wrap', $pguSlide5 ?? [])
                </div>

                <div
                    x-show="abaApresentacao !== 'capa' && abaApresentacao !== 'geral' && abaApresentacao !== 'funcoes100' && abaApresentacao !== 'gargalos' && abaApresentacao !== 'concentracao' && abaApresentacao !== 'plano'"
                    x-cloak
                    class="min-h-[40vh] rounded-xl border border-dashed border-pgu-border bg-white/80 p-8 text-center text-sm text-pgu-muted"
                >
                    Conteúdo deste slide em construção.
                </div>

                <div
                    x-show="modoApresentacao"
                    x-cloak
                    class="pointer-events-none fixed right-4 top-4 z-[95] flex items-center gap-2"
                >
                    <span class="rounded-lg bg-black/60 px-3 py-1 text-xs font-semibold text-white/90">
                        Esc para sair
                    </span>
                    <button
                        type="button"
                        @click="sairModoApresentacao()"
                        class="pointer-events-auto inline-flex items-center gap-1 rounded-lg border border-white/30 bg-black/70 px-3 py-2 text-xs font-semibold text-white transition hover:bg-black/85"
                    >
                        Sair apresentação
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
