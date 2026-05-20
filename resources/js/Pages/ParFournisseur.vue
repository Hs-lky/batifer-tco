<script setup>
import { ref, computed } from 'vue';
import { fmtK, faStatus } from '../lib/formatters';
import { COSTS_DEF } from '../lib/constants';

const props = defineProps({
    groups: { type: Array, default: () => [] },
});

const search = ref('');
const sortBy = ref('tco');

const filteredGroups = computed(() => {
    let arr = [...props.groups];
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        arr = arr.filter((g) => g.frs.toLowerCase().includes(q));
    }
    if (sortBy.value === 'tco') {
        return arr.sort((a, b) => b.tco - a.tco);
    } else if (sortBy.value === 'ratio_fa') {
        return arr.sort((a, b) => (b.avg_fa || 0) - (a.avg_fa || 0));
    } else if (sortBy.value === 'dossiers') {
        return arr.sort((a, b) => b.dossiers - a.dossiers);
    } else if (sortBy.value === 'ineff') {
        return arr.sort((a, b) => (b.ineff_total || 0) - (a.ineff_total || 0));
    }
    return arr;
});

const familleColors = {
    ACIER: '#f87171',
    PAPIER: '#fbbf24',
    ETANCHEITE: '#60a5fa',
    FOURNITURE: '#34d399',
    AUTRE: '#a78bfa',
};
const defaultFamColor = '#94a3b8';

function familleColor(famille) {
    return familleColors[famille] || defaultFamColor;
}

function costLabel(costId) {
    const def = COSTS_DEF.find((c) => c.id === costId);
    return def ? def.label : costId;
}

function costColor(costId) {
    const def = COSTS_DEF.find((c) => c.id === costId);
    return def ? def.color : '#94a3b8';
}

function faStatusFor(avgFa) {
    return faStatus(avgFa, { ok: 30, warn: 40 });
}

const globalMaxCost = computed(() => {
    let max = 1;
    for (const g of filteredGroups.value) {
        for (const val of Object.values(g.costs_sum || {})) {
            if (val > max) max = val;
        }
    }
    return max;
});
</script>

