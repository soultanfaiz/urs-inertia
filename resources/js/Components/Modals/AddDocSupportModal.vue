<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appRequest: {
        type: Object,
        required: true,
    },
    history: {
        type: Object,
        // history bisa null jika permohonan masih dalam tahap pertama
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    file_support_pdf: null,
});

// Perhatikan modal untuk membuka dan mengatur ulang formulir
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.history) return;

    // Pastikan route 'app-request.history.add-document' sudah terdefinisi di web.php atau api.php untuk request POST.
    form.post(route('app-request.history.add-document', { appRequest: props.appRequest.id, history: props.history.id }), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => {
            // Jika ada error pada file, input file akan otomatis dibersihkan oleh browser.
            // Kita hanya perlu memastikan state form kita juga ikut di-reset.
            if (form.errors.file_support_pdf) {
                form.reset('file_support_pdf');
            }
        },
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" focusable>
        <div v-if="history" class="p-6">
            <form @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    Tambahkan Dokumen Pendukung
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Unggah dokumen (PDF) sebagai bukti untuk melanjutkan ke tahap selanjutnya.
                </p>

                <!-- Upload File PDF -->
                <div class="mt-6">
                    <label for="file_support_pdf" class="block text-sm font-medium text-gray-700">Upload File (PDF)</label>
                    <input
                        type="file"
                        id="file_support_pdf"
                        @input="form.file_support_pdf = $event.target.files[0]"
                        accept=".pdf"
                        required
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        :class="{ 'rounded-md ring-1 ring-red-500': form.errors.file_support_pdf }"
                    >
                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">{{ form.progress.percentage }}%</progress>
                    <p class="mt-1 text-xs text-gray-500">Maksimal ukuran file: 5MB.</p>
                    <InputError :message="form.errors.file_support_pdf" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="submit">
                        Unggah Dokumen
                    </PrimaryButton>
                </div>
            </form>
        </div>
        <div v-else class="p-6">
            <p class="text-gray-600">Tidak dapat menemukan riwayat proses yang sesuai untuk menambahkan dokumen.</p>
        </div>
    </Modal>
</template>
