import { describe, it, expect } from 'vitest';
import { computeTco } from '../../../resources/js/composables/useCalculator.js';

describe('computeTco', () => {
    it('computes px_mad correctly', () => {
        const result = computeTco({ px_devise: 1000, taux: 10.72 });
        expect(result.px_mad).toBeCloseTo(10720);
    });

    it('computes TCO as px_mad + all costs', () => {
        const result = computeTco({
            px_devise: 1000,
            taux: 10.72,
            incoterm: 'FOB',
            costs: { fret: 500, douane: 200, transit: 100 },
        });
        expect(result.px_mad).toBeCloseTo(10720);
        expect(result.tco).toBeCloseTo(10720 + 800);
    });

    it('computes ratio FA excluding freight', () => {
        const result = computeTco({
            px_devise: 1000,
            taux: 10.72,
            incoterm: 'FOB',
            costs: { fret: 500, douane: 200 },
        });
        expect(result.frais).toBeCloseTo(200);
        expect(result.ratio_fa).toBeCloseTo((200 / (10720 + 500)) * 100);
    });

    it('returns 0 ratio_fa when base is zero', () => {
        const result = computeTco({
            px_devise: 0,
            taux: 0,
            costs: { douane: 100 },
        });
        expect(result.px_mad).toBe(0);
        expect(result.ratio_fa).toBe(0);
    });

    it('computes fret from incoterm Batifer-payant', () => {
        const result = computeTco({
            px_devise: 1000,
            taux: 10.72,
            incoterm: 'EXW',
            fret_montant: 300,
        });
        expect(result.costs.fret).toBe(300);
    });

    it('zeroes fret when incoterm is fournisseur-payant', () => {
        const result = computeTco({
            px_devise: 1000,
            taux: 10.72,
            incoterm: 'CIF',
            fret_montant: 300,
        });
        expect(result.costs.fret).toBe(0);
    });
});
