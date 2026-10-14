import Chart from 'chart.js/auto';

// Palet sama dengan desain (Tailwind: emerald-500, amber-500, rose-500, blue-500, slate-400, brand-700)
const COLORS = {
    green: '#10B981',
    amber: '#F59E0B',
    rose: '#F43F5E',
    blue: '#3B82F6',
    gray: '#94A3B8',
    teal: '#0F766E',
};

// Warna sama dengan badge status (BookingStatus::color()).
const STATUS_COLORS = {
    pending: COLORS.amber,
    approved: COLORS.green,
    rejected: COLORS.rose,
    completed: COLORS.blue,
    cancelled: COLORS.gray,
};

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748B';
Chart.defaults.borderColor = '#F1F5F9';
Chart.defaults.maintainAspectRatio = false;
if (prefersReducedMotion) Chart.defaults.animation = false;

const tooltip = { backgroundColor: '#0F172A', padding: 10, cornerRadius: 8, titleFont: { weight: '600' } };

function chart(id, config) {
    const canvas = document.getElementById(id);
    if (canvas) new Chart(canvas, config);
}

export function renderDashboard({ monthly, status, fuel }) {
    chart('chart-monthly', {
        type: 'bar',
        data: {
            labels: monthly.labels,
            datasets: [
                { label: 'Disetujui / Selesai', data: monthly.used, backgroundColor: COLORS.green, borderRadius: 4 },
                { label: 'Menunggu', data: monthly.pending, backgroundColor: COLORS.amber, borderRadius: 4 },
                { label: 'Ditolak / Batal', data: monthly.rejected, backgroundColor: COLORS.rose, borderRadius: 4 },
            ],
        },
        options: {
            maxBarThickness: 44,
            plugins: { legend: { display: false }, tooltip: { ...tooltip, mode: 'index', intersect: false } },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, border: { display: false } },
            },
        },
    });

    chart('chart-status', {
        type: 'doughnut',
        data: {
            labels: status.labels,
            datasets: [{ data: status.data, backgroundColor: status.keys.map((k) => STATUS_COLORS[k]), borderWidth: 0 }],
        },
        options: { cutout: '72%', plugins: { legend: { display: false }, tooltip } },
    });

    chart('chart-fuel', {
        type: 'line',
        data: {
            labels: fuel.labels,
            datasets: [
                {
                    label: 'BBM (liter)', data: fuel.liters, yAxisID: 'y', tension: 0.35,
                    borderColor: COLORS.teal, backgroundColor: '#FFFFFF', borderWidth: 3, pointRadius: 4, pointBorderWidth: 2.5,
                },
                {
                    label: 'Jarak (km)', data: fuel.km, yAxisID: 'y2', tension: 0.35, borderDash: [5, 3],
                    borderColor: COLORS.amber, backgroundColor: '#FFFFFF', borderWidth: 3, pointRadius: 4, pointBorderWidth: 2.5,
                },
            ],
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, title: { display: true, text: 'Liter' }, border: { display: false } },
                y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Km' }, border: { display: false } },
            },
        },
    });
}
