<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { faStatus, fmt, fmtK } from '../lib/formatters';
import { COSTS_DEF, PAYS_ALE } from '../lib/constants';
import { computeTco } from '../composables/useCalculator';

const props = defineProps({
    dossier: { type: Object, required: true },
});

const emit = defineEmits(['delete']);

// ─── Transform costs array to flat object ─────────────────────────
function costsFromCouts(couts) {
    const obj = {};
    if (Array.isArray(couts)) {
        couts.forEach((c) => { obj[c.cout_id] = Number(c.montant) || 0; });
    }
    return obj;
}

// ─── Computed TCO / FA data ─────────────────────────────────────────
const tcoData = computed(() => {
    const d = props.dossier;
    const costs = costsFromCouts(d.couts);
    return computeTco({
        px_devise: Number(d.px_devise) || 0,
        taux: Number(d.taux) || 1,
        incoterm: d.incoterm || 'CFR',
        pays: d.pays || '',
        certOrigine: d.cert_origine || 'non',
        costs,
        fret_montant: Number(d.fret_montant_orig) || 0,
        fret_devise: d.fret_devise_orig || 'MAD',
        fret_taux: Number(d.fret_taux_orig) || 1,
    });
});

const ratioStatus = computed(() => faStatus(tcoData.value.ratio_fa));

const inefficiencyTotal = computed(() => {
    const costs = tcoData.value.costs || {};
    return COSTS_DEF
        .filter((c) => c.type === 'ineff')
        .reduce((sum, c) => sum + (Number(costs[c.id]) || 0), 0);
});

const hasProduits = computed(() =>
    Array.isArray(props.dossier.produits) && props.dossier.produits.length > 0
);

const isAleCountry = computed(() =>
    PAYS_ALE.includes((props.dossier.pays || '').toUpperCase())
);

const certMissing = computed(() =>
    isAleCountry.value && props.dossier.cert_origine !== 'oui'
);

// ─── Date formatting ──────────────────────────────────────────────
const formattedDate = computed(() => {
    const d = props.dossier.created_at;
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString('fr-MA', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
    } catch { return d; }
});

// ─── Multi-product table data ──────────────────────────────────────
const productRows = computed(() => {
    if (!hasProduits.value) return [];
    const d = props.dossier;
    const pxMad = tcoData.value.px_mad;
    const tco = tcoData.value.tco;
    return d.produits.map((p) => {
        const ratio = Number(p.ratio) || 0;
        const share = ratio / 100;
        return {
            description: p.description || '—',
            qte: Number(p.quantite) || 0,
            unite: p.unite || 'KG',
            ratio,
            puAchat: pxMad * share,
            puTco: tco * share,
            part: ratio.toFixed(1) + '%',
        };
    });
});

// ─── Ineff badge data ────────────────────────────────────────────
const ineffItems = computed(() => {
    const costs = tcoData.value.costs || {};
    return COSTS_DEF
        .filter((c) => c.type === 'ineff' && (Number(costs[c.id]) || 0) > 0)
        .map((c) => ({ label: c.label, montant: Number(costs[c.id]) }));
});

// ─── Actions ─────────────────────────────────────────────────────
function doLoad() {
    router.get(route('dossiers.show', props.dossier.id));
}

function doAnalyze() {
    router.get(route('analyse'), { dossier: props.dossier.id });
}

function doDuplicate() {
    router.post(route('dossiers.store'), {
        ref: (props.dossier.ref || '') + ' (copie)',
        frs: props.dossier.frs || '',
        pays: props.dossier.pays,
        incoterm: props.dossier.incoterm,
        famille: props.dossier.famille,
        devise: props.dossier.devise,
        unite: props.dossier.unite,
        cert_origine: props.dossier.cert_origine,
        px_devise: props.dossier.px_devise,
        taux: props.dossier.taux,
        qte: props.dossier.qte,
        notes: props.dossier.notes,
        fret_montant_orig: props.dossier.fret_montant_orig,
        fret_devise_orig: props.dossier.fret_devise_orig,
        fret_taux_orig: props.dossier.fret_taux_orig,
        costs: costsFromCouts(props.dossier.couts),
        produits: (props.dossier.produits || []).map((p) => ({
            desc: p.description,
            qte: p.quantite,
            unite: p.unite,
            ratio: p.ratio,
        })),
    });
}

function doDelete(e) {
    e.stopPropagation();
    if (!confirm(`Supprimer le dossier « ${props.dossier.ref} » ?`)) return;
    router.delete(route('dossiers.destroy', props.dossier.id), {
        onSuccess: () => emit('delete'),
    });
}
</script>

