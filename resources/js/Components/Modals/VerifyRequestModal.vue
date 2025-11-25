<script setup>
import { computed, watch } from "vue";
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
    appRequest: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const enums = computed(() => usePage().props.enums);

const form = useForm({
    verification_status: enums.value.verificationStatus.DISETUJUI,
    reason: '',
});

// Watch for the modal to open and reset the form to its default state
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            form.reset();
            form.clearErrors();
        }
    }
);

const submit = () => {
    form.post(route('app-requests.verify', props.appRequest.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const closeModal = () => {
    emit("close");
};
</script>

<template>
    <Modal :show="show" @close="closeModal" focusable>
        <form @submit.prevent="submit" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Verifikasi Permohonan</h2>
            <p class="mt-1 text-sm text-gray-600">
                Pilih status baru untuk permohonan ini. Jika ditolak, alasan wajib diisi.
            </p>

            <!-- Pilihan Status -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Opsi Setujui -->
                <div>
                    <input type="radio" name="verification_status" id="status_approve" :value="enums.verificationStatus.DISETUJUI" v-model="form.verification_status" class="peer sr-only" />
                    <label for="status_approve" class="flex w-full cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-white p-3 text-sm font-medium text-gray-700 ring-2 ring-transparent hover:bg-gray-50 peer-checked:border-green-600 peer-checked:bg-green-500 peer-checked:text-white peer-checked:ring-green-500">
                        Setujui
                    </label>
                </div>
                <!-- Opsi Tolak -->
                <div>
                    <input type="radio" name="verification_status" id="status_reject" :value="enums.verificationStatus.DITOLAK" v-model="form.verification_status" class="peer sr-only" />
                    <label for="status_reject" class="flex w-full cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-white p-3 text-sm font-medium text-gray-700 ring-2 ring-transparent hover:bg-gray-50 peer-checked:border-red-600 peer-checked:bg-red-500 peer-checked:text-white peer-checked:ring-red-500">
                        Tolak Permohonan
                    </label>
                </div>
            </div>
            <InputError :message="form.errors.verification_status" class="mt-2" />

            <!-- Alasan (jika ditolak) -->
            <div class="mt-6">
                <label for="reason" class="block text-sm font-medium text-gray-700">
                    Alasan/Catatan <span class="text-gray-500">(Wajib diisi jika ditolak)</span>
                </label>
                <textarea
                    id="reason"
                    v-model="form.reason"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.reason }"
                ></textarea>
                <InputError :message="form.errors.reason" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">
                    Batal
                </SecondaryButton>

                <PrimaryButton type="submit" class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Kirim Verifikasi
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
