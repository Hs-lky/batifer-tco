<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js';
import { faStatus } from '../lib/formatters';
import { COSTS_DEF } from '../lib/constants';

Chart.register(DoughnutController, ArcElement, Tooltip, Legend);

const props = defineProps({
    analysis: { type: Object, required: true },
    seuilsData: { type: Object, default: () => ({}) },
});

const canvasRef = ref(null);
let chartInstance = null;

const ratioStatus = computed(() =>
    faStatus(props.analysis.ratio_fa || 0, { ok: 30, warn: 40 })
);

const datasets = computed(() => {
    const costs = props.analysis.costs || {};
    const pxMad = props.analysis.px_mad || 0;

    const structCosts = COSTS_DEF
        .filter((c) => c.type === 'struct')
        .reduce((sum, c) => sum + (Number(costs[c.id]) || 0), 0);

    const ineffCosts = COSTS_DEF
        .filter((c) => c.type === 'ineff')
        .reduce((sum, c) => sum + (Number(costs[c.id]) || 0), 0);

    return [
        { label: "Prix d'Achat", val: pxMad, color: '#3b82f6' },
        { label: 'Coûts Structurels', val: structCosts, color: '#60a5fa' },
        { label: 'Inefficiences', val: ineffCosts, color: '#f87171' },
    ].filter((d) => d.val > 0);
});

const ratioFa = computed(() => props.analysis.ratio_fa || 0);

const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart) {
        const { ctx, chartArea: { top, bottom, left, right } } = chart;
        const centerX = (left + right) / 2;
        const centerY = (top + bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Main value
        ctx.font = "bold 15px 'DM Sans', sans-serif";
        ctx.fillStyle = ratioStatus.value.color;
        ctx.fillText(ratioFa.value.toFixed(1) + '%', centerX, centerY - 7);

        // Subtitle
        ctx.font = "9px 'Space Mono', monospace";
        ctx.fillStyle = '#5a7090';
        ctx.fillText('Ratio FA', centerX, centerY + 9);

        ctx.restore();
    },
};

function createChart() {
    if (!canvasRef.value) return;
    if (datasets.value.length === 0) return;

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    chartInstance = new Chart(canvasRef.value, {
        type: 'doughnut',
        data: {
            labels: datasets.value.map((d) => d.label),
            datasets: [
                {
                    data: datasets.value.map((d) => d.val),
                    backgroundColor: datasets.value.map((d) => d.color),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                },
            ],
        },
        options: {
            cutout: '60%',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        padding: 14,
                        font: { size: 10, family: "'DM Sans', sans-serif" },
                        color: '#5a7090',
                        usePointStyle: true,
                        pointStyleWidth: 8,
                    },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const val = context.parsed;
                            const total = context.dataset.data.reduce((s, v) => s + v, 0);
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                            return ` ${context.label}: ${val.toLocaleString('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MAD (${pct}%)`;
                        },
                    },
                },
            },
        },
        plugins: datasets.value.length > 0 ? [centerTextPlugin] : [],
    });
}

watch(
    () => [props.analysis.tco, props.analysis.px_mad, props.analysis.ratio_fa, props.analysis.costs],
    () => {
        if (chartInstance) {
            if (datasets.value.length === 0) {
                chartInstance.destroy();
                chartInstance = null;
                return;
            }
            chartInstance.data.labels = datasets.value.map((d) => d.label);
            chartInstance.data.datasets[0].data = datasets.value.map((d) => d.val);
            chartInstance.data.datasets[0].backgroundColor = datasets.value.map((d) => d.color);
            chartInstance.update();
        } else {
            createChart();
        }
    },
    { deep: true }
);

onMounted(() => {
    createChart();
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-3">
            Répartition des Coûts
        </h3>
        <div class="flex justify-center">
            <div class="w-full max-w-[260px]">
                <canvas ref="canvasRef"></canvas>
            </div>
        </div>
    </div>
</template>
