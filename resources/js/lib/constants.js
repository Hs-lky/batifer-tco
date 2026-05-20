export const COSTS_DEF = [
    { id: 'fret',     label: 'Fret Maritime',         color: '#60a5fa', type: 'struct', seuil_ok: 5,  seuil_warn: 8,  seuil_bad: 12 },
    { id: 'assur',    label: 'Assurance',              color: '#a78bfa', type: 'struct', seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'douane',   label: 'Frais douane',           color: '#f59e0b', type: 'struct', seuil_ok: 2,  seuil_warn: 5,  seuil_bad: 10 },
    { id: 'transit',  label: 'Transit / Transitaire',  color: '#34d399', type: 'struct', seuil_ok: 1,  seuil_warn: 2,  seuil_bad: 4  },
    { id: 'banque',   label: 'Frais Bancaires',        color: '#9ca3af', type: 'struct', seuil_ok: 0.3, seuil_warn: 0.8, seuil_bad: 1.5 },
    { id: 'avis',     label: 'Avis Arrivée',           color: '#6ee7b7', type: 'struct', seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'manut',    label: 'Manutention Portuaire',  color: '#5eead4', type: 'struct', seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'acconage', label: 'Acconage',               color: '#2dd4bf', type: 'struct', seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'tic',      label: 'TIC (Taxe Intérieure)',  color: '#f97316', type: 'struct', seuil_ok: 1,  seuil_warn: 2,  seuil_bad: 4  },
    { id: 'tlocal',   label: 'Transport Local',        color: '#a3e635', type: 'struct', seuil_ok: 1,  seuil_warn: 2,  seuil_bad: 4  },
    { id: 'magasin',  label: 'Magasinage',             color: '#f87171', type: 'ineff',  seuil_ok: 0,  seuil_warn: 0.5, seuil_bad: 1  },
    { id: 'adval',    label: 'Ad Valorem',             color: '#fbbf24', type: 'struct', seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'autre1',   label: 'Autres Frais',           color: '#c084fc', type: 'ineff',  seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
    { id: 'autre2',   label: 'Frais Additionnels',     color: '#818cf8', type: 'ineff',  seuil_ok: 0.5, seuil_warn: 1,  seuil_bad: 2  },
];

export const PAYS_ALE = ['ESPAGNE', 'ALLEMAGNE', 'FRANCE', 'PORTUGAL', 'ITALIE', 'BELGIQUE', 'TURQUIE', 'EGYPTE'];

export const UNITES = ['KG', 'T', 'M²', 'Unité', 'ML', 'M³', 'L', 'Rouleau', 'Palette'];

export const INCO_BATIFER_FRET = ['EXW', 'FOB', 'FCA', 'FAS'];

export const INCO_OPTIONS = [
    { value: 'EXW', label: 'EXW' }, { value: 'FCA', label: 'FCA' }, { value: 'FAS', label: 'FAS' },
    { value: 'FOB', label: 'FOB' }, { value: 'CFR', label: 'CFR' }, { value: 'CPT', label: 'CPT' },
    { value: 'CIP', label: 'CIP' }, { value: 'CIF', label: 'CIF' }, { value: 'DAP', label: 'DAP' },
    { value: 'DDP', label: 'DDP' }, { value: 'DPU', label: 'DPU' },
];

export const PAYS_OPTIONS = [
    { value: 'ESPAGNE', label: 'Espagne (ALE-UE)' }, { value: 'ALLEMAGNE', label: 'Allemagne (ALE-UE)' },
    { value: 'FRANCE', label: 'France (ALE-UE)' }, { value: 'PORTUGAL', label: 'Portugal (ALE-UE)' },
    { value: 'ITALIE', label: 'Italie (ALE-UE)' }, { value: 'BELGIQUE', label: 'Belgique (ALE-UE)' },
    { value: 'TURQUIE', label: 'Turquie (ALE-Turquie)' }, { value: 'EGYPTE', label: 'Égypte (ALE-Agadir)' },
    { value: 'CHINE', label: 'Chine (Hors ALE)' }, { value: 'AUTRE', label: 'Autre pays' },
];

export const FAMILLE_OPTIONS = [
    { value: 'ACIER', label: 'Acier' }, { value: 'PAPIER', label: 'Papier' },
    { value: 'ETANCHEITE', label: 'Étanchéité' }, { value: 'FOURNITURE', label: 'Fourniture' },
    { value: 'AUTRE', label: 'Autre' },
];

export const DEVISE_OPTIONS = [
    { value: 'EUR', label: 'EUR €' }, { value: 'USD', label: 'USD $' },
    { value: 'MAD', label: 'MAD' }, { value: 'GBP', label: 'GBP £' },
];

export const DEFAULT_RATIO_SEUILS = { ok: 30, warn: 40, bad: 60 };
