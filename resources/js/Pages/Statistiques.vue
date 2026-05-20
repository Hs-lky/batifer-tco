<script setup>
import { computed, ref, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { fmt, fmtK, faStatus } from '../lib/formatters';
import { FAMILLE_OPTIONS, COSTS_DEF } from '../lib/constants';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    count: { type: Number, default: 0 },
    totalTco: { type: Number, default: 0 },
    totalPxMad: { type: Number, default: 0 },
    avgRatioFa: { type: Number, default: 0 },
    maxRatioFa: { type: Number, default: 0 },
    minRatioFa: { type: Number, default: 0 },
    anomaliesCount: { type: Number, default: 0 },
    totalIneff: { type: Number, default: 0 },
    certManquants: { type: Number, default: 0 },
    topAnomalies: { type: Array, default: () => [] },
    byPays: { type: Array, default: () => [] },
    byFamille: { type: Array, default: () => [] },
    faChart: { type: Array, default: () => [] },
    seuilWarn: { type: Number, default: 40 },
    seuilOk: { type: Number, default: 30 },
});

const avgStatus = computed(() => faStatus(props.avgRatioFa, { ok: props.seuilOk, warn: props.seuilWarn }));

// ─── Max values for bar scaling ──────────────────────────────────
const maxPaysTco = computed(() => Math.max(1, ...props.byPays.map((p) => p.tco)));
const maxFamilleTco = computed(() => Math.max(1, ...props.byFamille.map((f) => f.tco)));

// ─── Famille colors ──────────────────────────────────────────────
const familleColors = {
    ACIER: '#60a5fa',
    PAPIER: '#a78bfa',
    ETANCHEITE: '#34d399',
    FOURNITURE: '#f59e0b',
    AUTRE: '#9ca3af',
};
const defaultFamColor = '#c084fc';

function familleColor(famille) {
    return familleColors[famille] || defaultFamColor;
}

// ─── Chart.js FA chart data ──────────────────────────────────────
const chartData = computed(() => ({
    labels: props.faChart.map((d) => d.label),
    datasets: [
        {
            label: 'Ratio FA (%)',
            data: props.faChart.map((d) => d.ratio_fa),
            backgroundColor: props.faChart.map((d) => {
                const s = faStatus(d.ratio_fa, { ok: props.seuilOk, warn: props.seuilWarn });
                return s.color;
            }),
            borderRadius: 4,
            maxBarThickness: 32,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => `Ratio FA : ${ctx.raw}%`,
            },
        },
    },
    scales: {
        x: {
            ticks: { font: { size: 9 }, maxRotation: 60, minRotation: 0 },
            grid: { display: false },
        },
        y: {
            beginAtZero: true,
            ticks: { font: { size: 10 }, callback: (v) => v + '%' },
            grid: { color: '#e8ecf4' },
        },
    },
}));
</script>

