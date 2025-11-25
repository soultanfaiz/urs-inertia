<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ProgressCard from '@/Components/Cards/ProgressCard.vue';
import RequestInfoCard from '@/Components/Cards/RequestInfoCard.vue';
import SupportingDocumentsCard from '@/Components/Cards/SupportingDocumentsCard.vue';
import DevelopmentActivityCard from '@/Components/Cards/DevelopmentActivityCard.vue';
import VerifyRequestModal from '@/Components/Modals/VerifyRequestModal.vue';
import RejectionAlert from '@/Components/Alerts/RejectionAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    }
});

// --- Modal State Management ---
const user = computed(() => usePage().props.auth.user);
const enums = computed(() => usePage().props.enums);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));

const canVerify = computed(() => {
    return isAdmin.value &&
           props.appRequest.status === enums.value.requestStatus.PERMOHONAN &&
           props.appRequest.verification_status !== enums.value.verificationStatus.DITOLAK;
});

// State for Add Modals
const showingAddDocSupportModal = ref(false);
const showingAddImageSupportModal = ref(false);

// State for Verification Modals
const showingVerifyRequestModal = ref(false);
const showingVerifyDocModal = ref(false);
const showingVerifyImageModal = ref(false);
const docToVerifyId = ref(null);
const imageToVerifyId = ref(null);

const openDocVerifyModal = (docId) => {
    docToVerifyId.value = docId;
    showingVerifyDocModal.value = true;
};

const openImageVerifyModal = (imageId) => {
    imageToVerifyId.value = imageId;
    showingVerifyImageModal.value = true;
};
</script>

<template>
    <Head title="Detail Permintaan Aplikasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Permintaan Aplikasi</h2>
                <button v-if="canVerify" @click="showingVerifyRequestModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto" title="Verifikasi Permohonan">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Verifikasi Permohonan
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6">
                    <!-- Alert jika permohonan ditolak -->
                    <RejectionAlert :app-request="appRequest" />

                    <!-- Cards -->
                    <ProgressCard :app-request="appRequest" />
                    <RequestInfoCard :app-request="appRequest" />
                    <SupportingDocumentsCard
                        :app-request="appRequest"
                        @open-add-doc-modal="showingAddDocSupportModal = true"
                        @open-add-image-modal="showingAddImageSupportModal = true"
                        @open-verify-doc-modal="openDocVerifyModal"
                        @open-verify-image-modal="openImageVerifyModal"
                    />
                    <DevelopmentActivityCard :app-request="appRequest" />
                </div>
            </div>
        </div>

        <!-- Modals -->
        <VerifyRequestModal
            :show="showingVerifyRequestModal"
            :app-request="appRequest"
            @close="showingVerifyRequestModal = false"
        />
    </AuthenticatedLayout>
</template>
