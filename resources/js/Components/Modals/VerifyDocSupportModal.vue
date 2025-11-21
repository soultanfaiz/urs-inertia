<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    docSupport: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const enums = computed(() => usePage().props.enums);

const form = useForm({
    doc_verification_status: enums.value.verificationStatus.DISETUJUI,
    doc_reason: '',
});

// Perhatikan modal untuk membuka dan mengatur ulang formulir ke keadaan default
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    form.post(route('app-request.doc-support.verify', props.docSupport.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" focusable>
        <form @submit.prevent="submit" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Verifikasi Dokumen Pendukung
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Setujui atau tolak dokumen <span class="font-bold">{{ docSupport.file_name }}</span>. Jika ditolak, alasan wajib diisi.
            </p>

            <!-- Pilihan Status -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Opsi Setujui -->
                <div>
                    <input type="radio" name="doc_verification_status" :id="`doc_status_approve_${docSupport.id}`" :value="enums.verificationStatus.DISETUJUI" v-model="form.doc_verification_status" class="peer sr-only">
                    <label :for="`doc_status_approve_${docSupport.id}`" class="flex items-center justify-center w-full p-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50 peer-checked:border-green-600 peer-checked:bg-green-500 peer-checked:text-white ring-2 ring-transparent peer-checked:ring-green-500">
                        Setujui
                    </label>
                </div>
                <!-- Opsi Tolak -->
                <div>
                    <input type="radio" name="doc_verification_status" :id="`doc_status_reject_${docSupport.id}`" :value="enums.verificationStatus.DITOLAK" v-model="form.doc_verification_status" class="peer sr-only">
                    <label :for="`doc_status_reject_${docSupport.id}`" class="flex items-center justify-center w-full p-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50 peer-checked:border-red-600 peer-checked:bg-red-500 peer-checked:text-white ring-2 ring-transparent peer-checked:ring-red-500">
                        Tolak
                    </label>
                </div>
            </div>
            <InputError :message="form.errors.doc_verification_status" class="mt-2" />

            <!-- Alasan (jika ditolak) -->
            <div class="mt-6">
                <label for="doc_reason" class="block text-sm font-medium text-gray-700">
                    Alasan/Catatan <span class="text-gray-500">(Wajib diisi jika ditolak)</span>
                </label>
                <textarea id="doc_reason" v-model="form.doc_reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.doc_reason }"></textarea>
                <InputError :message="form.errors.doc_reason" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">
                    Batal
                </SecondaryButton>

                <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Kirim Verifikasi
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
