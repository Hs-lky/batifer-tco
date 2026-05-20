<script setup>
import { computed } from 'vue';
import { fmt, faStatus } from '../lib/formatters';
import { COSTS_DEF } from '../lib/constants';

const props = defineProps({
    analysis: { type: Object, required: true },
    ratioSeuils: { type: Object, default: () => ({ ok: 30, warn: 40 }) },
});

const pxFret = computed(() => (props.analysis.px_mad || 0) + ((props.analysis.costs && props.analysis.costs.fret) || 0));

const inefficiencyTotal = computed(() => {
    const costs = props.analysis.costs || {};
    return COSTS_DEF
        .filter((c) => c.type === 'ineff')
        .reduce((sum, c) => sum + (Number(costs[c.id]) || 0), 0);
});

const ratioStatus = computed(() => faStatus(props.analysis.ratio_fa || 0, props.ratioSeuils));

const tco = computed(() => props.analysis.tco || 0);
const pxMad = computed(() => props.analysis.px_mad || 0);
const fretVal = computed(() => (props.analysis.costs && props.analysis.costs.fret) || 0);
const ratioFa = computed(() => props.analysis.ratio_fa || 0);
</script>

<template>
    <div class="grid grid-cols-4 gap-3.5">
        <!-- Card 1: TCO Total -->
        <div class="relative overflow-hidden bg-card border border-border rounded-[11px] p-3.5 shadow">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-navy rounded-t-[11px]"></div>
            <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1">TCO Total</div>
            <div class="text-lg font-mono font-bold text-navy leading-tight">{{ fmt(tco) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Coût total importé</div>
        </div>

        <!-- Card 2: Prix d'Achat + Fret -->
        <div class="relative overflow-hidden bg-card border border-border rounded-[11px] p-3.5 shadow">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[#3b82f6] rounded-t-[11px]"></div>
            <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1">Prix d'Achat + Fret</div>
            <div class="text-lg font-mono font-bold text-text leading-tight">{{ fmt(pxFret) }}</div>
            <div class="text-[10px] text-muted mt-0.5 space-y-px">
                <div>Achat : {{ fmt(pxMad) }}</div>
                <div>Fret : {{ fmt(fretVal) }}</div>
            </div>
        </div>

        <!-- Card 3: Ratio FA -->
        <div class="relative overflow-hidden bg-card border border-border rounded-[11px] p-3.5 shadow">
            <div
                class="absolute top-0 left-0 right-0 h-[3px] rounded-t-[11px]"
                :style="{ backgroundColor: ratioStatus.color }"
            ></div>
            <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1">Ratio FA</div>
            <div
                class="text-lg font-mono font-bold leading-tight"
                :style="{ color: ratioStatus.color }"
            >
                {{ ratioFa.toFixed(1) }}%
            </div>
            <div class="text-[10px] text-muted mt-0.5">
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
                <span class="ml-1">Seuil &lt; {{ ratioSeuils.ok }}% OK, &gt; {{ ratioSeuils.warn }}% Alerte</span>
            </div>
        </div>

        <!-- Card 4: Coûts Inefficience -->
        <div class="relative overflow-hidden bg-card border border-border rounded-[11px] p-3.5 shadow">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-danger rounded-t-[11px]"></div>
            <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1">Coûts Inefficience</div>
            <div class="text-lg font-mono font-bold text-danger leading-tight">{{ fmt(inefficiencyTotal) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Magasinage + Autres + Add.</div>
        </div>
    </div>
</template>
