<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; // Assuming you have a pagination component
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    appRequests: {
        type: Object,
        required: true,
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
        case 'permohonan':
            text = 'Permohonan';
            a_class = 'bg-blue-100 text-blue-800';
            break;
        case 'urs':
            text = 'URS';
            a_class = 'bg-cyan-100 text-cyan-800';
            break;
        case 'pengembangan':
            text = 'Pengembangan';
            a_class = 'bg-yellow-100 text-yellow-800';
            break;
        case 'uat':
            text = 'UAT';
            a_class = 'bg-purple-100 text-purple-800';
            break;
        case 'selesai':
            text = 'Selesai';
            a_class = 'bg-green-100 text-green-800';
            break;
    }

    return { text, class: a_class };
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
                <div class="flex items-center justify-between mb-6 px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Permohonan</h3>
                    <Link :href="route('app-requests.create')" class="inline-flex items-center justify-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Ajukan Permohonan
                    </Link>
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
                                    <Link :href="route('app-requests.download', request.id)" @click.stop as="a" target="_blank" class="p-2 text-gray-400 rounded-full hover:text-blue-600" title="Unduh PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </Link>
                                    <!-- Edit/Delete buttons are only for admin for now -->
                                    <template v-if="user.roles && user.roles.includes('admin')">
                                         <Link :href="route('app-requests.edit', request.id)" @click.stop class="p-2 text-gray-400 rounded-full hover:text-blue-600" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </Link>
                                        <Link :href="route('app-requests.destroy', request.id)" method="delete" as="button" @click.stop.prevent="confirm('Are you sure?')" class="p-2 text-gray-400 rounded-full hover:text-red-600" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </Link>
                                    </template>
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
    </AuthenticatedLayout>
    </div>
</template>
