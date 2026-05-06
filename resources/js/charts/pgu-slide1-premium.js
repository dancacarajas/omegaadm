import { runSlide1CountUps } from './counters.js';
import { syncPguExecutiveDonut } from './pgu-donut.js';

export function syncPguSlide1Premium(root, summary) {
    if (!root || !summary) return;
    const svg = root.querySelector('[data-pgu-executive-donut]');
    if (svg) {
        syncPguExecutiveDonut(svg, summary.overall_progress);
    }
    runSlide1CountUps(root, summary);
}

window.syncPguSlide1Premium = syncPguSlide1Premium;
