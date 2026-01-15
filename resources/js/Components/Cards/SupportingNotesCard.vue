<script setup>
import { ref, computed, watch } from 'vue';
import { format } from 'date-fns';
import { usePage } from '@inertiajs/vue3';
import { marked } from 'marked';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['open-add-note-modal', 'open-edit-note-modal']);

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));
const canAddNote = computed(() => isAdmin.value);

// Tombol laporan: Admin atau user dari instansi yang sama
const canDownloadReport = computed(() => {
    if (isAdmin.value) return true;
    // Cek apakah user berasal dari instansi yang sama dengan permohonan
    return user.value?.instansi === props.appRequest.instansi;
});

const notes = ref([]);

watch(() => props.appRequest.supporting_notes, (newNotes) => {
    notes.value = (newNotes || []).map(note => ({
        id: note.id,
        user_name: note.user ? note.user.name : 'Sistem',
        title: note.title,
        reason: note.note,
        generated_note: note.generated_note,
        image_path: note.image_path,
        created_at: note.created_at,
        open: false, // Default tertutup
    })).sort((a, b) => new Date(b.created_at) - new Date(a.created_at)); // Urutkan terbaru
}, { immediate: true });

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy HH:mm');
};
</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="mb-4 border-b border-gray-200 pb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h3 class="text-lg font-semibold text-gray-800">Catatan Pendukung</h3>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <a v-if="canDownloadReport && notes.length > 0" :href="route('supporting-notes.generate-report', appRequest.id)" target="_blank" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buat Laporan
                </a>
                <button v-if="canAddNote" @click="emit('open-add-note-modal')" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Catatan
                </button>
            </div>
        </div>

        <div v-if="notes.length > 0" class="border border-gray-200 rounded-lg overflow-hidden divide-y divide-gray-200">
            <div v-for="note in notes" :key="note.id" class="bg-white">
                <!-- Header (Click to toggle) -->
                <div class="p-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div @click="note.open = !note.open" class="flex-grow cursor-pointer flex flex-col">
                        <span class="font-medium text-gray-900 text-sm">{{ note.title || 'Catatan Tanpa Judul' }}</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500">{{ note.user_name }}</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-xs text-gray-500">{{ formatDate(note.created_at) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="canAddNote" @click.stop="emit('open-edit-note-modal', note)" class="p-1 text-gray-400 hover:text-blue-600 transition-colors" title="Edit Catatan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <div @click="note.open = !note.open" class="cursor-pointer">
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="{ 'rotate-180': note.open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Body (Collapsible) -->
                <div v-show="note.open" class="p-4 bg-gray-50 border-t border-gray-100">
                     <!-- Menggunakan v-html untuk merender hasil Tiptap yang mungkin berisi MD atau HTML -->
                    <div class="text-sm text-gray-700 prose prose-sm max-w-none note-content" v-html="marked.parse(note.reason)"></div>

                    <!-- Tampilkan gambar jika ada -->
                    <div v-if="note.image_path" class="mt-3">
                        <img :src="note.image_path" alt="Gambar Pendukung" class="max-h-64 rounded-lg border border-gray-200 shadow-sm object-contain">
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="py-6 text-center text-gray-500">
            <p class="text-sm">Belum ada catatan pendukung.</p>
        </div>
    </div>
</template>
