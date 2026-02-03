<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { format, parseISO, isPast, differenceInDays, isSameDay, startOfDay } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ClientPagination from '@/Components/ClientPagination.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import axios from 'axios';

const props = defineProps({
    activities: {
        type: Array, // Receiving all data
        required: true,
    },
    availablePics: {
        type: Array,
        default: () => [],
    },
    pics: {
        type: Array,
        default: () => [],
    },
    appRequests: {
        type: Array,
        default: () => [],
    },
});

const get_pic_names = (picIds) => {
    if (!picIds || !Array.isArray(picIds) || picIds.length === 0) return '-';
    if (!props.pics) return '-';
    
    return picIds.map(id => {
        const pic = props.pics.find(p => p.id === id);
        return pic ? pic.name : '';
    }).filter(name => name).join(', ');
};

const format_date = (value) => {
    if (value) {
        const date = parseISO(value);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return format(date, 'd MMM yyyy', { locale: idLocale });
    }
    return '-';
}

const showAssignModal = ref(false);
const selectedPic = ref(null);

const form = useForm({
    activity_id: '',
    pic_id: '',
});

const openAssignModal = (pic) => {
    selectedPic.value = pic;
    form.pic_id = pic.id;
    form.activity_id = '';
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    form.reset();
    selectedPic.value = null;
};

const submitAssignment = () => {
    form.post(route('development-activities.assign-pic'), {
        onSuccess: () => {
            closeAssignModal();
        },
    });
};

// --- Add Task Workflow ---
const showSelectAppRequestModal = ref(false);
const showAddActivityModal = ref(false);
const selectedAppRequest = ref(null);

const newActivityForm = useForm({
    description: '',
    sub_activities: [''],
    start_date: '',
    end_date: '',
    pic: [],
});

const openSelectAppRequestModal = () => {
    showSelectAppRequestModal.value = true;
};

const closeSelectAppRequestModal = () => {
    showSelectAppRequestModal.value = false;
    selectedAppRequest.value = null;
    searchAppRequest.value = '';
};

// Filter logic for App Request selection modal
const searchAppRequest = ref('');
const filteredAppRequests = computed(() => {
    if (!searchAppRequest.value) return props.appRequests;
    return props.appRequests.filter(req => 
        req.title.toLowerCase().includes(searchAppRequest.value.toLowerCase()) ||
        req.instansi?.toLowerCase().includes(searchAppRequest.value.toLowerCase()) ||
        req.user?.name.toLowerCase().includes(searchAppRequest.value.toLowerCase())
    );
});

const selectAppRequest = (request) => {
    selectedAppRequest.value = request;
    showSelectAppRequestModal.value = false;
    showAddActivityModal.value = true;
    
    // Reset form
    newActivityForm.reset();
    newActivityForm.sub_activities = [''];
    newActivityForm.pic = [];
};

const closeAddActivityModal = () => {
    showAddActivityModal.value = false;
    newActivityForm.reset();
    selectedAppRequest.value = null;
};

const addSubActivityField = () => {
    newActivityForm.sub_activities.push('');
};

const removeSubActivityField = (index) => {
    if (newActivityForm.sub_activities.length > 1) {
        newActivityForm.sub_activities.splice(index, 1);
    }
};

const submitNewActivity = () => {
    if (!selectedAppRequest.value) return;

    newActivityForm
        .transform((data) => ({
            ...data,
            start_date: data.start_date ? new Date(data.start_date).toISOString() : null,
            end_date: data.end_date ? new Date(data.end_date).toISOString() : null,
        }))
        .post(route('app-request.development-activity.store', selectedAppRequest.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeAddActivityModal();
            }
        });
};

const picOptions = computed(() => {
    return (props.pics || []).map(pic => ({
        value: pic.id,
        label: `${pic.name} - ${pic.position}`
    }));
});

// --- Filter & Pagination Logic ---

const search = ref('');
const deadlineFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

