<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ProgressCard from '@/Components/Cards/ProgressCard.vue';
import RequestInfoCard from '@/Components/Cards/RequestInfoCard.vue';
import RequestHistoryCard from '@/Components/Cards/RequestHistoryCard.vue';
import SupportingDocumentsCard from '@/Components/Cards/SupportingDocumentsCard.vue';
import SupportingNotesCard from '@/Components/Cards/SupportingNotesCard.vue';
import DevelopmentActivityCard from '@/Components/Cards/DevelopmentActivityCard.vue';
import VerifyRequestModal from '@/Components/Modals/VerifyRequestModal.vue';
import ChangeRequestStatusModal from '@/Components/Modals/ChangeRequestStatusModal.vue';
import AddDocSupportModal from '@/Components/Modals/AddDocSupportModal.vue';
import AddImageSupportModal from '@/Components/Modals/AddImageSupportModal.vue';
import AddSupportingNoteModal from '@/Components/Modals/AddSupportingNoteModal.vue';
import VerifyDocSupportModal from '@/Components/Modals/VerifyDocSupportModal.vue';
import VerifyImageSupportModal from '@/Components/Modals/VerifyImageSupportModal.vue';
import RejectionAlert from '@/Components/Alerts/RejectionAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
    pics: {
        type: Array,
        default: () => [],
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

const canAddSupportDocuments = computed(() => {
    const isOwner = user.value.id === props.appRequest.user_id;
    return (isAdmin.value || isOwner) &&
           props.appRequest.status !== enums.value.requestStatus.PERMOHONAN &&
           props.appRequest.status !== enums.value.requestStatus.SELESAI &&
           props.appRequest.verification_status === enums.value.verificationStatus.DISETUJUI;
});

const latestHistoryForCurrentStatus = computed(() => {
    if (!props.appRequest || !props.appRequest.histories) {
        return null;
    }
    // Histories are pre-sorted descending, so find the first one matching the current status.
    return props.appRequest.histories.find(h => h.status === props.appRequest.status);
});

const canChangeStatus = computed(() => {
    // 1. Must be admin
    if (!isAdmin.value) return false;

    return true;
});

// State for Add Modals
const showingAddDocSupportModal = ref(false);
const showingAddImageSupportModal = ref(false);
const showingAddNoteModal = ref(false);
const noteToEdit = ref(null);

const openAddNoteModal = () => {
    noteToEdit.value = null;
    showingAddNoteModal.value = true;
};

const openEditNoteModal = (note) => {
    noteToEdit.value = note;
    showingAddNoteModal.value = true;
};

// State for Verification Modals
const showingVerifyRequestModal = ref(false);
const showingChangeRequestStatusModal = ref(false);

const showingVerifyDocModal = ref(false);
const docToVerify = ref(null);
const showingVerifyImageModal = ref(false);
const imageToVerify = ref(null);

const openDocVerifyModal = (doc) => {
    docToVerify.value = doc;
    showingVerifyDocModal.value = true;
};

const openImageVerifyModal = (image) => {
    imageToVerify.value = image;
    showingVerifyImageModal.value = true;
};

// Find the latest history entry that is not a 'verification' type.
// This is used for adding new supporting documents from the header button.
const latestHistoryForUpload = computed(() => {
    if (!props.appRequest || !props.appRequest.histories) {
        return null;
    }
    // Find the first history record that is a status change, which is where new documents should be attached.
    return props.appRequest.histories.find(h => h.type === 'status_change') ??
           // Fallback to the very first history record if no status change is found (edge case)
           props.appRequest.histories[0] ?? null;
});
</script>

<template>
    <Head title="Detail Permohonan Aplikasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Permohonan Aplikasi</h2>
                    <Link :href="route('app-requests.index')" class="text-sm text-blue-600 hover:underline mt-1">
                        &larr; Kembali ke Daftar
                    </Link>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                    <button v-if="canChangeStatus" @click="showingChangeRequestStatusModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                        </svg>
                        Ubah Status
                    </button>
                    <button v-if="canAddSupportDocuments" @click="showingAddDocSupportModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 w-full sm:w-auto" title="Tambahkan Dokumen Pendukung">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Tambahkan Dokumen
                    </button>
                    <button v-if="canVerify" @click="showingVerifyRequestModal = true" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto" title="Verifikasi Permohonan">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Verifikasi Permohonan
                    </button>
                </div>
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
                    <SupportingNotesCard
                        :app-request="appRequest"
                        @open-add-note-modal="openAddNoteModal"
                        @open-edit-note-modal="openEditNoteModal"
                    />
                    <DevelopmentActivityCard :app-request="appRequest" :pics="pics" />
                    <RequestHistoryCard :app-request="appRequest" />
                </div>
            </div>
        </div>

        <!-- Modals -->
        <VerifyRequestModal
            :show="showingVerifyRequestModal"
            :app-request="appRequest"
            @close="showingVerifyRequestModal = false"
        />
        <ChangeRequestStatusModal
            :show="showingChangeRequestStatusModal"
            :app-request="appRequest"
            :latest-history="latestHistoryForCurrentStatus"
            @close="showingChangeRequestStatusModal = false"
        />
        <AddDocSupportModal
            :show="showingAddDocSupportModal"
            :app-request="appRequest"
            :history="latestHistoryForUpload"
            @close="showingAddDocSupportModal = false"
        />
        <AddImageSupportModal
            :show="showingAddImageSupportModal"
            :app-request="appRequest"
            :history="latestHistoryForUpload"
            @close="showingAddImageSupportModal = false"
        />
        <AddSupportingNoteModal
            :show="showingAddNoteModal"
            :app-request="appRequest"
            :note-to-edit="noteToEdit"
            @close="showingAddNoteModal = false"
        />
        <VerifyDocSupportModal
            :show="showingVerifyDocModal"
            :doc-support="docToVerify"
            @close="showingVerifyDocModal = false"
        />
        <VerifyImageSupportModal
            :show="showingVerifyImageModal"
            :image-support="imageToVerify"
            @close="showingVerifyImageModal = false"
        />
    </AuthenticatedLayout>
</template>
