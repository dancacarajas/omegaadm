@props([
    /** @var array{plotH: int, vbW: int, cols: list<array>, connectors: list<array{i: int, yBottomPx: int}>} $layout */
    'layout',
])

@php
    $plotH = (int) ($layout['plotH'] ?? 220);
    $vbW = (int) ($layout['vbW'] ?? 600);
    $cols = $layout['cols'] ?? [];
    $connectors = $layout['connectors'] ?? [];
    $w6 = $vbW > 0 ? $vbW / 6 : 100;
@endphp

<div class="relative w-full overflow-hidden rounded-xl bg-white">
    <div class="relative flex w-full" style="min-height: {{ $plotH + 52 }}px">
        <svg
            class="pointer-events-none absolute inset-x-0 top-0 z-0 text-[#bdbdbd]"
            style="height: {{ $plotH }}px"
            viewBox="0 0 {{ $vbW }} {{ $plotH }}"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            @foreach ($connectors as $line)
                @php
                    $i = (int) ($line['i'] ?? 0);
                    $yBottomPx = (int) ($line['yBottomPx'] ?? 0);
                    $y = $plotH - $yBottomPx;
                    $x1 = ($i + 0.5) * $w6;
                    $x2 = ($i + 1.5) * $w6;
                @endphp
                <line
                    x1="{{ $x1 }}"
                    y1="{{ $y }}"
                    x2="{{ $x2 }}"
                    y2="{{ $y }}"
                    stroke="currentColor"
                    stroke-width="1.25"
                    stroke-dasharray="5 5"
                    vector-effect="non-scaling-stroke"
                />
            @endforeach
        </svg>

        @foreach ($cols as $col)
            @php
                $tone = ($col['tone'] ?? 'maroon') === 'pink' ? 'pink' : 'maroon';
                $barClass = $tone === 'pink'
                    ? 'rounded-sm bg-[#f3cfd9]'
                    : 'rounded-t-md bg-[#600020]';
                $bb = (int) ($col['barBottomPx'] ?? 0);
                $bh = (int) ($col['barHeightPx'] ?? 0);
                $pos = $col['valuePosition'] ?? 'above';
                $belowBottom = $bh > 0 ? max(2, $bb - 20) : 6;
            @endphp
            <div class="relative z-[1] flex min-w-0 flex-1 flex-col items-center">
                <div class="relative w-full border-b border-zinc-400" style="height: {{ $plotH }}px">
                    @if ($pos === 'above' && ($col['valueLabel'] ?? '') !== '')
                        <span
                            class="absolute left-1/2 z-[2] -translate-x-1/2 whitespace-nowrap text-xs font-bold tabular-nums text-zinc-900"
                            style="bottom: {{ $bb + $bh + 6 }}px"
                        >{{ $col['valueLabel'] }}</span>
                    @endif

                    @if ($bh > 0)
                        <div
                            class="absolute left-1/2 w-[58%] max-w-[52px] -translate-x-1/2 {{ $barClass }}"
                            style="bottom: {{ $bb }}px; height: {{ $bh }}px"
                        ></div>
                    @endif

                    @if ($pos === 'below' && ($col['valueLabel'] ?? '') !== '')
                        <span
                            class="absolute left-1/2 z-[2] -translate-x-1/2 whitespace-nowrap text-xs font-bold tabular-nums text-zinc-900"
                            style="bottom: {{ $belowBottom }}px"
                        >{{ $col['valueLabel'] }}</span>
                    @endif
                </div>
                <p class="mt-2 max-w-[7.5rem] px-0.5 text-center text-[10px] font-semibold leading-tight text-zinc-600 sm:max-w-none sm:text-[11px]">
                    {{ $col['category'] ?? '' }}
                </p>
            </div>
        @endforeach
    </div>
</div>
