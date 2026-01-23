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

// Get the label for a given status value
// Get the label for a given status value
const getStatusLabel = (statusValue) => {
    return enums.value.requestStatusLabels?.[statusValue] || statusValue;
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
        form.status = props.appRequest.status;
        form.end_date = props.appRequest.end_date
            ? format(new Date(props.appRequest.end_date), 'yyyy-MM-dd')
            : format(new Date(), 'yyyy-MM-dd');
    }
});

const submit = () => {
    form.patch(route('app-requests.update-status', props.appRequest.id), {
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
                Pilih status baru untuk permohonan ini.
            </p>

            <!-- Status Selection (Radio Buttons) -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="space-y-2">
                    <div v-for="status in allStatuses" :key="status" class="flex items-center">
                        <input
                            type="radio"
                            :id="status"
                            :value="status"
                            v-model="form.status"
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                        >
                        <label :for="status" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                            {{ getStatusLabel(status) }}
                        </label>
                    </div>
                </div>
                <InputError :message="form.errors.status" class="mt-2" />
            </div>

            <!-- Form Fields -->
            <div>
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

                <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Simpan Perubahan
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
