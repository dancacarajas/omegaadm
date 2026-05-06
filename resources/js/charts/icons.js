import { createIcons, icons } from 'lucide';

/** Ícones Lucide para Blade/Alpine (substitui ícones pesados; mantém o pacote completo para `data-lucide`). */
export function initAppLucideIcons() {
    createIcons({
        icons,
        attrs: {
            'stroke-width': 1.8,
        },
    });
}
