export function fmt(n) {
    return (n || 0).toLocaleString('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MAD';
}

export function fmtK(n) {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(2) + 'M';
    if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K';
    return (n || 0).toFixed(0);
}

export function pct(v, t) {
    return t > 0 ? ((v / t) * 100).toFixed(1) + '%' : '0.0%';
}

export function faStatus(ratio, seuils = { ok: 30, warn: 40 }) {
    if (ratio <= seuils.ok) return { cls: 'ok', label: 'NORMAL', color: '#0f9d58' };
    if (ratio <= seuils.warn) return { cls: 'warn', label: 'ALERTE', color: '#f59e0b' };
    return { cls: 'danger', label: 'ANOMALIE', color: '#d93025' };
}
