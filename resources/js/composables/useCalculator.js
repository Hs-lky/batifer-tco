import { ref, reactive, computed } from 'vue';
import { COSTS_DEF, INCO_BATIFER_FRET, DEFAULT_RATIO_SEUILS } from '../lib/constants';

export function computeTco({
    px_devise = 0,
    taux = 1,
    incoterm = 'CFR',
    pays = '',
    certOrigine = 'non',
    costs = {},
    produits = null,
    seuilsData = {},
    ratioSeuils = DEFAULT_RATIO_SEUILS,
    fret_montant = 0,
    fret_devise = 'MAD',
    fret_taux = 1,
    multiActive = false,
} = {}) {
    const px_mad = px_devise * taux;

    const allCosts = { ...costs };

    if (INCO_BATIFER_FRET.includes(incoterm)) {
        if (fret_montant) {
            allCosts.fret = Math.round(fret_montant * fret_taux * 100) / 100;
        }
    } else {
        allCosts.fret = 0;
    }

    const frais_total = Object.values(allCosts).reduce((sum, v) => sum + (Number(v) || 0), 0);
    const tco = px_mad + frais_total;
    const frais = frais_total - (Number(allCosts.fret) || 0);
    const baseFA = px_mad + (Number(allCosts.fret) || 0);
    const ratio_fa = baseFA > 0 ? (frais / baseFA) * 100 : 0;

    return {
        px_mad,
        costs: allCosts,
        frais,
        frais_total,
        tco,
        ratio_fa,
    };
}

export function useCalculator() {
    const form = reactive({
        px_devise: 0,
        taux: 1,
        incoterm: 'CFR',
        pays: '',
        certOrigine: 'non',
        fret_montant: 0,
        fret_devise: 'MAD',
        fret_taux: 1,
        multiActive: false,
    });

    const costInputs = reactive(
        Object.fromEntries(COSTS_DEF.map((c) => [c.id, 0]))
    );

    const ratioSeuils = reactive({ ...DEFAULT_RATIO_SEUILS });

    const seuilsData = reactive({});

    const analysis = computed(() =>
        computeTco({
            ...form,
            costs: { ...costInputs },
            ratioSeuils: { ...ratioSeuils },
            seuilsData: { ...seuilsData },
        })
    );

    return {
        form,
        costInputs,
        ratioSeuils,
        seuilsData,
        analysis,
    };
}
