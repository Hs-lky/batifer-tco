<script setup>
import { computed } from 'vue';
import { COSTS_DEF, PAYS_ALE, DEFAULT_RATIO_SEUILS } from '../lib/constants';
import { fmt, faStatus } from '../lib/formatters';

const props = defineProps({
    analysis: { type: Object, required: true },
    seuilsData: { type: Object, default: () => ({}) },
    ratioSeuils: { type: Object, default: () => ({ ...DEFAULT_RATIO_SEUILS }) },
});

const costs = computed(() => props.analysis.costs || {});
const pxMad = computed(() => props.analysis.px_mad || 0);
const tco = computed(() => props.analysis.tco || 0);
const fretVal = computed(() => Number(costs.value.fret) || 0);
const fraisVal = computed(() => props.analysis.frais || 0);
const ratioFa = computed(() => props.analysis.ratio_fa || 0);
const pays = computed(() => props.analysis.pays || '');
const certOrigine = computed(() => props.analysis.certOrigine || 'non');

const droitsTotal = computed(() => {
    return (Number(costs.value.douane) || 0) + (Number(costs.value.adval) || 0);
});

const structTotal = computed(() => {
    return COSTS_DEF
        .filter((c) => c.type === 'struct')
        .reduce((sum, c) => sum + (Number(costs.value[c.id]) || 0), 0);
});

const ineffTotal = computed(() => {
    return COSTS_DEF
        .filter((c) => c.type === 'ineff')
        .reduce((sum, c) => sum + (Number(costs.value[c.id]) || 0), 0);
});

const totalCosts = computed(() => structTotal.value + ineffTotal.value);

const structPct = computed(() => totalCosts.value > 0 ? (structTotal.value / totalCosts.value) * 100 : 0);
const ineffPct = computed(() => totalCosts.value > 0 ? (ineffTotal.value / totalCosts.value) * 100 : 0);

const certOrigineSavings = computed(() => {
    if (PAYS_ALE.includes(pays.value) && certOrigine.value === 'non') {
        return (Number(costs.value.douane) || 0) + (Number(costs.value.adval) || 0);
    }
    return 0;
});

const magasinageCost = computed(() => Number(costs.value.magasin) || 0);

const ratioStatus = computed(() => faStatus(ratioFa.value, props.ratioSeuils));

const ecoTotale = computed(() => certOrigineSavings.value + magasinageCost.value + ineffTotal.value);

function barCls(pct) {
    if (pct >= 60) return 'bg-danger';
    if (pct >= 30) return 'bg-warn';
    return 'bg-ok';
}
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-4">
            Synthèse
        </h3>

        <div class="grid grid-cols-3 gap-5">
            <!-- Colonne 1: Répartition TCO -->
            <div>
                <h4 class="text-[10px] font-mono text-muted tracking-wider uppercase mb-3 font-bold">
                    Répartition TCO
                </h4>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Prix Achat</span>
                        <span class="text-[11px] font-mono text-text font-bold">{{ fmt(pxMad) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Fret</span>
                        <span class="text-[11px] font-mono text-text font-bold">{{ fmt(fretVal) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Droits</span>
                        <span class="text-[11px] font-mono text-text font-bold">{{ fmt(droitsTotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Frais Approche</span>
                        <span class="text-[11px] font-mono text-text font-bold">{{ fmt(fraisVal) }}</span>
                    </div>
                    <div class="border-t border-border pt-2 flex justify-between items-center">
                        <span class="text-[11px] font-bold text-navy">TCO Total</span>
                        <span class="text-[12px] font-mono font-bold text-navy">{{ fmt(tco) }}</span>
                    </div>
                </div>
            </div>

            <!-- Colonne 2: Par Nature -->
            <div>
                <h4 class="text-[10px] font-mono text-muted tracking-wider uppercase mb-3 font-bold">
                    Par Nature
                </h4>
                <div class="space-y-3.5">
                    <!-- Structurels -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[11px] text-text">Structurels</span>
                            <span class="text-[11px] font-mono text-muted">{{ fmt(structTotal) }}</span>
                        </div>
                        <div class="h-2 bg-[#f0f4f9] rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all bg-[#60a5fa]"
                                :style="{ width: structPct + '%' }"
                            ></div>
                        </div>
                        <div class="text-[9px] font-mono text-muted mt-0.5">
                            {{ structPct.toFixed(1) }}%
                        </div>
                    </div>

                    <!-- Inefficiences -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[11px] text-text">Inefficiences</span>
                            <span class="text-[11px] font-mono text-danger">{{ fmt(ineffTotal) }}</span>
                        </div>
                        <div class="h-2 bg-[#f0f4f9] rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all bg-danger"
                                :style="{ width: ineffPct + '%' }"
                            ></div>
                        </div>
                        <div class="text-[9px] font-mono text-danger mt-0.5 font-bold">
                            {{ ineffPct.toFixed(1) }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne 3: Économies Potentielles -->
            <div>
                <h4 class="text-[10px] font-mono text-muted tracking-wider uppercase mb-3 font-bold">
                    Économies Potentielles
                </h4>
                <div class="space-y-2.5">
                    <div v-if="certOrigineSavings > 0" class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Cert. Origine</span>
                        <span class="text-[11px] font-mono text-ok font-bold">{{ fmt(certOrigineSavings) }}</span>
                    </div>
                    <div v-if="magasinageCost > 0" class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Magasinage</span>
                        <span class="text-[11px] font-mono text-warn font-bold">{{ fmt(magasinageCost) }}</span>
                    </div>
                    <div v-if="ineffTotal > 0" class="flex justify-between items-center">
                        <span class="text-[11px] text-text">Inefficiences</span>
                        <span class="text-[11px] font-mono text-danger font-bold">{{ fmt(ineffTotal) }}</span>
                    </div>

                    <!-- Total économies -->
                    <div v-if="ecoTotale > 0" class="border-t border-border pt-2 flex justify-between items-center">
                        <span class="text-[11px] font-bold text-text">Total Économies</span>
                        <span class="text-[11px] font-mono font-bold text-ok">{{ fmt(ecoTotale) }}</span>
                    </div>

                    <!-- Ratio FA -->
                    <div class="border-t border-border pt-3 mt-2">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] font-mono text-muted tracking-wider uppercase">Ratio FA</span>
                            <span
                                class="inline-block px-1.5 py-px rounded text-[9px] font-bold"
                                :class="{
                                    'bg-ok/10 text-ok': ratioStatus.cls === 'ok',
                                    'bg-warn/10 text-warn': ratioStatus.cls === 'warn',
                                    'bg-danger/10 text-danger': ratioStatus.cls === 'danger',
                                }"
                            >
                                {{ ratioStatus.label }}
                            </span>
                        </div>
                        <div class="text-2xl font-mono font-bold leading-tight" :style="{ color: ratioStatus.color }">
                            {{ ratioFa.toFixed(1) }}%
                        </div>
                        <div class="text-[9px] text-muted mt-0.5 leading-tight">
                            Seuil: &lt; {{ props.ratioSeuils.ok }}% OK,
                            &gt; {{ props.ratioSeuils.warn }}% Alerte
                        </div>
                    </div>

                    <div v-if="ecoTotale === 0 && certOrigineSavings === 0 && ineffTotal === 0" class="text-[11px] text-muted italic py-2">
                        Aucune économie identifiée
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
