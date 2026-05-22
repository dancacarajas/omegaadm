/**
 * Máscara monetária BR (1.234,56): últimos 2 dígitos = centavos.
 * Uso: data-mask="moeda-br" no input.
 */
export function formatMoedaBrValue(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 15);

    if (digits.length === 0) {
        return '';
    }

    const padded = digits.padStart(3, '0');
    const centavos = padded.slice(-2);
    let inteiros = padded.slice(0, -2).replace(/^0+(?=\d)/, '');

    if (inteiros === '') {
        inteiros = '0';
    }

    const comMilhar = inteiros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return `${comMilhar},${centavos}`;
}

export function initMoedaBrMasks(root = document) {
    root.querySelectorAll('[data-mask="moeda-br"]').forEach((input) => {
        if (input.dataset.moedaBrMaskInit === '1') {
            return;
        }

        input.dataset.moedaBrMaskInit = '1';
        input.value = formatMoedaBrValue(input.value);

        input.addEventListener('input', () => {
            const pos = input.selectionStart ?? 0;
            const antes = input.value.length;
            input.value = formatMoedaBrValue(input.value);
            const depois = input.value.length;
            const novaPos = Math.max(0, Math.min(depois, pos + (depois - antes)));
            input.setSelectionRange(novaPos, novaPos);
        });

        input.addEventListener('blur', () => {
            input.value = formatMoedaBrValue(input.value);
        });
    });
}
