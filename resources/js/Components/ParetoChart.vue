<script setup>
import { computed } from 'vue';
import { pct } from '../lib/formatters';
import { COSTS_DEF } from '../lib/constants';

const props = defineProps({
    analysis: { type: Object, required: true },
    seuilsData: { type: Object, default: () => ({}) },
});

const items = computed(() => {
    const costs = props.analysis.costs || {};
    const pxMad = props.analysis.px_mad || 0;

    const all = [
        {
            id: 'px',
            label: "Prix d'Achat",
            color: '#3b82f6',
            val: pxMad,
            type: 'achat',
        },
        ...COSTS_DEF.map((c) => ({
            id: c.id,
            label: c.label,
            color: c.color,
            val: Number(costs[c.id]) || 0,
            type: c.type,
        })),
    ];

    const filtered = all.filter((item) => item.val > 0);
    filtered.sort((a, b) => b.val - a.val);
    return filtered;
});

const maxVal = computed(() => {
    if (items.value.length === 0) return 1;
    return Math.max(...items.value.map((i) => i.val));
});

const totalVal = computed(() => items.value.reduce((sum, i) => sum + i.val, 0));

const groupings = computed(() => {
    const achatItems = items.value.filter((i) => i.type === 'achat');
    const structItems = items.value.filter((i) => i.type === 'struct');
    const ineffItems = items.value.filter((i) => i.type === 'ineff');

    const groups = [];
    if (achatItems.length > 0) groups.push({ key: 'achat', label: "Prix d'Achat", items: achatItems });
    if (structItems.length > 0) groups.push({ key: 'struct', label: 'Frais Structurels', items: structItems });
    if (ineffItems.length > 0) groups.push({ key: 'ineff', label: 'Inefficiences', items: ineffItems });
    return groups;
});

function barWidth(val) {
    const vp = (val / maxVal.value) * 100;
    return Math.min(vp * 2.5, 100);
}

function typeBadge(type) {
    if (type === 'achat') return { text: 'ACHAT', cls: 'bg-[#dbeafe] text-[#1e40af]' };
    if (type === 'struct') return { text: 'STRUCT', cls: 'bg-[#d1fae5] text-[#065f46]' };
    return { text: 'INEFF', cls: 'bg-[#fee2e2] text-[#991b1b]' };
}
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-3">
            Pareto des Coûts
        </h3>

        <div v-if="items.length === 0" class="text-[11px] text-muted italic py-6 text-center">
            Aucun coût saisi
        </div>

        <template v-else>
            <div v-for="group in groupings" :key="group.key" class="mb-4 last:mb-0">
                <div class="text-[10px] font-mono text-muted tracking-wide uppercase mb-2 pl-1">
                    {{ group.label }}
                </div>
                <div
                    v-for="item in group.items"
                    :key="item.id"
                    class="flex items-center gap-2 mb-2 last:mb-0"
                >
                    <!-- Colored dot -->
                    <span
                        class="w-2 h-2 rounded-full shrink-0"
                        :style="{ backgroundColor: item.color }"
                    ></span>

                    <!-- Label + type badge -->
                    <div class="w-[140px] shrink-0 flex items-center gap-1.5">
                        <span class="text-[11px] text-text truncate">{{ item.label }}</span>
                        <span
                            class="text-[8px] font-mono font-bold px-1 py-px rounded"
                            :class="typeBadge(item.type).cls"
                        >
                            {{ typeBadge(item.type).text }}
                        </span>
                    </div>

                    <!-- Bar track + fill -->
                    <div class="flex-1 h-3 bg-[#f0f4f9] rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :style="{ width: barWidth(item.val) + '%', backgroundColor: item.color }"
                        ></div>
                    </div>

                    <!-- Percentage -->
                    <span class="w-12 text-right text-[11px] font-mono text-muted shrink-0">
                        {{ pct(item.val, totalVal) }}
                    </span>
                </div>
            </div>
        </template>
    </div>
</template>
