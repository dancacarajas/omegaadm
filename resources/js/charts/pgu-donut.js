/**
 * Donut executivo do slide 1 PGU — SVG + stroke-dasharray (fidelidade a callouts/espessura).
 * Não usa ApexCharts: controle total do anel e do miolo via Blade/Tailwind.
 */
export function syncPguExecutiveDonut(svg, overallProgress) {
    if (!svg) return;
    const arc = svg.querySelector('[data-pgu-donut-progress]');
    if (!arc) return;
    const r = parseFloat(arc.getAttribute('r') || '40', 10);
    const circumference = 2 * Math.PI * r;
    const p = Math.min(100, Math.max(0, Number(overallProgress)));
    const dash = (p / 100) * circumference;
    arc.setAttribute('stroke-dasharray', `${dash} ${circumference}`);
    arc.setAttribute('stroke-dashoffset', '0');
}
