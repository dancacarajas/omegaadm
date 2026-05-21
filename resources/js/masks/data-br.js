/**
 * Máscara dd/mm/aaaa em campos com data-mask="data-br".
 */
export function formatDataBrValue(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 8);

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 4) {
        return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }

    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
}

export function initDataBrMasks(root = document) {
    root.querySelectorAll('[data-mask="data-br"]').forEach((input) => {
        if (input.dataset.dataBrMaskInit === '1') {
            return;
        }

        input.dataset.dataBrMaskInit = '1';
        input.value = formatDataBrValue(input.value);

        input.addEventListener('input', () => {
            const pos = input.selectionStart ?? 0;
            const antes = input.value.length;
            input.value = formatDataBrValue(input.value);
            const depois = input.value.length;
            const novaPos = Math.max(0, pos + (depois - antes));
            input.setSelectionRange(novaPos, novaPos);
        });

        input.addEventListener('blur', () => {
            input.value = formatDataBrValue(input.value);
        });
    });
}
