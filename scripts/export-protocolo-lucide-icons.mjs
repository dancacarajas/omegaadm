/**
 * Exporta ícones Lucide (mesma família do sistema: stroke 1.8) para PNG do protocolo Webcard.
 * Uso: node scripts/export-protocolo-lucide-icons.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer';
import {
    Building2,
    FileText,
    ClipboardList,
    Check,
    User,
} from 'lucide';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '..', 'resources', 'pdf', 'protocolo-webcard');

const STROKE_WIDTH = 1.8;
const STROKE = '#111111';
const SIZE = 64;

/** @param {import('lucide').IconNode} iconNode */
function iconToSvg(iconNode) {
    const paths = iconNode
        .map(([tag, attrs]) => {
            const parts = Object.entries(attrs).map(([k, v]) => `${k}="${String(v).replace(/"/g, '&quot;')}"`);

            return `<${tag} ${parts.join(' ')}/>`;
        })
        .join('');

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${SIZE}" height="${SIZE}" viewBox="0 0 24 24" fill="none" stroke="${STROKE}" stroke-width="${STROKE_WIDTH}" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
}

const mapa = {
    empresa: Building2,
    contrato: ClipboardList,
    documento: FileText,
    check: Check,
    pessoa: User,
};

if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

const browser = await puppeteer.launch({ headless: true });

for (const [nome, iconNode] of Object.entries(mapa)) {
    const svg = iconToSvg(iconNode);
    const html = `<!DOCTYPE html><html><body style="margin:0;padding:8px;background:transparent;display:flex;align-items:center;justify-content:center;">${svg}</body></html>`;
    const page = await browser.newPage();
    await page.setViewport({ width: SIZE + 16, height: SIZE + 16, deviceScaleFactor: 2 });
    await page.setContent(html, { waitUntil: 'networkidle0' });
    const el = await page.$('svg');
    const dest = path.join(outDir, `${nome}.png`);
    await el.screenshot({ path: dest, omitBackground: true });
    await page.close();
    console.log(`OK ${dest}`);
}

await browser.close();
console.log(`Icones Lucide em ${outDir}`);
