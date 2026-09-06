/**
 * Display helpers.
 *
 * Money always arrives from the API as a decimal string the server computed.
 * These functions format it; they never do arithmetic on it.
 */
const peso = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});

export function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);

    return peso.format(Number.isFinite(amount) ? amount : 0);
}

export function formatDate(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value));
}

export function formatDateTime(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

/**
 * Pull the first message for a field out of an API error.
 */
export function firstError(errors, field) {
    return errors?.[field]?.[0] ?? null;
}
