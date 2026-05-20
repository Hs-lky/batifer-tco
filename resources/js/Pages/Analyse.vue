<script setup>
import { useCalculator } from '../composables/useCalculator';
import KpiGrid from '../Components/KpiGrid.vue';
import ParetoChart from '../Components/ParetoChart.vue';
import PieChart from '../Components/PieChart.vue';

const { analysis, ratioSeuils, seuilsData } = useCalculator();
</script>

<template>
    <div class="flex-1 bg-bg p-5 overflow-y-auto">
        <KpiGrid :analysis="analysis" :ratioSeuils="ratioSeuils" />
        <div class="grid grid-cols-2 gap-3.5 mt-3.5">
            <ParetoChart :analysis="analysis" :seuilsData="seuilsData" />
            <PieChart :analysis="analysis" :seuilsData="seuilsData" />
        </div>
    </div>
</template>
