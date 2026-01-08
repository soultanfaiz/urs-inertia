<script setup>
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    requests: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const selectedIds = ref([]);
const selectAll = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal) {
        selectedIds.value = [];
        selectAll.value = false;
    }
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = props.requests.map(r => r.id);
    } else {
        selectedIds.value = [];
    }
};

const submit = () => {
    if (selectedIds.value.length === 0) return;

    const page = usePage();

    // 1. Coba ambil token dari meta tag (Cara standar Laravel)
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // 2. Fallback: Coba ambil dari props Inertia (Jika dibagikan via HandleInertiaRequests)
    if (!csrfToken && page.props.csrf_token) {
        csrfToken = page.props.csrf_token;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('app-requests.generate-report');
    form.target = '_blank';

    // Input CSRF Token
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';

    if (!csrfToken) {
        alert('Gagal memproses: CSRF Token tidak ditemukan. Silakan refresh halaman atau pastikan meta tag csrf-token tersedia.');
        return;
    }
    tokenInput.value = csrfToken;
    form.appendChild(tokenInput);

    // Input Array ID
    selectedIds.value.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'request_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    emit('close');
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="lg">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Buat Laporan PDF
            </h2>
            <p class="mt-1 text-sm text-gray-600 mb-4">
                Pilih permohonan yang ingin disertakan dalam laporan.
            </p>

            <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <span class="ml-2 text-sm font-bold text-gray-700">Pilih Semua</span>
                </label>
            </div>

            <div class="max-h-60 overflow-y-auto border rounded-md p-2 space-y-2 bg-gray-50">
                <div v-for="req in requests" :key="req.id" class="flex items-start">
                    <input type="checkbox" :id="`req_${req.id}`" :value="req.id" v-model="selectedIds" class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <label :for="`req_${req.id}`" class="ml-2 text-sm text-gray-700 cursor-pointer">
                        <span class="font-medium">{{ req.title }}</span>
                        <br>
                        <span class="text-xs text-gray-500">{{ req.instansi }} - {{ new Date(req.created_at).toLocaleDateString('id-ID') }}</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton class="ms-3" @click="submit" :disabled="selectedIds.length === 0" :class="{ 'opacity-50': selectedIds.length === 0 }">
                    Cetak PDF
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
