import { CountUp } from 'countup.js';

const instances = new Map();

function animateCountUp(el, endVal, options) {
    if (!el) return;
    let inst = instances.get(el);
    if (!inst) {
        inst = new CountUp(el, endVal, options);
        if (inst.error) {
            return;
        }
        instances.set(el, inst);
        inst.start();
        return;
    }
    inst.update(endVal);
}

/**
 * Anima KPIs e percentuais do slide premium a partir do summary da API PGU.
 */
export function runSlide1CountUps(root, summary) {
    if (!root || !summary) return;

    const t = Number(summary.total_functions ?? 0);
    const c = Number(summary.vagas_concluidas ?? summary.completed_functions ?? 0);
    const pend = Math.max(0, t - c);
    const overall = Number(summary.overall_progress ?? 0);
    const pctDone = t > 0 ? (c / t) * 100 : 0;
    const pctPend = t > 0 ? (pend / t) * 100 : 0;
    const remainder = Number.isFinite(overall) ? Math.min(100, Math.max(0, 100 - overall)) : 0;

    const intOpts = {
        duration: 1.15,
        decimalPlaces: 0,
        separator: '.',
        decimal: ',',
    };
    const decPct = {
        duration: 1.35,
        decimalPlaces: 1,
        separator: '.',
        decimal: ',',
        suffix: '%',
    };
    const decPlain = {
        duration: 1.35,
        decimalPlaces: 1,
        separator: '.',
        decimal: ',',
    };

    const runAll = (name, value, opts) => {
        root.querySelectorAll(`[data-pgu-countup="${name}"]`).forEach((el) => {
            animateCountUp(el, value, opts);
        });
    };

    runAll('total', t, intOpts);
    runAll('completed', c, intOpts);
    runAll('pending', pend, intOpts);
    runAll('pct-concluidas', pctDone, decPlain);
    runAll('pct-pendentes', pctPend, decPlain);
    runAll('overall-pct', overall, decPct);
    runAll('remainder-pct', remainder, decPct);
}
