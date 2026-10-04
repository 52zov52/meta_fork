export function formatRuDateInput(value) {
    const raw = String(value || '');
    const apiDate = apiDateToRuDate(raw);
    if (apiDate) return apiDate;

    const digits = raw.replace(/\D/g, '').slice(0, 8);
    const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)].filter(Boolean);

    return parts.join('.');
}

export function isAllowedRuDateKey(event) {
    if (event.ctrlKey || event.metaKey) return true;

    const key = event.key;
    if (!key || key.length !== 1) return true;

    return /^\d$/.test(key);
}

export function apiDateToRuDate(value) {
    const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return '';

    const [, year, month, day] = match;

    return `${day}.${month}.${year}`;
}

export function isValidRuDate(value) {
    const match = String(value || '').match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
    if (!match) return false;

    const [, dayRaw, monthRaw, yearRaw] = match;
    const day = Number(dayRaw);
    const month = Number(monthRaw);
    const year = Number(yearRaw);

    if (year < 1900 || year > 2100) return false;

    const date = new Date(Date.UTC(year, month - 1, day));

    return date.getUTCFullYear() === year
        && date.getUTCMonth() === month - 1
        && date.getUTCDate() === day;
}

export function ruDateToApiDate(value) {
    if (!isValidRuDate(value)) return '';

    const [, day, month, year] = String(value).match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

    return `${year}-${month}-${day}`;
}

export function ruDateToParts(value) {
    if (!isValidRuDate(value)) return null;

    const [, day, month, year] = String(value).match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

    return { day: Number(day), month: Number(month) - 1, year: Number(year) };
}

export function ruDateFromParts(day, month, year) {
    const pad = (part) => String(part).padStart(2, '0');

    return `${pad(day)}.${pad(month + 1)}.${year}`;
}