<template>
    <div class="bg-card border border-border rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
        <!-- ═══ Header ═══ -->
        <div class="px-4 py-3 border-b border-border bg-[#f8fafc] flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <span class="font-mono font-bold text-navy text-sm">{{ dossier.ref }}</span>
                <span class="text-xs text-muted truncate max-w-[180px]">{{ dossier.frs }}</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[10px] bg-[#e8ecf4] text-muted px-2 py-0.5 rounded font-mono">
                    {{ dossier.pays || '—' }}
                </span>
                <span class="text-[10px] bg-[#e8ecf4] text-muted px-2 py-0.5 rounded font-mono">
                    {{ dossier.incoterm || '—' }}
                </span>
                <span class="text-[10px] bg-[#e8ecf4] text-muted px-2 py-0.5 rounded font-mono">
                    {{ dossier.famille || '—' }}
                </span>
                <span class="text-[10px] text-muted/60">{{ formattedDate }}</span>
            </div>
        </div>

        <!-- ═══ Body: Status Badges ═══ -->
        <div class="px-4 py-3 flex items-center gap-2 flex-wrap">
            <!-- FA Ratio badge -->
            <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                :class="{
                    'bg-ok/10 text-ok': ratioStatus.cls === 'ok',
                    'bg-warn/10 text-warn': ratioStatus.cls === 'warn',
                    'bg-danger/10 text-danger': ratioStatus.cls === 'danger',
                }"
            >
                <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: ratioStatus.color }"></span>
                FA {{ tcoData.ratio_fa.toFixed(1) }}% — {{ ratioStatus.label }}
            </span>

            <!-- Cert Origine warning -->
            <span
                v-if="certMissing"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-warn/10 text-warn"
            >
                ⚠️ Certificat d'Origine manquant
            </span>

            <!-- Ineff markers -->
            <span
                v-for="item in ineffItems"
                :key="item.label"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-danger/10 text-danger"
            >
                💸 {{ item.label }} : {{ fmt(item.montant) }}
            </span>
        </div>

        <!-- ═══ Multi-Product Table ═══ -->
        <div v-if="hasProduits" class="px-4 pb-3">
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] border-collapse">
                    <thead>
                        <tr class="text-muted font-mono text-[10px] uppercase tracking-wider border-b border-border">
                            <th class="text-left py-1.5 font-medium">Produit</th>
                            <th class="text-right py-1.5 font-medium">Qté</th>
                            <th class="text-right py-1.5 font-medium">PU Achat</th>
                            <th class="text-right py-1.5 font-medium">PU TCO</th>
                            <th class="text-right py-1.5 font-medium">Part</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, idx) in productRows"
                            :key="idx"
                            class="border-b border-border/50 last:border-0"
                        >
                            <td class="py-1 pr-2 text-text truncate max-w-[140px]">{{ row.description }}</td>
                            <td class="py-1 text-right font-mono text-navy">{{ row.qte }} {{ row.unite }}</td>
                            <td class="py-1 text-right font-mono">{{ fmt(row.puAchat) }}</td>
                            <td class="py-1 text-right font-mono text-navy font-bold">{{ fmt(row.puTco) }}</td>
                            <td class="py-1 text-right font-mono text-muted">{{ row.part }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══ Mono-Product KPI Badges ═══ -->
        <div v-else class="px-4 pb-3 flex items-center gap-3 flex-wrap">
            <div class="bg-[#f0f4f9] rounded-lg px-3 py-2 text-center min-w-[80px]">
                <div class="text-[10px] text-muted uppercase tracking-wider font-mono">TCO</div>
                <div class="text-sm font-mono font-bold text-navy">{{ fmt(tcoData.tco) }}</div>
            </div>
            <div class="bg-[#f0f4f9] rounded-lg px-3 py-2 text-center min-w-[80px]">
                <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Prix MAD</div>
                <div class="text-sm font-mono font-bold text-text">{{ fmt(tcoData.px_mad) }}</div>
            </div>
            <div class="bg-[#f0f4f9] rounded-lg px-3 py-2 text-center min-w-[80px]">
                <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Qté</div>
                <div class="text-sm font-mono font-bold text-text">{{ dossier.qte || 0 }} {{ dossier.unite || 'KG' }}</div>
            </div>
            <div class="bg-[#f0f4f9] rounded-lg px-3 py-2 text-center min-w-[80px]">
                <div class="text-[10px] text-muted uppercase tracking-wider font-mono">PU MAD</div>
                <div class="text-sm font-mono font-bold text-text">
                    {{ dossier.qte > 0 ? fmt(tcoData.px_mad / dossier.qte) : '—' }}
                </div>
            </div>
        </div>

        <!-- ═══ Action Buttons ═══ -->
        <div class="px-4 py-2.5 border-t border-border bg-white flex items-center gap-2">
            <button
                @click.stop="doLoad"
                class="px-3 py-1.5 rounded-md bg-[#e8f0fe] text-navy text-[11px] font-semibold border-none cursor-pointer hover:bg-[#d4e2fc] transition-colors"
            >
                📂 Charger
            </button>
            <button
                @click.stop="doAnalyze"
                class="px-3 py-1.5 rounded-md bg-[#e6f7f3] text-accent text-[11px] font-semibold border-none cursor-pointer hover:bg-[#cdf0e6] transition-colors"
            >
                📊 Analyser
            </button>
            <button
                @click.stop="doDuplicate"
                class="px-3 py-1.5 rounded-md bg-[#f0f4f9] text-muted text-[11px] font-semibold border-none cursor-pointer hover:bg-[#dce3ee] transition-colors"
            >
                📋 Dupliquer
            </button>
            <button
                @click="doDelete"
                class="ml-auto px-3 py-1.5 rounded-md bg-transparent text-danger text-[11px] font-semibold border border-danger/20 cursor-pointer hover:bg-danger/5 transition-colors"
            >
                🗑
            </button>
        </div>
    </div>
</template>
