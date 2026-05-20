<script setup>
import { computed } from 'vue';
import { COSTS_DEF } from '../lib/constants';
import { pct } from '../lib/formatters';

const props = defineProps({
    analysis: { type: Object, required: true },
    seuilsData: { type: Object, default: () => ({}) },
});

function getThreshold(c) {
    const s = props.seuilsData[c.id];
    if (s) return { ok: s.seuil_ok, warn: s.seuil_warn, bad: s.seuil_bad };
    return { ok: c.seuil_ok, warn: c.seuil_warn, bad: c.seuil_bad };
}

const items = computed(() => {
    const costs = props.analysis.costs || {};
    const tco = props.analysis.tco || 0;

    return COSTS_DEF
        .filter((c) => (Number(costs[c.id]) || 0) > 0)
        .map((c) => {
            const val = Number(costs[c.id]) || 0;
            const vp = tco > 0 ? (val / tco) * 100 : 0;
            const t = getThreshold(c);

            let cls, statusLabel;
            if (vp <= t.ok) { cls = 'ok'; statusLabel = 'NORMAL'; }
            else if (vp <= t.warn) { cls = 'warn'; statusLabel = 'ALERTE'; }
            else { cls = 'danger'; statusLabel = 'ANOMALIE'; }

            const scale = Math.max(t.bad * 1.5, vp * 1.15, 8);

            return {
                id: c.id,
                label: c.label,
                color: c.color,
                val,
                vp,
                cls,
                statusLabel,
                t,
                scale,
                okPct: (t.ok / scale) * 100,
                warnPct: ((t.warn - t.ok) / scale) * 100,
                badPct: ((t.bad - t.warn) / scale) * 100,
                fillPct: Math.min((vp / scale) * 100, 100),
            };
        });
});

const statusColors = {
    ok: { border: 'border-l-ok', text: 'text-ok', bg: 'bg-ok/10' },
    warn: { border: 'border-l-warn', text: 'text-warn', bg: 'bg-warn/10' },
    danger: { border: 'border-l-danger', text: 'text-danger', bg: 'bg-danger/10' },
};

const zoneColors = {
    ok: 'bg-ok',
    warn: 'bg-warn',
    danger: 'bg-danger',
};
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-3">
            Tolérances par Frais
        </h3>

        <div v-if="items.length === 0" class="text-[11px] text-muted italic py-6 text-center">
            Aucun frais saisi
        </div>

        <div v-else class="space-y-2.5">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex items-center gap-3 border-l-[3px] rounded-l-md pl-3 py-1.5"
                :class="statusColors[item.cls].border"
            >
                <!-- Colored dot + label -->
                <div class="flex items-center gap-1.5 w-[150px] shrink-0">
                    <span
                        class="w-2 h-2 rounded-full shrink-0"
                        :style="{ backgroundColor: item.color }"
                    ></span>
                    <span class="text-[11px] text-text truncate">{{ item.label }}</span>
                </div>

                <!-- Percentage value + status label -->
                <div class="w-[90px] shrink-0 flex items-center gap-2">
                    <span class="text-[12px] font-mono font-bold text-text">
                        {{ item.vp.toFixed(1) }}%
                    </span>
                    <span
                        class="inline-block px-1.5 py-px rounded text-[9px] font-bold"
                        :class="statusColors[item.cls].bg + ' ' + statusColors[item.cls].text"
                    >
                        {{ item.statusLabel }}
                    </span>
                </div>

                <!-- Gauge track -->
                <div class="flex-1 relative">
                    <!-- Zone overlays -->
                    <div
                        class="h-[6px] rounded-[3px] overflow-hidden relative"
                        :style="{ backgroundColor: '#f0f4f9' }"
                    >
                        <!-- Green zone -->
                        <div
                            class="absolute top-0 left-0 h-full rounded-l-[3px]"
                            :style="{ width: item.okPct + '%', backgroundColor: zoneColors.ok }"
                        ></div>
                        <!-- Yellow zone -->
                        <div
                            class="absolute top-0 h-full"
                            :style="{ left: item.okPct + '%', width: item.warnPct + '%', backgroundColor: zoneColors.warn }"
                        ></div>
                        <!-- Red zone -->
                        <div
                            class="absolute top-0 h-full rounded-r-[3px]"
                            :style="{ left: (item.okPct + item.warnPct) + '%', width: item.badPct + '%', backgroundColor: zoneColors.danger }"
                        ></div>
                        <!-- Fill bar (actual value) -->
                        <div
                            class="absolute top-0 h-full rounded-[3px] transition-all"
                            :style="{
                                width: item.fillPct + '%',
                                backgroundColor: item.color,
                            }"
                        ></div>
                    </div>

                    <!-- Threshold text -->
                    <div class="flex justify-between mt-0.5 text-[8px] font-mono text-muted leading-none">
                        <span>{{ item.t.ok }}%</span>
                        <span>{{ item.t.warn }}%</span>
                        <span>{{ item.t.bad }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
