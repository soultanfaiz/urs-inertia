<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; 
import ClientPagination from '@/Components/ClientPagination.vue'; // New component
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { format, parseISO, isPast, differenceInDays, isSameDay } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import GenerateReportModal from '@/Components/Modals/GenerateReportModal.vue';

const props = defineProps({
    appRequests: {
        type: Array, // Now receiving all data as an array
        required: true,
    },
    allRequestsForReport: {
        type: Array,
        default: () => [],
    },
});

const user = computed(() => usePage().props.auth.user);
// Removed key computed property as we are typically not forcing re-render of the whole page on filter change now
// But we might want to keep it if there are other reasons. The original code used url as key.
// With client side, url doesn't change on search. So let's just ignore it or use a static key if needed.

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

// State untuk filter client-side
const search = ref('');
const statusFilter = ref('');

// --- Clients-Side Filtering & Pagination ---

// 1. Filter Data
const filteredRequests = computed(() => {
    let result = props.appRequests;

    // Filter by Status
    if (statusFilter.value) {
        result = result.filter(req => req.status === statusFilter.value);
    }

    // Filter by Search (Title, User, Instansi)
    if (search.value) {
        const query = search.value.toLowerCase();
        result = result.filter(req => {
            const titleMatch = req.title.toLowerCase().includes(query);
            const userMatch = req.user.name.toLowerCase().includes(query);
            const instansiMatch = req.instansi && req.instansi.toLowerCase().includes(query);
            
            return titleMatch || userMatch || instansiMatch;
        });
    }

    return result;
});

// 2. Pagination State
const currentPage = ref(1);
const itemsPerPage = 10;

// Reset page to 1 when filters change
watch([search, statusFilter], () => {
    currentPage.value = 1;
});

// 3. Paginated Data
const totalPages = computed(() => Math.ceil(filteredRequests.value.length / itemsPerPage));

const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredRequests.value.slice(start, end);
});

// 4. Change Page Function
const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Remove server-side fetching logic (fetchData, watchers on search with debounce, router.get)

const getNearestActivity = (request) => {
    if (!request.development_activities || request.development_activities.length === 0) return null;
    
    // Filter: only include activities that are NOT completed AND have end_date
    const validActivities = request.development_activities.filter(a => a.end_date && !a.is_completed);
    if (validActivities.length === 0) return null;

    // Sort by end_date ascending
    const sorted = [...validActivities].sort((a, b) => new Date(a.end_date) - new Date(b.end_date));
    
    // Find first activity that is NOT in the past (future or today)
    const upcoming = sorted.find(a => {
        const date = parseISO(a.end_date);
        // Compare dates ignoring time
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        return date >= today;
    });
    
    if (upcoming) return upcoming;
    
    // If all are in past but still not completed, return the one with nearest past deadline
    return sorted[sorted.length - 1];
};

const format_date_activity = (activity) => {
    if (!activity || !activity.end_date) return '';

    const endDate = parseISO(activity.end_date);

    if (activity.start_date) {
        const startDate = parseISO(activity.start_date);
        
        if (isSameDay(startDate, endDate)) {
             return format(startDate, 'd MMM yyyy HH:mm', { locale: idLocale }) + ' - ' + format(endDate, 'HH:mm', { locale: idLocale });
        } else {
             // Different days: "29 Jan 2026 - 31 Jan 2026"
             return format(startDate, 'd MMM yyyy', { locale: idLocale }) + ' - ' + format(endDate, 'd MMM yyyy', { locale: idLocale });
        }
    }

    // fallback if no start date
    return format(endDate, 'd MMM yyyy', { locale: idLocale });
};

const getDeadlineColor = (activity) => {
    if (!activity || !activity.end_date) return 'text-gray-500';
    // If completed, maybe neutral color? The prompt didn't specify, but red for overdue is good.
    if (activity.is_completed) return 'text-green-600'; 

    const deadline = parseISO(activity.end_date);
    if (isPast(deadline)) return 'text-red-600';
    
    const now = new Date();
    const diff = differenceInDays(deadline, now);
    if (diff <= 7) return 'text-yellow-600';
    
    return 'text-gray-600';
};

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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Permohonan</h3>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <button @click="showingReportModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Buat Laporan
                        </button>
                        <Link :href="route('app-requests.create')" class="inline-flex items-center justify-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 w-full sm:w-auto">
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
                                <input v-model="search" type="text" placeholder="Cari berdasarkan judul, nama pemohon, atau instansi..." class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
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
                <div v-if="paginatedRequests.length > 0" class="space-y-4">
                    <Link
                        v-for="request in paginatedRequests"
                        :key="request.id"
                        :href="route('app-requests.show', request.id)"
                        class="block p-4 bg-white shadow-sm transition duration-150 ease-in-out hover:shadow-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:rounded-lg"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start md:items-center">
                            <!-- Judul, Tanggal, dan Pemohon -->
                            <div class="md:col-span-4 lg:col-span-3">
                                <p class="font-semibold text-gray-900 group-hover:text-blue-600">{{ request.title }}</p>
                                <p class="text-sm text-gray-500">
                                    oleh {{ request.user.name }} &bull; {{ format_date(request.created_at) }}
                                </p>
                            </div>

                            <!-- Instansi -->
                            <div class="md:col-span-2 lg:col-span-2">
                                <p class="text-sm text-gray-500">Instansi</p>
                                <p class="font-medium text-gray-900">{{ request.instansi }}</p>
                            </div>

                             <!-- Development Activity -->
                            <div class="md:col-span-3 lg:col-span-3">
                                <template v-if="getNearestActivity(request)">
                                    <p class="text-sm text-gray-500">Aktivitas Terdekat</p>
                                    <div class="font-medium text-gray-900 text-sm whitespace-normal break-words">
                                       {{ getNearestActivity(request).description }}
                                    </div>
                                    <p class="text-xs mt-1" :class="getDeadlineColor(getNearestActivity(request))">
                                        Due: {{ format_date_activity(getNearestActivity(request)) }}
                                    </p>
                                </template>
                                <template v-else>
                                    <p class="text-sm text-gray-400 italic">-</p>
                                </template>
                            </div>

                            <!-- Status dan Aksi -->
                            <div class="md:col-span-3 lg:col-span-4 flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center justify-start md:justify-end gap-2 md:gap-4 mt-2 md:mt-0">
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
                    <h3 class="text-lg font-medium text-gray-900">Tidak Ditemukan Permohonan</h3>
                     <p v-if="filteredRequests.length === 0 && appRequests.length > 0" class="mt-1 text-sm text-gray-500">
                        Tidak ada permohonan yang cocok dengan filter pencarian Anda.
                    </p>
                    <p v-else class="mt-1 text-sm text-gray-500">
                        Belum ada permohonan yang diajukan.
                    </p>
                </div>

                <!-- Client-Side Pagination Controls -->
                <div class="mt-8 flex justify-center">
                    <ClientPagination 
                        :current-page="currentPage" 
                        :total-pages="totalPages" 
                        @page-change="changePage" 
                    />
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
