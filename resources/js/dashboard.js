// Grafik dashboard (Chart.js). Hanya dimuat di halaman dashboard.
import Chart from 'chart.js/auto';

// Palet kategorikal tervalidasi (slot 1-5, urutan tetap) + token ink/grid.
const SERI = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];
const INK = '#52514e';
const GRID = '#e1e0d9';
const PERMUKAAN = '#fcfcfb';
const angka = new Intl.NumberFormat('id-ID');

Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = INK;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;

const sumbuAngka = (arah) => ({
    beginAtZero: true,
    grid: { color: GRID },
    border: { display: false },
    ticks: { precision: 0, callback: (v) => angka.format(v) },
    position: arah,
});
const sumbuKategori = { grid: { display: false }, border: { color: GRID } };

function buat(id, config) {
    const el = document.getElementById(id);
    if (!el) return null;
    return new Chart(el, config);
}

export function render(d) {
    // 1. Persil per status hak (batang horizontal, satu seri)
    buat('grafikStatusHak', {
        type: 'bar',
        data: {
            labels: d.status_hak.map((s) => s.label),
            datasets: [{ label: 'Jumlah persil', data: d.status_hak.map((s) => s.persil), backgroundColor: SERI[0], borderRadius: 4, maxBarThickness: 22 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { afterLabel: (c) => `Luas: ${angka.format(d.status_hak[c.dataIndex].luas_m2)} m²` } },
            },
            scales: { x: sumbuAngka(), y: sumbuKategori },
        },
    });

    // 2. Luas penggunaan lahan per dusun (batang bertumpuk, jarak 2px antarsegmen)
    buat('grafikPenggunaan', {
        type: 'bar',
        data: {
            labels: d.penggunaan_per_dusun.dusun,
            datasets: d.penggunaan_per_dusun.seri.map((s, i) => ({
                label: s.label, data: s.nilai, backgroundColor: SERI[i % SERI.length],
                borderColor: PERMUKAAN, borderWidth: 2, maxBarThickness: 56,
            })),
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', align: 'start' },
                tooltip: {
                    mode: 'index', intersect: false,
                    callbacks: { label: (c) => ` ${c.dataset.label}: ${angka.format(c.parsed.y)} m² (${(c.parsed.y / 10000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} ha)` },
                },
            },
            scales: {
                x: { ...sumbuKategori, stacked: true },
                y: { ...sumbuAngka(), stacked: true, title: { display: true, text: 'Luas (m²)', color: INK } },
            },
        },
    });

    // 3. Pengajuan surat per bulan (batang vertikal, satu seri)
    buat('grafikSuratBulan', {
        type: 'bar',
        data: {
            labels: d.surat_per_bulan.label,
            datasets: [{ label: 'Pengajuan surat', data: d.surat_per_bulan.nilai, backgroundColor: SERI[0], borderRadius: { topLeft: 4, topRight: 4 }, maxBarThickness: 32 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: sumbuKategori, y: sumbuAngka() },
        },
    });

    // 4. Pengajuan surat per status (batang horizontal, satu seri)
    buat('grafikSuratStatus', {
        type: 'bar',
        data: {
            labels: d.surat_per_status.map((s) => s.label),
            datasets: [{ label: 'Jumlah pengajuan', data: d.surat_per_status.map((s) => s.nilai), backgroundColor: SERI[0], borderRadius: 4, maxBarThickness: 22 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: sumbuAngka(), y: sumbuKategori },
        },
    });
}

window.Dashboard = { render };
