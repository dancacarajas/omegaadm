<aside id="presenca-obra-sidebar" data-app-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[280px] shrink-0 -translate-x-full flex-col border-r border-zinc-200/80 bg-white shadow-xl transition-transform duration-200 ease-out lg:static lg:inset-y-auto lg:left-auto lg:z-auto lg:flex lg:w-[280px] lg:translate-x-0 lg:shadow-none">
    <div class="flex h-16 items-center border-b border-zinc-100 px-4">
        <div class="flex flex-1 justify-center">
            <img src="{{ asset('logo.png') }}" alt="Omega Service" class="block object-contain" style="max-height: 42px; max-width: 165px; width: auto; height: auto;">
        </div>
        <button type="button" data-mobile-nav-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy lg:hidden" aria-label="Fechar menu">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <nav class="flex-1 px-4 py-5 text-sm">
        <div class="space-y-1">
            <div class="flex h-11 items-center gap-3 rounded-lg bg-brand-burgundy px-3 font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                <i data-lucide="hard-hat" class="h-5 w-5"></i>
                <span class="flex-1 text-left">Presença na obra</span>
            </div>
            <div class="mt-2 space-y-1 border-l border-zinc-200 pl-4">
                <a href="{{ route('medicao.presenca-obra.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('medicao.presenca-obra.index') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                    <i data-lucide="layout-grid" class="h-4 w-4"></i>
                    Gestão de Presenças
                </a>
                <a href="{{ route('presenca-obra.identificar') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('presenca-obra.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                    <i data-lucide="hard-hat" class="h-4 w-4"></i>
                    Confirmar presença
                </a>
            </div>
        </div>
    </nav>

    <div class="border-t border-zinc-100 px-4 py-4">
        <p class="text-xs leading-relaxed text-brand-gray">
            Acesso público para consulta e confirmação de presença na obra. Não substitui o ponto do RH.
        </p>
    </div>
</aside>
