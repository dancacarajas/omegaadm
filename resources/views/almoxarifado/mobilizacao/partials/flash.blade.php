@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
        <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/80 bg-gradient-to-r from-red-50 to-white px-5 py-4 text-sm text-red-900 shadow-sm">
        <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if (session('warning'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-white px-5 py-4 text-sm text-amber-900 shadow-sm">
        <i data-lucide="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif
