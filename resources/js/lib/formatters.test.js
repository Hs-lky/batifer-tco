import { describe, it, expect } from 'vitest';
import { fmt, fmtK, pct, faStatus } from './formatters.js';

describe('fmt', () => {
    it('formats a number as Moroccan MAD currency', () => {
        const result = fmt(1234.5);
        expect(result).toContain('MAD');
        expect(result).toContain('234,50');
    });

    it('handles zero by defaulting to 0.00', () => {
        const result = fmt(0);
        expect(result).toContain('0,00');
        expect(result).toContain('MAD');
    });

    it('handles undefined/null by defaulting to 0.00', () => {
        const result = fmt(null);
        expect(result).toContain('0,00');
    });
});

describe('fmtK', () => {
    it('formats millions', () => {
        expect(fmtK(2_500_000)).toBe('2.50M');
    });

    it('formats thousands', () => {
        expect(fmtK(4_200)).toBe('4.2K');
    });

    it('formats numbers below 1000 as integers', () => {
        expect(fmtK(456)).toBe('456');
    });

    it('handles zero', () => {
        expect(fmtK(0)).toBe('0');
    });

    it('handles null/undefined by defaulting to 0', () => {
        expect(fmtK(null)).toBe('0');
    });
});

describe('pct', () => {
    it('calculates percentage', () => {
        expect(pct(25, 100)).toBe('25.0%');
    });

    it('rounds to one decimal', () => {
        expect(pct(1, 3)).toBe('33.3%');
    });

    it('returns 0.0% when total is 0', () => {
        expect(pct(10, 0)).toBe('0.0%');
    });
});

describe('faStatus', () => {
    it('returns ok status for low ratios', () => {
        const result = faStatus(10, { ok: 30, warn: 40 });
        expect(result.cls).toBe('ok');
        expect(result.label).toBe('NORMAL');
        expect(result.color).toBe('#0f9d58');
    });

    it('returns warn status for medium ratios', () => {
        const result = faStatus(35, { ok: 30, warn: 40 });
        expect(result.cls).toBe('warn');
        expect(result.label).toBe('ALERTE');
        expect(result.color).toBe('#f59e0b');
    });

    it('returns danger status for high ratios', () => {
        const result = faStatus(50, { ok: 30, warn: 40 });
        expect(result.cls).toBe('danger');
        expect(result.label).toBe('ANOMALIE');
        expect(result.color).toBe('#d93025');
    });

    it('uses default thresholds when none provided', () => {
        const result = faStatus(15);
        expect(result.cls).toBe('ok');
    });
});
