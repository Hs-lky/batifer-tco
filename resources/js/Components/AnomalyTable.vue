<script setup>
import { computed } from 'vue';
import { COSTS_DEF, PAYS_ALE } from '../lib/constants';
import { fmt, pct } from '../lib/formatters';

const props = defineProps({
    analysis: { type: Object, required: true },
    seuilsData: { type: Object, default: () => ({}) },
});

function getThreshold(c) {
    const s = props.seuilsData[c.id];
    if (s) return { ok: s.seuil_ok, warn: s.seuil_warn, bad: s.seuil_bad };
    return { ok: c.seuil_ok, warn: c.seuil_warn, bad: c.seuil_bad };
}

function statusInfo(val, tco, c) {
    const vp = tco > 0 ? (val / tco) * 100 : 0;
    const t = getThreshold(c);
    if (vp <= t.ok) return { cls: 'ok', icon: '✅', label: 'NORMAL', color: '#0f9d58' };
    if (vp <= t.warn) return { cls: 'warn', icon: '⚠️', label: 'ALERTE', color: '#f59e0b' };
    return { cls: 'danger', icon: '🔴', label: 'ANOMALIE', color: '#d93025' };
}

const rows = computed(() => {
    const costs = props.analysis.costs || {};
    const tco = props.analysis.tco || 0;

    const baseRows = COSTS_DEF
        .filter((c) => (Number(costs[c.id]) || 0) > 0)
        .map((c) => {
            const val = Number(costs[c.id]) || 0;
            const si = statusInfo(val, tco, c);
            return {
                id: c.id,
                label: c.label,
                color: c.color,
                val,
                vp: tco > 0 ? (val / tco) * 100 : 0,
                type: c.type === 'struct' ? 'Structurel' : 'Inefficience',
                typeBadgeCls: c.type === 'struct'
                    ? 'bg-[#dbeafe] text-[#1e40af]'
                    : 'bg-[#fee2e2] text-[#991b1b]',
                si,
                isSpecial: false,
            };
        });

    return baseRows;
});

const specialRows = computed(() => {
    const costs = props.analysis.costs || {};
    const pays = props.analysis.pays || '';
    const certOrigine = props.analysis.certOrigine || 'non';
    const specials = [];

    // CERT. ORIGINE MANQUANT: country in ALE but paying customs without cert of origin
    if (PAYS_ALE.includes(pays) && certOrigine === 'non' && (Number(costs.douane) || 0) > 0) {
        specials.push({
            id: 'cert-origine',
            label: 'CERT. ORIGINE MANQUANT',
            color: '#d93025',
            val: Number(costs.douane) || 0,
            vp: props.analysis.tco > 0 ? ((Number(costs.douane) || 0) / props.analysis.tco) * 100 : 0,
            type: 'Anomalie',
            typeBadgeCls: 'bg-[#fee2e2] text-[#991b1b] border border-[#fca5a5]',
            si: { cls: 'danger', icon: '🔴', label: 'ANOMALIE', color: '#d93025' },
            isSpecial: true,
        });
    }

    // RETARD DOC.: magasinage costs indicate documentation delays
    if ((Number(costs.magasin) || 0) > 0) {
        specials.push({
            id: 'retard-doc',
            label: 'RETARD DOC.',
            color: '#d93025',
            val: Number(costs.magasin) || 0,
            vp: props.analysis.tco > 0 ? ((Number(costs.magasin) || 0) / props.analysis.tco) * 100 : 0,
            type: 'Alerte',
            typeBadgeCls: 'bg-[#fee2e2] text-[#991b1b] border border-[#fca5a5]',
            si: { cls: 'danger', icon: '🔴', label: 'RETARD', color: '#d93025' },
            isSpecial: true,
        });
    }

    return specials;
});

const allRows = computed(() => [...rows.value, ...specialRows.value]);
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-3">
            Détail des Anomalies
        </h3>

        <div v-if="allRows.length === 0" class="text-[11px] text-muted italic py-6 text-center">
            Aucun frais saisi
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-[#f0f4f9]">
                        <th class="px-3 py-2 text-[10px] font-mono text-muted tracking-wider uppercase font-bold">Poste</th>
                        <th class="px-3 py-2 text-[10px] font-mono text-muted tracking-wider uppercase font-bold text-right">Montant</th>
                        <th class="px-3 py-2 text-[10px] font-mono text-muted tracking-wider uppercase font-bold text-right">% TCO</th>
                        <th class="px-3 py-2 text-[10px] font-mono text-muted tracking-wider uppercase font-bold">Type</th>
                        <th class="px-3 py-2 text-[10px] font-mono text-muted tracking-wider uppercase font-bold">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in allRows"
                        :key="row.id"
                        class="border-t border-border hover:bg-[#f0f4f9]/50 transition-colors"
                        :class="row.isSpecial ? 'bg-[#fef2f2] border-l-[3px] border-l-danger' : ''"
                    >
                        <!-- Poste: dot + label -->
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="w-2 h-2 rounded-full shrink-0"
                                    :style="{ backgroundColor: row.color }"
                                ></span>
                                <span
                                    class="text-[11px] font-medium"
                                    :class="row.isSpecial ? 'text-danger font-bold' : 'text-text'"
                                >
                                    {{ row.label }}
                                </span>
                            </div>
                        </td>

                        <!-- Montant -->
                        <td class="px-3 py-2.5 text-right">
                            <span class="text-[11px] font-mono text-text">{{ fmt(row.val) }}</span>
                        </td>

                        <!-- % TCO -->
                        <td class="px-3 py-2.5 text-right">
                            <span class="text-[11px] font-mono text-muted">{{ row.vp.toFixed(1) }}%</span>
                        </td>

                        <!-- Type badge -->
                        <td class="px-3 py-2.5">
                            <span
                                class="inline-block text-[9px] font-mono font-bold px-1.5 py-0.5 rounded"
                                :class="row.typeBadgeCls"
                            >
                                {{ row.type }}
                            </span>
                        </td>

                        <!-- Statut: icon + label -->
                        <td class="px-3 py-2.5">
                            <span
                                class="text-[10px] font-mono font-bold"
                                :style="{ color: row.si.color }"
                            >
                                {{ row.si.icon }} {{ row.si.label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