<template>
    <div class="flex-1 bg-bg p-5 overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase">
                🏭 TCO par Fournisseur
            </h2>
            <div class="flex items-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un fournisseur..."
                    class="bg-white border border-border rounded-md text-text font-mono text-[11px] py-1.5 px-2.5 focus:border-accent outline-none w-56"
                />
                <select
                    v-model="sortBy"
                    class="bg-white border border-border rounded-md text-text font-mono text-[11px] py-1.5 px-2 focus:border-accent outline-none"
                >
                    <option value="tco">TCO</option>
                    <option value="ratio_fa">Ratio FA</option>
                    <option value="dossiers">Nb dossiers</option>
                    <option value="ineff">Inefficiences</option>
                </select>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="group in filteredGroups"
                :key="group.frs"
                class="bg-card border border-border rounded-lg overflow-hidden shadow-sm"
            >
                <!-- Supplier header -->
                <div class="px-4 py-2.5 flex items-center justify-between bg-[#fafbfd] border-b border-border">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-sm text-navy truncate max-w-[160px]">
                            {{ group.frs }}
                        </span>
                        <span
                            v-if="group.pays_ale"
                            class="text-[9px] px-1.5 py-px rounded bg-accent/10 text-accent font-bold"
                            title="Pays ALE"
                        >
                            ALE
                        </span>
                    </div>
                    <span class="text-[10px] text-muted font-mono">
                        {{ group.dossiers }} dossier{{ group.dossiers > 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="p-4 space-y-3">
                    <!-- % global TCO -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] text-muted font-medium">{{ group.pct_tco }}% du TCO global</span>
                            <span
                                class="text-[10px] font-mono px-1.5 py-px rounded font-bold"
                                :style="{ backgroundColor: faStatusFor(group.avg_fa).color + '15', color: faStatusFor(group.avg_fa).color }"
                            >
                                FA {{ group.avg_fa }}%
                            </span>
                        </div>
                        <div class="h-2 bg-[#f0f4f9] rounded overflow-hidden">
                            <div
                                class="h-full rounded transition-all bg-navy"
                                :style="{ width: group.pct_tco + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Anomalies row -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <div
                            v-if="group.cert_manquants > 0"
                            class="text-[9px] font-mono px-2 py-0.5 rounded bg-danger/10 text-danger font-bold"
                        >
                            ⚠ {{ group.cert_manquants }} cert EUR1 manquant{{ group.cert_manquants > 1 ? 's' : '' }}
                        </div>
                        <div
                            v-if="group.magasinage_count > 0"
                            class="text-[9px] font-mono px-2 py-0.5 rounded bg-warn/10 text-warn font-bold"
                        >
                            🏗 {{ group.magasinage_count }} magasinage
                        </div>
                        <div
                            v-if="group.ineff_total > 0"
                            class="text-[9px] font-mono px-2 py-0.5 rounded bg-danger/10 text-danger font-bold"
                        >
                            💸 Ineff. {{ fmtK(group.ineff_total) }}
                        </div>
                    </div>

                    <!-- Famille tags -->
                    <div v-if="group.familles && group.familles.length > 0" class="flex flex-wrap gap-1">
                        <span
                            v-for="fam in group.familles"
                            :key="fam"
                            class="text-[9px] font-mono px-2 py-0.5 rounded"
                            :style="{ backgroundColor: familleColor(fam) + '15', color: familleColor(fam) }"
                        >
                            {{ fam }}
                        </span>
                    </div>

                    <!-- 4 KPI cards -->
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-[#fafbfd] border border-border/50 rounded p-2">
                            <div class="text-[9px] text-muted uppercase tracking-wider font-mono">TCO</div>
                            <div class="text-sm font-mono font-bold text-navy">{{ fmtK(group.tco) }}</div>
                        </div>
                        <div class="bg-[#fafbfd] border border-border/50 rounded p-2">
                            <div class="text-[9px] text-muted uppercase tracking-wider font-mono">Prix Achat</div>
                            <div class="text-sm font-mono font-bold text-text">{{ fmtK(group.px) }}</div>
                        </div>
                        <div class="bg-[#fafbfd] border border-border/50 rounded p-2">
                            <div class="text-[9px] text-muted uppercase tracking-wider font-mono">Frais</div>
                            <div class="text-sm font-mono font-bold text-warn">{{ fmtK(group.frais) }}</div>
                        </div>
                        <div class="bg-[#fafbfd] border border-border/50 rounded p-2">
                            <div class="text-[9px] text-muted uppercase tracking-wider font-mono">FA moyen</div>
                            <div
                                class="text-sm font-mono font-bold"
                                :style="{ color: faStatusFor(group.avg_fa).color }"
                            >
                                {{ group.avg_fa }}%
                            </div>
                        </div>
                    </div>

                    <!-- Top 5 cost items mini bars -->
                    <div v-if="group.costs_sum && Object.keys(group.costs_sum).length > 0">
                        <div class="text-[10px] text-muted font-medium mb-2">Top 5 coûts</div>
                        <div class="space-y-1.5">
                            <div
                                v-for="(val, cid) in group.costs_sum"
                                :key="cid"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="w-2 h-2 rounded-full shrink-0"
                                    :style="{ backgroundColor: costColor(cid) }"
                                ></span>
                                <span class="text-[10px] text-text truncate flex-1">{{ costLabel(cid) }}</span>
                                <div class="w-24 h-3 bg-[#f0f4f9] rounded overflow-hidden shrink-0">
                                    <div
                                        class="h-full rounded"
                                        :style="{ width: (val / globalMaxCost * 100) + '%', backgroundColor: costColor(cid) }"
                                    ></div>
                                </div>
                                <span class="text-[9px] font-mono text-muted shrink-0">{{ fmtK(val) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dossier tags -->
                    <div v-if="group.dossier_tags && group.dossier_tags.length > 0">
                        <div class="text-[10px] text-muted font-medium mb-1.5">Dossiers</div>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="tag in group.dossier_tags"
                                :key="tag.ref"
                                class="text-[9px] font-mono px-2 py-0.5 rounded border cursor-default"
                                :style="{
                                    borderColor: faStatus(tag.ratio_fa).color + '40',
                                    backgroundColor: faStatus(tag.ratio_fa).color + '10',
                                    color: faStatus(tag.ratio_fa).color,
                                }"
                                :title="tag.pays ? tag.pays + ' — ' + (tag.famille || '') : ''"
                            >
                                {{ tag.ref }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="filteredGroups.length === 0"
                class="col-span-full text-center py-12 text-[11px] text-muted italic"
            >
                <template v-if="search.trim()">
                    Aucun fournisseur trouvé pour "{{ search }}".
                </template>
                <template v-else>
                    Aucune donnée à afficher. Ajoutez des dossiers pour voir les statistiques par fournisseur.
                </template>
            </div>
        </div>
    </div>
</template>
