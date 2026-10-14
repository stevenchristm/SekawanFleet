import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Grafik dashboard dimuat terpisah (code-split) hanya di halaman yang membutuhkannya.
const dashboardData = document.getElementById('dashboard-data');
if (dashboardData) {
    import('./dashboard').then(({ renderDashboard }) => renderDashboard(JSON.parse(dashboardData.textContent)));
}
