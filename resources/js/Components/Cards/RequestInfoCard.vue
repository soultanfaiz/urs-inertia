<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <!-- Card Header: Title on the left, Download button on the right -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Informasi Permohonan</h3>
            <a :href="route('app-requests.download', appRequest.id)" target="_blank"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Unduh PDF
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Pemohon</p>
                <p class="font-medium text-gray-900">{{ appRequest.user.name }}</p>
            </div>
            <div>
                <p class="text-gray-500">Instansi</p>
                <p class="font-medium text-gray-900">{{ appRequest.instansi }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-gray-500">Deskripsi</p>
                <p class="font-medium text-gray-900 whitespace-pre-wrap">{{ appRequest.description }}</p>
            </div>
        </div>
    </div>
</template>
