<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    chartData: {
        type: Object,
        required: true
    },
    chartOptions: {
        type: Object,
        default: () => ({})
    },
    height: {
        type: Number,
        default: 200
    }
});

const chartCanvas = ref(null);
let chartInstance = null;

const createChart = () => {
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    const ctx = chartCanvas.value.getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: props.chartData,
        options: props.chartOptions
    });
};

watch(() => props.chartData, (newVal) => {
    if (chartInstance && newVal) {
        chartInstance.data = newVal;
        chartInstance.update();
    }
}, { deep: true });

onMounted(() => {
    if (props.chartData) {
        createChart();
    }
});
</script>

<template>
    <div class="chart-container">
        <canvas ref="chartCanvas" :height="height"></canvas>
    </div>
</template> 