const filteredActivities = computed(() => {
    let result = props.activities;

    // Filter by Deadline
    if (deadlineFilter.value) {
        const now = startOfDay(new Date());
        result = result.filter(activity => {
            if (!activity.end_date) return false;
            const endDate = parseISO(activity.end_date);
            
            if (deadlineFilter.value === 'overdue') {
                return isPast(endDate) && !isSameDay(endDate, now);
            } else if (deadlineFilter.value === 'today') {
                return isSameDay(endDate, now);
            } else if (deadlineFilter.value === 'week') {
                const diff = differenceInDays(endDate, now);
                return diff >= 0 && diff <= 7;
            }
            return true;
        });
    }

    // Filter by Search (Title, Instansi, Description, PIC)
    if (search.value) {
        const query = search.value.toLowerCase();
        result = result.filter(activity => {
            const titleMatch = activity.app_request.title.toLowerCase().includes(query);
            const instansiMatch = activity.app_request.instansi && activity.app_request.instansi.toLowerCase().includes(query);
            const descMatch = activity.description && activity.description.toLowerCase().includes(query);
            const picMatch = get_pic_names(activity.pic).toLowerCase().includes(query);
            
            return titleMatch || instansiMatch || descMatch || picMatch;
        });
    }

    return result;
});

// Reset page on filter change
watch([search, deadlineFilter], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredActivities.value.length / itemsPerPage));

const paginatedActivities = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredActivities.value.slice(start, end);
});

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

</script>

