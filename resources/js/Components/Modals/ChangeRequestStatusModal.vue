<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { format } from 'date-fns';
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

// --- Computed Properties for Status Logic ---

// Get the array of status values from the enums object
const allStatuses = computed(() => Object.values(enums.value.requestStatus));

// Find the index of the current status
const currentIndex = computed(() => allStatuses.value.indexOf(props.appRequest.status));

// Determine the next status in the sequence
const nextStatus = computed(() => {
    const nextIndex = currentIndex.value + 1;
    return nextIndex < allStatuses.value.length ? allStatuses.value[nextIndex] : null;
});

// Get the label for a given status value
const getStatusLabel = (statusValue) => {
    const entry = Object.entries(enums.value.requestStatus).find(([key, value]) => value === statusValue);
    // Capitalize the first letter of the key
    return entry ? entry[0].charAt(0).toUpperCase() + entry[0].slice(1).toLowerCase() : '';
};

// --- Form Handling ---

const form = useForm({
    status: '',
    reason: '',
    end_date: '',
});

// Watch for the modal to open and reset the form
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
        // Set default values when modal opens
        form.status = nextStatus.value ?? '';
        form.end_date = props.appRequest.end_date
            ? format(new Date(props.appRequest.end_date), 'yyyy-MM-dd')
            : format(new Date(), 'yyyy-MM-dd');
    }
});

const submit = () => {
    if (!nextStatus.value) return; // Prevent submission if there's no next status

    form.patch(route('app-request.update-status', props.appRequest.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
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
                Ubah Status Permohonan
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Pilih progres selanjutnya untuk permohonan ini.
            </p>

            <!-- Status Flow Display -->
            <div class="mt-6">
                <div v-if="nextStatus" class="flex items-center justify-center gap-2 text-sm">
                    <span class="inline-flex items-center justify-center rounded-md border border-blue-500 bg-blue-500 px-4 py-2 font-medium text-white">
                        {{ getStatusLabel(appRequest.status) }}
                    </span>

                    <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>

                    <span class="inline-flex items-center justify-center rounded-md border-2 border-blue-600 bg-blue-600 px-4 py-2 font-medium text-white shadow-lg">
                        {{ getStatusLabel(nextStatus) }}
                    </span>
                </div>
                <p v-else class="rounded-md bg-gray-100 p-4 text-center text-gray-600">
                    Permohonan sudah berada di tahap akhir.
                </p>
            </div>

            <!-- Form Fields -->
            <div v-if="nextStatus">
                <!-- Reason/Notes -->
                <div class="mt-6">
                    <label for="reasonChange" class="block text-sm font-medium text-gray-700">Alasan/Catatan (Opsional)</label>
                    <textarea id="reasonChange" v-model="form.reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    <InputError :message="form.errors.reason" class="mt-2" />
                </div>

                <!-- End Date -->
                <div class="mt-6">
                    <label for="end_date" class="block text-sm font-medium text-gray-700">
                        Tenggat Waktu (Deadline) <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="end_date" v-model="form.end_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.end_date }">
                    <p class="mt-1 text-xs text-gray-500">Wajib diisi. Set tanggal tenggat waktu untuk tahap ini.</p>
                    <InputError :message="form.errors.end_date" class="mt-2" />
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">
                    Batal
                </SecondaryButton>

                <PrimaryButton v-if="nextStatus" class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Simpan Perubahan
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