<template>
    <div class="flex-1 bg-bg p-5 overflow-y-auto">
        <!-- ═══ Overview ═══ -->
        <section class="mb-5">
            <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Vue d'ensemble
            </h2>
            <div class="grid grid-cols-4 gap-3">
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Nb dossiers</div>
                    <div class="text-xl font-mono font-bold text-navy mt-1">{{ count }}</div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">TCO total</div>
                    <div class="text-xl font-mono font-bold text-navy mt-1">{{ fmtK(totalTco) }}</div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Prix achat total</div>
                    <div class="text-xl font-mono font-bold text-text mt-1">{{ fmtK(totalPxMad) }}</div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Ratio FA moyen</div>
                    <div class="text-xl font-mono font-bold mt-1" :style="{ color: avgStatus.color }">
                        {{ avgRatioFa }}%
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-5 gap-3 mt-3">
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">FA max</div>
                    <div class="text-lg font-mono font-bold text-danger mt-1">{{ maxRatioFa }}%</div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">FA min</div>
                    <div class="text-lg font-mono font-bold text-ok mt-1">{{ minRatioFa }}%</div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Anomalies</div>
                    <div class="text-lg font-mono font-bold mt-1" :class="anomaliesCount > 0 ? 'text-danger' : 'text-ok'">
                        {{ anomaliesCount }}
                    </div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Inefficiences</div>
                    <div class="text-lg font-mono font-bold mt-1" :class="totalIneff > 0 ? 'text-danger' : 'text-ok'">
                        {{ fmtK(totalIneff) }}
                    </div>
                </div>
                <div class="bg-card border border-border rounded-lg p-3">
                    <div class="text-[10px] text-muted uppercase tracking-wider font-mono">Cert manquants</div>
                    <div class="text-lg font-mono font-bold mt-1" :class="certManquants > 0 ? 'text-warn' : 'text-ok'">
                        {{ certManquants }}
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ Top Anomalies ═══ -->
        <section v-if="topAnomalies.length > 0" class="mb-5">
            <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-3">
                🔴 Top Anomalies
            </h2>
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <table class="w-full text-[11px] border-collapse">
                    <thead>
                        <tr class="text-muted font-mono text-[10px] uppercase tracking-wider border-b border-border bg-[#fafbfd]">
                            <th class="text-left py-2 px-3 font-medium">Réf</th>
                            <th class="text-left py-2 px-3 font-medium">Fournisseur</th>
                            <th class="text-left py-2 px-3 font-medium">Pays</th>
                            <th class="text-right py-2 px-3 font-medium">Ratio FA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="a in topAnomalies"
                            :key="a.ref"
                            class="border-b border-border/50 last:border-0 hover:bg-[#f8fafc]"
                        >
                            <td class="py-1.5 px-3 font-mono text-navy font-bold">{{ a.ref }}</td>
                            <td class="py-1.5 px-3 text-text truncate max-w-[200px]">{{ a.frs }}</td>
                            <td class="py-1.5 px-3 text-muted">{{ a.pays || '—' }}</td>
                            <td class="py-1.5 px-3 text-right font-mono font-bold text-danger">{{ a.ratio_fa }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ═══ Two-column: TCO par Pays / TCO par Famille ═══ -->
        <div class="grid grid-cols-2 gap-5 mb-5">
            <!-- TCO par Pays -->
            <section>
                <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-3">
                    🌍 TCO par Pays
                </h2>
                <div class="bg-card border border-border rounded-lg p-3 space-y-2">
                    <template v-if="byPays.length > 0">
                        <div v-for="entry in byPays" :key="entry.pays" class="flex items-center gap-3">
                            <span class="text-[11px] font-mono text-text w-24 truncate shrink-0">{{ entry.pays }}</span>
                            <span class="text-[10px] text-muted shrink-0">{{ entry.count }} dossier{{ entry.count > 1 ? 's' : '' }}</span>
                            <div class="flex-1 h-5 bg-[#f0f4f9] rounded relative overflow-hidden">
                                <div
                                    class="h-full rounded bg-navy/80 transition-all"
                                    :style="{ width: (entry.tco / maxPaysTco * 100) + '%' }"
                                ></div>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-navy shrink-0 text-right w-20">{{ fmtK(entry.tco) }}</span>
                        </div>
                    </template>
                    <div v-else class="text-[11px] text-muted italic py-2">Aucune donnée</div>
                </div>
            </section>

            <!-- TCO par Famille -->
            <section>
                <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-3">
                    🏷 TCO par Famille
                </h2>
                <div class="bg-card border border-border rounded-lg p-3 space-y-2">
                    <template v-if="byFamille.length > 0">
                        <div v-for="entry in byFamille" :key="entry.famille" class="flex items-center gap-3">
                            <span class="text-[11px] font-mono text-text w-24 truncate shrink-0">{{ entry.famille }}</span>
                            <span class="text-[10px] text-muted shrink-0">{{ entry.count }} dossier{{ entry.count > 1 ? 's' : '' }}</span>
                            <div class="flex-1 h-5 bg-[#f0f4f9] rounded relative overflow-hidden">
                                <div
                                    class="h-full rounded transition-all"
                                    :style="{ width: (entry.tco / maxFamilleTco * 100) + '%', backgroundColor: familleColor(entry.famille) }"
                                ></div>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-navy shrink-0 text-right w-20">{{ fmtK(entry.tco) }}</span>
                        </div>
                    </template>
                    <div v-else class="text-[11px] text-muted italic py-2">Aucune donnée</div>
                </div>
            </section>
        </div>

        <!-- ═══ FA par Dossier (Chart.js) ═══ -->
        <section>
            <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-3">
                📊 FA par Dossier
            </h2>
            <div class="bg-card border border-border rounded-lg p-4">
                <template v-if="faChart.length > 0">
                    <div style="height: 300px;">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </template>
                <div v-else class="text-[11px] text-muted italic py-6 text-center">
                    Aucune donnée à afficher
                </div>
            </div>
        </section>
    </div>
</template>
