// Chart Component
import ChartLib from 'chart.js/auto';

const Chart = {
    charts: new Map(),

    init(root = document) {
        root.querySelectorAll('[data-ux-chart]').forEach(container => {
            const chartId = container.id;
            if (chartId && this.charts.has(chartId)) return;
            
            const canvas = container.querySelector('canvas');
            if (!canvas) return;

            const config = this.parseConfig(container);
            const chart = this.createChart(canvas, config);
            
            if (chartId) {
                this.charts.set(chartId, chart);
            }
        });
    },

    parseConfig(container) {
        const configJson = container.dataset.chartConfig;
        if (!configJson) return null;
        
        try {
            return JSON.parse(configJson);
        } catch (e) {
            console.error('Failed to parse chart config:', e);
            return null;
        }
    },

    createChart(canvas, config) {
        const ctx = canvas.getContext('2d');
        const options = this.buildOptions(config.options || {});

        return new ChartLib(ctx, {
            type: config.type || 'line',
            data: config.data || {},
            options
        });
    },

    buildOptions(userOptions) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: userOptions.animation === 'smooth' ? {
                duration: 750,
                easing: 'easeInOutQuart'
            } : false,
            plugins: {
                legend: {
                    display: userOptions.showLegend !== false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: userOptions.showGrid !== false ? {
                x: {
                    grid: { display: true }
                },
                y: {
                    grid: { display: true },
                    beginAtZero: true
                }
            } : {}
        };
    },

    update(id, data) {
        const chart = this.charts.get(id);
        if (chart) {
            chart.data = data;
            chart.update();
        }
    },

    destroy(id) {
        const chart = this.charts.get(id);
        if (chart) {
            chart.destroy();
            this.charts.delete(id);
        }
    }
};

// Auto-init
document.addEventListener('DOMContentLoaded', () => Chart.init());

// Listen for partial updates
window.addEventListener('y:updated', (e) => {
    const root = e.detail?.el || document;
    Chart.init(root);
});

export default Chart;