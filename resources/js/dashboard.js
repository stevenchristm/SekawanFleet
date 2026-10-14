import Chart from 'chart.js/auto';

const COLORS = {
    green: '#16a34a',
    yellow: '#eab308',
    red: '#dc2626',
    blue: '#2563eb',
    gray: '#9ca3af',
    indigo: '#4f46e5',
    teal: '#0d9488',
    orange: '#ea580c',
};

// Warna sama dengan badge status (BookingStatus::color()).
const STATUS_COLORS = {
    pending: COLORS.yellow,
    approved: COLORS.green,
    rejected: COLORS.red,
    completed: COLORS.blue,
    cancelled: COLORS.gray,
};

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.animation = prefersReducedMotion ? false : Chart.defaults.animation;
Chart.defaults.plugins.legend.position = 'bottom';

function chart(id, config) {
    const canvas = document.getElementById(id);
    if (canvas) new Chart(canvas, config);
}

export function renderDashboard({ monthly, topVehicles, status, byType, fuel }) {
    chart('chart-monthly', {
        type: 'bar',
        data: {
            labels: monthly.labels,
            datasets: [
                { label: 'Disetujui / Selesai', data: monthly.used, backgroundColor: COLORS.green },
                { label: 'Menunggu', data: monthly.pending, backgroundColor: COLORS.yellow },
                { label: 'Ditolak / Batal', data: monthly.rejected, backgroundColor: COLORS.red },
            ],
        },
        options: {
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
        },
    });

    chart('chart-top-vehicles', {
        type: 'bar',
        data: {
            labels: topVehicles.labels,
            datasets: [
                { label: 'Jumlah perjalanan', data: topVehicles.trips, backgroundColor: COLORS.indigo, xAxisID: 'x' },
                { label: 'Total jam', data: topVehicles.hours, backgroundColor: COLORS.teal, xAxisID: 'x2' },
            ],
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, position: 'bottom', ticks: { precision: 0 }, title: { display: true, text: 'Perjalanan' } },
                x2: { beginAtZero: true, position: 'top', grid: { drawOnChartArea: false }, title: { display: true, text: 'Jam' } },
            },
        },
    });

    chart('chart-status', {
        type: 'doughnut',
        data: {
            labels: status.labels,
            datasets: [{ data: status.data, backgroundColor: status.keys.map((k) => STATUS_COLORS[k]) }],
        },
    });

    chart('chart-type', {
        type: 'bar',
        data: {
            labels: byType.labels,
            datasets: [
                { label: 'Milik Perusahaan', data: byType.owned, backgroundColor: COLORS.blue },
                { label: 'Sewa', data: byType.rented, backgroundColor: COLORS.orange },
            ],
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    chart('chart-fuel', {
        type: 'line',
        data: {
            labels: fuel.labels,
            datasets: [
                { label: 'BBM (liter)', data: fuel.liters, borderColor: COLORS.orange, backgroundColor: COLORS.orange, yAxisID: 'y', tension: 0.3 },
                { label: 'Jarak (km)', data: fuel.km, borderColor: COLORS.indigo, backgroundColor: COLORS.indigo, yAxisID: 'y2', tension: 0.3 },
            ],
        },
        options: {
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Liter' } },
                y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Km' } },
            },
        },
    });
}
