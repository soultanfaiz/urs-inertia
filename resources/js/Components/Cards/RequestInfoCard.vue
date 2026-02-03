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
        <!-- Card Header -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Informasi Permohonan</h3>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="md:col-span-2">
                <p class="text-gray-500">Judul</p>
                <p class="font-medium text-gray-900">{{ appRequest.title }}</p>
            </div>
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

        <!-- Download Section -->
        <div v-if="appRequest.file_path" class="mt-6 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-2">Dokumen Permohonan</p>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center min-w-0">
                    <svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" :title="appRequest.file_name || (appRequest.title + '.pdf')">{{ appRequest.file_name || (appRequest.title + '.pdf') }}</p>
                        <p class="text-xs text-gray-500">File PDF Permohonan</p>
                    </div>
                </div>
                <a :href="route('app-requests.download', appRequest.id)" target="_blank"
                    class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Unduh PDF
                </a>
            </div>
        </div>
    </div>
</template>
