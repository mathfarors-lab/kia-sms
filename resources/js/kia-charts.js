import Chart from 'chart.js/auto';

/**
 * Renders every <canvas data-kia-chart="..."> on the page. Each canvas
 * carries its config as a JSON data attribute so Blade views stay
 * declarative — no per-page JS files needed.
 */
function renderKiaCharts() {
    document.querySelectorAll('canvas[data-kia-chart]').forEach((canvas) => {
        if (canvas.dataset.kiaChartRendered) return;
        canvas.dataset.kiaChartRendered = '1';

        const config = JSON.parse(canvas.dataset.kiaChart);
        new Chart(canvas.getContext('2d'), config);
    });
}

document.addEventListener('DOMContentLoaded', renderKiaCharts);
document.addEventListener('kia:charts-refresh', renderKiaCharts);
