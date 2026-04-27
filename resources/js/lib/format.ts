export function formatCurrencyTZS(value: number | string | null | undefined) {
    const numericValue = Number(value ?? 0);

    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        maximumFractionDigits: 0,
    }).format(numericValue);
}

export function titleCase(value: string) {
    return value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
