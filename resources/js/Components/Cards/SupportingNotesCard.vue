<script setup>
import { ref, computed, watch } from 'vue';
import { format } from 'date-fns';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['open-add-note-modal']);

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));
const canAddNote = computed(() => isAdmin.value);

const notes = ref([]);

watch(() => props.appRequest.supporting_notes, (newNotes) => {
    notes.value = (newNotes || []).map(note => ({
        id: note.id,
        user_name: note.user ? note.user.name : 'Sistem',
        title: note.title,
        reason: note.note,
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
        <div class="mb-4 border-b border-gray-200 pb-4 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Catatan Pendukung</h3>
            <button v-if="canAddNote" @click="emit('open-add-note-modal')" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Tambah Catatan
            </button>
        </div>

        <div v-if="notes.length > 0" class="border border-gray-200 rounded-lg overflow-hidden divide-y divide-gray-200">
            <div v-for="note in notes" :key="note.id" class="bg-white">
                <!-- Header (Click to toggle) -->
                <div @click="note.open = !note.open" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900 text-sm">{{ note.title || 'Catatan Tanpa Judul' }}</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500">{{ note.user_name }}</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-xs text-gray-500">{{ formatDate(note.created_at) }}</span>
                        </div>
                    </div>
                    <div>
                        <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="{ 'rotate-180': note.open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Body (Collapsible) -->
                <div v-show="note.open" class="p-4 bg-gray-50 border-t border-gray-100">
                     <!-- Menggunakan v-html untuk merender hasil Tiptap -->
                    <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="note.reason"></div>

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
