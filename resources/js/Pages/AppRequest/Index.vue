<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; // Assuming you have a pagination component
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import GenerateReportModal from '@/Components/Modals/GenerateReportModal.vue';

const props = defineProps({
    appRequests: {
        type: Object,
        required: true,
    },
    allRequestsForReport: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
});

const user = computed(() => usePage().props.auth.user);

const key = computed(() => usePage().url);
const format_date = (value) => {
    if (value) {
        const date = new Date(value);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }
    return '';
}

const getStatus = (request) => {
    // These string values must match the values of your PHP enums
    if (request.verification_status === 'ditolak') {
        return {
            text: 'Permohonan Ditolak',
            class: 'bg-red-100 text-red-800',
        };
    }

    const status = request.status;
    let text = status.charAt(0).toUpperCase() + status.slice(1);
    let a_class = 'bg-gray-100 text-gray-800';

    switch (status) {
        case 'PERMOHONAN':
            text = 'Permohonan';
            a_class = 'bg-blue-100 text-blue-800';
            break;
        case 'URS':
            text = 'URS';
            a_class = 'bg-cyan-100 text-cyan-800';
            break;
        case 'PENGEMBANGAN':
            text = 'Pengembangan';
            a_class = 'bg-yellow-100 text-yellow-800';
            break;
        case 'UAT':
            text = 'UAT';
            a_class = 'bg-purple-100 text-purple-800';
            break;
        case 'SELESAI':
            text = 'Selesai';
            a_class = 'bg-green-100 text-green-800';
            break;
    }

    return { text, class: a_class };
};

const showingReportModal = ref(false);

// State untuk filter
const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');

// Fungsi untuk fetch data dengan filter
const fetchData = () => {
    router.get(route('app-requests.index'), {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Debounce sederhana untuk search agar tidak request setiap ketikan
let timeout = null;
watch(search, (newValue) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        fetchData();
    }, 300);
});

// Watch status filter (langsung request saat berubah)
watch(statusFilter, fetchData);

</script>

<template>
    <Head title="Permohonan" />
    <div :key="key">
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Permohonan
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl">
                <!-- Tombol Ajukan Permohonan -->
                <div class="flex items-center justify-between mb-6 px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Permohonan</h3>
                    <div class="flex gap-2">
                        <button @click="showingReportModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Buat Laporan
                        </button>
                        <Link :href="route('app-requests.create')" class="inline-flex items-center justify-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Ajukan Permohonan
                        </Link>
                    </div>
                </div>

                <!-- Card Filter & Pencarian -->
                <div class="mb-6 px-4 sm:px-6 lg:px-8">
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Input Search -->
                            <div class="md:col-span-2">
                                <input v-model="search" type="text" placeholder="Cari berdasarkan judul atau nama pemohon..." class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            </div>
                            <!-- Dropdown Status -->
                            <div>
                                <select v-model="statusFilter" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="PERMOHONAN">Permohonan</option>
                                    <option value="URS">URS</option>
                                    <option value="PENGEMBANGAN">Pengembangan</option>
                                    <option value="UAT">UAT</option>
                                    <option value="SELESAI">Selesai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Permohonan -->
                <div v-if="appRequests.data.length > 0" class="space-y-4">
                    <Link
                        v-for="request in appRequests.data"
                        :key="request.id"
                        :href="route('app-requests.show', request.id)"
                        class="block p-4 bg-white shadow-sm transition duration-150 ease-in-out hover:shadow-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:rounded-lg"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start md:items-center">
                            <!-- Judul, Tanggal, dan Pemohon -->
                            <div class="md:col-span-5 lg:col-span-4">
                                <p class="font-semibold text-gray-900 group-hover:text-blue-600">{{ request.title }}</p>
                                <p class="text-sm text-gray-500">
                                    oleh {{ request.user.name }} &bull; {{ format_date(request.created_at) }}
                                </p>
                            </div>

                            <!-- Instansi -->
                            <div class="md:col-span-3 lg:col-span-2">
                                <p class="text-sm text-gray-500">Instansi</p>
                                <p class="font-medium text-gray-900">{{ request.instansi }}</p>
                            </div>

                            <!-- Status dan Aksi -->
                            <div class="md:col-span-4 lg:col-span-6 flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center justify-start md:justify-end gap-2 md:gap-4 mt-2 md:mt-0">
                                <!-- Badge Status -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatus(request).class">
                                    {{ getStatus(request).text }}
                                </span>

                                <!-- Tombol Aksi -->
                                <div class="flex items-center space-x-1 mt-2 sm:mt-0">
                                    <a :href="route('app-requests.download', request.id)" @click.stop target="_blank" class="p-2 text-gray-400 rounded-full hover:text-blue-600" title="Unduh PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center bg-white shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Belum Ada Permohonan</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Anda belum mengajukan permohonan apa pun. Silakan klik tombol "Ajukan Permohonan" untuk memulai.
                    </p>
                </div>

                <!-- Tautan Paginasi -->
                <div v-if="appRequests.links.length > 3" class="mt-8">
                    <Pagination :links="appRequests.links" />
                </div>
            </div>
        </div>

        <!-- Modal Buat Laporan -->
        <GenerateReportModal
            :show="showingReportModal"
            :requests="allRequestsForReport"
            @close="showingReportModal = false"
        />
    </AuthenticatedLayout>
    </div>
</template>
