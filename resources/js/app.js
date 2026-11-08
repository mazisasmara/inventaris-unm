
import Chart from 'chart.js/auto';

function initializeDashboardCharts() {
    const dataElement = document.getElementById('dashboard-chart-data');

    if (!dataElement) {
        return;
    }

    let chartData;

    try {
        chartData = JSON.parse(dataElement.textContent);
    } catch (error) {
        console.error('Gagal membaca data grafik dashboard:', error);
        return;
    }

    Object.entries(chartData).forEach(([key, config]) => {
        const canvas = document.querySelector(
            `[data-dashboard-chart="${key}"]`
        );

        if (!canvas) {
            return;
        }

        // Hindari duplikasi instance ketika halaman diinisialisasi ulang.
        if (canvas.chartInstance) {
            canvas.chartInstance.destroy();
        }

        canvas.chartInstance = new Chart(canvas, {
            ...config,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                ...config.options,
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeDashboardCharts);
document.addEventListener('livewire:navigated', initializeDashboardCharts);