<template>
    <Head title="Daftar Tugas Pengembangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Daftar Tugas Pengembangan
                </h2>
                <button @click="openSelectAppRequestModal" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 w-full sm:w-auto">
                    + Tambah Tugas
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Card Filter & Pencarian -->
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Input Search -->
                        <div class="md:col-span-2">
                            <input v-model="search" type="text" placeholder="Cari berdasarkan judul, instansi, deskripsi, atau PIC..." class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                        </div>
                        <!-- Dropdown Deadline -->
                        <div>
                            <select v-model="deadlineFilter" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                <option value="">Semua Waktu</option>
                                <option value="overdue">Terlewat (Overdue)</option>
                                <option value="today">Hari Ini</option>
                                <option value="week">7 Hari Kedepan</option>
                            </select>
                        </div>
                    </div>
                </div>

                 <!-- Card: Daftar Tugas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 text-gray-900 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tugas Berjalan</h3>
                        
                        <!-- Empty State for Filter -->
                        <div v-if="filteredActivities.length === 0" class="text-center text-gray-500 py-4">
                            <span v-if="activities.length === 0">Tidak ada aktivitas pengembangan yang belum selesai.</span>
                            <span v-else>Tidak ada aktivitas yang cocok dengan filter Anda.</span>
                        </div>
                        
                        <div v-else>
                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                No
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Judul App Request
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                PIC
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Deskripsi Activity
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                End Date
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(activity, index) in paginatedActivities" :key="activity.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ activity.app_request.title }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ get_pic_names(activity.pic) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ activity.description }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ format_date(activity.end_date) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Link :href="route('app-requests.show', activity.app_request.id)" class="text-blue-600 hover:text-blue-900">
                                                    Lihat
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="md:hidden space-y-4">
                                <div v-for="(activity, index) in paginatedActivities" :key="activity.id" class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-base font-semibold text-gray-900 mb-1 flex-1">{{ activity.app_request.title }}</h4>
                                        <Link :href="route('app-requests.show', activity.app_request.id)" class="text-blue-600 text-sm font-medium hover:underline ml-2">
                                            Lihat
                                        </Link>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-2">
                                         PIC: {{ get_pic_names(activity.pic) }}
                                    </div>
                                    <p class="text-sm text-gray-700 mb-2">{{ activity.description }}</p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Deadline: <span class="font-medium ml-1">{{ format_date(activity.end_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div class="mt-6 flex justify-center">
                                <ClientPagination 
                                    :current-page="currentPage" 
                                    :total-pages="totalPages" 
                                    @page-change="changePage" 
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: PIC Available -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 text-gray-900 border-b border-gray-200">
                         <h3 class="text-lg font-medium text-gray-900 mb-4">PIC Tersedia (Tanpa Tugas Aktif)</h3>
                         <div v-if="availablePics.length === 0" class="text-center text-gray-500 py-4">
                            Semua PIC sedang mengerjakan tugas.
                        </div>
                         <div v-else>
                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nama
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Jabatan
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="pic in availablePics" :key="pic.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ pic.name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ pic.position }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button @click="openAssignModal(pic)" type="button" class="text-green-600 hover:text-green-900 font-medium hover:underline">
                                                    + Tambah Tugas
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="md:hidden space-y-4">
                                <div v-for="pic in availablePics" :key="pic.id" class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm flex justify-between items-center">
                                    <div>
                                        <h4 class="text-base font-semibold text-gray-900">{{ pic.name }}</h4>
                                        <p class="text-sm text-gray-500">{{ pic.position }}</p>
                                    </div>
                                    <button @click="openAssignModal(pic)" type="button" class="text-green-600 hover:text-green-900 font-medium text-sm border border-green-200 bg-green-50 px-3 py-1 rounded-md hover:bg-green-100 transition-colors">
                                        + Tugas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Assign Task -->
        <Modal :show="showAssignModal" @close="closeAssignModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Pilih Tugas untuk {{ selectedPic?.name }}
                </h2>

                <div class="mt-4">
                     <p class="text-sm text-gray-500 mb-2">
                        Silakan pilih aktivitas pengembangan yang akan ditugaskan kepada PIC ini.
                    </p>
                    
                    <div class="mt-4">
                        <label for="activity" class="block text-sm font-medium text-gray-700">Aktivitas Pengembangan</label>
                        <select
                            id="activity"
                            v-model="form.activity_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                        >
                            <option value="" disabled>Pilih Aktivitas...</option>
                            <option v-for="activity in activities" :key="activity.id" :value="activity.id">
                                {{ (activity.app_request.title + ' - ' + activity.description).length > 60 
                                    ? (activity.app_request.title + ' - ' + activity.description).substring(0, 60) + '...' 
                                    : (activity.app_request.title + ' - ' + activity.description) }}
                            </option>
                        </select>
                        <p v-if="form.errors.activity_id" class="mt-2 text-sm text-red-600">{{ form.errors.activity_id }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeAssignModal">
                        Batal
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing || !form.activity_id"
                        @click="submitAssignment"
                    >
                        Simpan
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Step 1: Select App Request -->
        <Modal :show="showSelectAppRequestModal" @close="closeSelectAppRequestModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Pilih Permohonan
                </h2>
                
                <!-- Search Box -->
                <div class="mb-4">
                    <input v-model="searchAppRequest" type="text" placeholder="Cari permohonan..." class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                </div>

                <div class="max-h-96 overflow-y-auto space-y-2">
                    <div v-if="filteredAppRequests.length === 0" class="text-center text-gray-500 py-4">
                        Tidak ada permohonan yang tersedia.
                    </div>
                    <button 
                        v-for="request in filteredAppRequests" 
                        :key="request.id"
                        @click="selectAppRequest(request)"
                        type="button"
                        class="w-full text-left p-3 rounded-md border border-gray-200 hover:bg-gray-50 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                    >
                        <div class="font-medium text-gray-900">{{ request.title }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                             {{ request.instansi }} &bull; Oleh: {{ request.user?.name }}
                        </div>
                    </button>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeSelectAppRequestModal">
                        Batal
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Step 2: Add Activity Form -->
        <Modal :show="showAddActivityModal" @close="closeAddActivityModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Tambah Tugas Baru: {{ selectedAppRequest?.title }}
                </h2>

                <form @submit.prevent="submitNewActivity">
                <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                    <div>
                        <label for="new_description" class="block text-sm font-medium text-gray-700">Deskripsi Aktivitas Utama</label>
                        <textarea id="new_description" v-model="newActivityForm.description" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" placeholder="Contoh: Implementasi Modul Keuangan" required></textarea>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-800 mb-2">Detail Pekerjaan</h4>
                        <div class="space-y-2">
                            <div v-for="(sub, index) in newActivityForm.sub_activities" :key="index" class="flex items-center space-x-2">
                                <input type="text" v-model="newActivityForm.sub_activities[index]" @keydown.enter.prevent="addSubActivityField" class="block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Membuat endpoint API">
                                <button type="button" @click="removeSubActivityField(index)" v-show="newActivityForm.sub_activities.length > 1" class="text-red-500 hover:text-red-700" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" @click="addSubActivityField" class="mt-3 text-sm text-blue-600 hover:text-blue-800">+ Tambah Detail</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai (Opsional)</label>
                            <input type="datetime-local" id="new_start_date" v-model="newActivityForm.start_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                         <div>
                            <label for="new_end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai (Opsional)</label>
                            <input type="datetime-local" id="new_end_date" v-model="newActivityForm.end_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">PIC (Opsional)</label>
                            <MultiSelect
                                v-model="newActivityForm.pic"
                                :options="picOptions"
                                placeholder="Pilih PIC..."
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeAddActivityModal">
                        Batal
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': newActivityForm.processing }"
                        :disabled="newActivityForm.processing"
                        type="submit"
                    >
                        Simpan
                    </PrimaryButton>
                </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
