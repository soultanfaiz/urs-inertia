<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, nextTick } from 'vue';
import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalRequests: 0,
            completedRequests: 0,
            pendingVerification: 0,
            rejectedRequests: 0,
        }),
    },
    charts: {
        type: Object,
        default: () => ({
            requestsByStatus: {},
            requestsByInstansi: {},
            requestsOverTime: {},
        }),
    },
});

const requestsByStatusChart = ref(null);
const requestsByInstansiChart = ref(null);
const requestsOverTimeChart = ref(null);

const initializeCharts = () => {
    // Grafik 1: Permohonan berdasarkan Status (Pie Chart)
    if (requestsByStatusChart.value && requestsByStatusChart.value.getContext) {
        new Chart(requestsByStatusChart.value.getContext('2d'), {
            type: 'pie',
            data: {
                labels: Object.keys(props.charts.requestsByStatus),
                datasets: [{
                    label: 'Jumlah Permohonan',
                    data: Object.values(props.charts.requestsByStatus),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                    ],
                    borderColor: 'rgba(255, 255, 255, 0.8)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
            },
        });
    }

    // Grafik 2: Permohonan berdasarkan Instansi (Bar Chart)
    if (requestsByInstansiChart.value && requestsByInstansiChart.value.getContext) {
        new Chart(requestsByInstansiChart.value.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(props.charts.requestsByInstansi),
                datasets: [{
                    label: 'Jumlah Permohonan',
                    data: Object.values(props.charts.requestsByInstansi),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                }],
            },
            options: {
                indexAxis: 'y', // Membuat bar chart menjadi horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    // Grafik 3: Tren Permohonan Masuk (Line Chart)
    if (requestsOverTimeChart.value && requestsOverTimeChart.value.getContext) {
        new Chart(requestsOverTimeChart.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: Object.keys(props.charts.requestsOverTime),
                datasets: [{
                    label: 'Permohonan Masuk',
                    data: Object.values(props.charts.requestsOverTime),
                    fill: true,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }
};

onMounted(async () => {
    // Menunggu siklus render Vue selanjutnya
    await nextTick();
    // Memberi jeda tambahan untuk transisi CSS
    setTimeout(initializeCharts, 100);
});
</script>

<template>
    <Head title="Laporan Infografis" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Laporan Infografis Permohonan
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-8">
                    <!-- 1. Kartu Statistik Utama -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Total Permohonan -->
                        <div class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-6 shadow-md">
                            <div class="shrink-0 rounded-full bg-blue-100 p-3 text-blue-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">Total Permohonan</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.totalRequests }}</p>
                            </div>
                        </div>
                        <!-- Permohonan Selesai -->
                        <div class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-6 shadow-md">
                            <div class="shrink-0 rounded-full bg-green-100 p-3 text-green-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">Selesai</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.completedRequests }}</p>
                            </div>
                        </div>
                        <!-- Menunggu Verifikasi -->
                        <div class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-6 shadow-md">
                            <div class="shrink-0 rounded-full bg-yellow-100 p-3 text-yellow-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">Menunggu Verifikasi</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.pendingVerification }}</p>
                            </div>
                        </div>
                        <!-- Ditolak -->
                        <div class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-6 shadow-md">
                            <div class="shrink-0 rounded-full bg-red-100 p-3 text-red-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">Ditolak</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.rejectedRequests }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <h3 class="mb-4 font-semibold text-gray-800">Permohonan berdasarkan Status</h3>
                            <div class="relative aspect-square w-full">
                                <canvas ref="requestsByStatusChart" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <h3 class="mb-4 font-semibold text-gray-800">Permohonan berdasarkan Instansi</h3>
                            <div class="relative aspect-square w-full">
                                <canvas ref="requestsByInstansiChart" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h3 class="mb-4 font-semibold text-gray-800">Tren Permohonan Masuk (12 Bulan Terakhir)</h3>
                        <div class="relative aspect-video w-full">
                            <canvas ref="requestsOverTimeChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
