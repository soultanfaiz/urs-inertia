<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Assuming these components will be created.
// You might need to adjust the paths.
import ProgressCard from '@/Components/Cards/ProgressCard.vue';
import RequestInfoCard from '@/Components/Cards/RequestInfoCard.vue';
import SupportingDocumentsCard from '@/Components/Cards/SupportingDocumentsCard.vue';
import DevelopmentActivityCard from '@/Components/Cards/DevelopmentActivityCard.vue';
import RequestHistoryCard from '@/Components/Cards/RequestHistoryCard.vue';
import RejectionAlert from '@/Components/Alerts/RejectionAlert.vue';

// Modal Components
import VerifyRequestModal from '@/Components/Modals/VerifyRequestModal.vue';
import ChangeRequestStatusModal from '@/Components/Modals/ChangeRequestStatusModal.vue';
import AddDocSupportModal from '@/Components/Modals/AddDocSupportModal.vue';
import AddImageSupportModal from '@/Components/Modals/AddImageSupportModal.vue';
import VerifyDocSupportModal from '@/Components/Modals/VerifyDocSupportModal.vue';
import VerifyImageSupportModal from '@/Components/Modals/VerifyImageSupportModal.vue';


const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

const user = computed(() => usePage().props.auth.user);
const enums = computed(() => usePage().props.enums);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));
const isOwner = computed(() => user.value.id === props.appRequest.user_id);

// --- Modal visibility state ---
const showingVerifyRequestModal = ref(false);
const showingChangeRequestStatusModal = ref(false);
const showingAddDocSupportModal = ref(false);
const showingAddImageSupportModal = ref(false);

// --- Computed properties for complex logic from Blade ---

// Equivalent to $appRequest->verification_status === VerificationStatus::MENUNGGU
const isAwaitingInitialVerification = computed(() => {
    return props.appRequest.verification_status?.toUpperCase() === 'MENUNGGU';
});

// Equivalent to $appRequest->verification_status === VerificationStatus::DISETUJUI && $appRequest->status !== RequestStatus::SELESAI
const isApprovedAndNotFinished = computed(() => {
    return props.appRequest.verification_status === enums.value.verificationStatus.DISETUJUI &&
           props.appRequest.status !== enums.value.requestStatus.SELESAI;
});

// Find the latest history record that matches the current request status
const latestHistoryForCurrentStatus = computed(() => {
    if (!props.appRequest.histories) return null;
    return [...props.appRequest.histories]
        .filter(h => h.status === props.appRequest.status)
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] || null;
});

// Check if the "Change Status" button should be enabled
const canChangeStatus = computed(() => {
    const history = latestHistoryForCurrentStatus.value;
    if (!history || !history.doc_supports || history.doc_supports.length === 0) {
        return false;
    }
    // Check if there is at least one approved document support
    return history.doc_supports.some(doc => doc.verification_status === enums.value.verificationStatus.DISETUJUI);
});

// Flattened lists for verification modals
const allDocSupports = computed(() => {
    return props.appRequest.histories?.flatMap(h => h.doc_supports) ?? [];
});

const allImageSupports = computed(() => {
    return props.appRequest.histories?.flatMap(h => h.image_supports) ?? [];
});

</script>

<template>
    <Head :title="`Detail: ${appRequest.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 min-w-0">
                <!-- Title and Back Link -->
                <div class="flex-1 min-w-0">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                        Detail Permohonan: {{ appRequest.title }}
                    </h2>
                    <Link :href="route('app-requests.index')" class="text-sm text-blue-600 hover:underline">
                        &larr; Kembali ke Daftar
                    </Link>
                </div>

                <!-- Action Buttons -->
                <div class="flex-shrink-0 flex flex-wrap items-center justify-start gap-2">
                    <!-- Verify Request Button (Admin only) -->
                    <button
                        v-if="isAdmin && isAwaitingInitialVerification"
                        @click="showingVerifyRequestModal = true"
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Verifikasi Permohonan
                    </button>

                    <template v-if="(isAdmin || isOwner) && isApprovedAndNotFinished">
                        <!-- Add Document Button -->
                        <button
                            @click="showingAddDocSupportModal = true"
                            type="button"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                            </svg>
                            Tambahkan Dokumen
                        </button>

                        <!-- Change Status Button (Admin only) -->
                        <button
                            v-if="isAdmin && canChangeStatus"
                            @click="showingChangeRequestStatusModal = true"
                            type="button"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                             <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                            </svg>
                            Ubah Status
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="space-y-8">
                    <!-- Rejection Notification -->
                    <RejectionAlert :app-request="appRequest" />

                    <!-- Progress Card -->
                    <ProgressCard :app-request="appRequest" />

                    <!-- Request Info Card -->
                    <RequestInfoCard :app-request="appRequest" />

                    <!-- Verified Supporting Documents Card -->
                    <SupportingDocumentsCard
                        v-if="appRequest.status !== enums?.value?.requestStatus?.PERMOHONAN"
                        :app-request="appRequest"
                    />

                    <!-- Development Activity Card -->
                    <DevelopmentActivityCard :app-request="appRequest" :request-status-enum="enums?.value?.requestStatus" />

                    <!-- Request History Card -->
                    <RequestHistoryCard :app-request="appRequest" />
                </div>
            </div>
        </div>

        <!-- Modals -->
        <template v-if="isAdmin">
            <!-- Verify Request Modal -->
            <VerifyRequestModal
                :show="showingVerifyRequestModal"
                :app-request="appRequest"
                @close="showingVerifyRequestModal = false"
            />
            <!-- Change Status Modal -->
            <ChangeRequestStatusModal
                :show="showingChangeRequestStatusModal"
                :app-request="appRequest"
                @close="showingChangeRequestStatusModal = false"
            />
            <!-- Verify Document Modals (one for each document) -->
            <template v-for="doc in allDocSupports" :key="`doc-${doc.id}`">
                 <VerifyDocSupportModal :doc-support="doc" />
            </template>

            <!-- Verify Image Modals (one for each image) -->
            <template v-for="image in allImageSupports" :key="`image-${image.id}`">
                 <VerifyImageSupportModal :image-support="image" />
            </template>
        </template>

        <template v-if="isAdmin || isOwner">
             <!-- Add Document Modal -->
            <AddDocSupportModal
                :show="showingAddDocSupportModal"
                :app-request="appRequest"
                :history="latestHistoryForCurrentStatus"
                @close="showingAddDocSupportModal = false"
            />
            <!-- Add Image Modal -->
            <AddImageSupportModal
                :show="showingAddImageSupportModal"
                :app-request="appRequest"
                :history="latestHistoryForCurrentStatus"
                @close="showingAddImageSupportModal = false"
            />
        </template>

    </AuthenticatedLayout>
</template>
