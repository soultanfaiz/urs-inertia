<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

// Define the events this component can emit to its parent.
const emit = defineEmits([
    'open-add-doc-modal',
    'open-add-image-modal',
    'open-verify-doc-modal',
    'open-verify-image-modal',
]);

const user = computed(() => usePage().props.auth.user);
const enums = computed(() => usePage().props.enums); // Access enums from page props

const isAdmin = computed(() => user.value.roles && user.value.roles.includes('admin'));
const isOwner = computed(() => user.value.id === props.appRequest.user_id);

// --- Computed properties for data processing ---

// Flatten all documents and images from the histories
const allDocs = computed(() => props.appRequest.histories?.flatMap(h => h.doc_supports) ?? []);
const allImages = computed(() => props.appRequest.histories?.flatMap(h => h.image_supports) ?? []);

const hasContent = computed(() => allDocs.value.length > 0 || allImages.value.length > 0);

// Determine the display order of status groups based on the latest submission.
const statusOrder = computed(() => {
    const allItems = [...allDocs.value, ...allImages.value];
    const sortedByDate = allItems.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const statusLabels = sortedByDate.map(item => item.request_status); // Assuming request_status is the label string
    return [...new Set(statusLabels)]; // Return unique status labels in order
});

// Group documents and images by their status label for easy rendering.
const groupedDocs = computed(() => {
    return allDocs.value.reduce((acc, doc) => {
        const status = doc.request_status;
        if (!acc[status]) {
            acc[status] = [];
        }
        acc[status].push(doc);
        return acc;
    }, {});
});

const groupedImages = computed(() => {
    return allImages.value.reduce((acc, image) => {
        const status = image.request_status;
        if (!acc[status]) {
            acc[status] = [];
        }
        acc[status].push(image);
        return acc;
    }, {});
});

// Helper function to get the CSS class for a verification status.
const getVerificationStatusClass = (status) => {
    const verificationStatusEnum = enums.value.verificationStatus;
    switch (status) {
        case verificationStatusEnum.DISETUJUI: return 'bg-green-100 text-green-800';
        case verificationStatusEnum.DITOLAK: return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Dokumen Pendukung</h3>
            <div v-if="isAdmin || isOwner" class="flex space-x-2">
                <button @click="emit('open-add-doc-modal')" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Tambah Dokumen PDF">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    Tambah PDF
                </button>
                <button @click="emit('open-add-image-modal')" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 border-blue-600 rounded shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Unggah Banyak Gambar">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Upload Gambar
                </button>
            </div>
        </div>

        <div v-if="hasContent" class="space-y-6">
            <!-- Documents Section -->
            <div v-if="allDocs.length > 0" class="space-y-6">
                <template v-for="status in statusOrder" :key="`doc-status-${status}`">
                    <div v-if="groupedDocs[status]">
                        <h4 class="pb-2 mb-3 text-md font-semibold text-gray-600 border-b border-gray-200">{{ status }}</h4>
                        <ul class="space-y-3">
                            <li v-for="doc in groupedDocs[status]" :key="doc.id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 text-sm border-b border-gray-100 sm:gap-4 last:border-b-0 last:pb-0">
                                <!-- Left Side: File Name & Status -->
                                <div class="flex flex-wrap items-center flex-1 min-w-0 gap-2">
                                    <span class="text-gray-800 break-words" :title="doc.file_name">{{ doc.file_name }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full" :class="getVerificationStatusClass(doc.verification_status)">
                                        {{ doc.verification_status }}
                                    </span>
                                </div>
                                <!-- Right Side: Actions -->
                                <div class="flex items-center self-end flex-shrink-0 space-x-2 sm:self-center">
                                    <button v-if="isAdmin && doc.verification_status === enums.verificationStatus.MENUNGGU" @click="emit('open-verify-doc-modal', doc.id)" type="button" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50" title="Verifikasi Dokumen">
                                        Verifikasi
                                    </button>
                                    <a :href="route('app-request.doc-support.download', doc.id)" target="_blank" class="text-blue-600 hover:text-blue-800" title="Unduh Dokumen">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>

            <!-- Images Section -->
            <div v-if="allImages.length > 0" class="mt-8 space-y-6">
                <h3 class="pt-6 text-lg font-semibold text-gray-800 border-t border-gray-200">Gambar Pendukung</h3>
                <template v-for="status in statusOrder" :key="`img-status-${status}`">
                    <div v-if="groupedImages[status]">
                        <h4 class="pb-2 mb-3 text-md font-semibold text-gray-600 border-b border-gray-200">{{ status }}</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="image in groupedImages[status]" :key="image.id" class="flex flex-col">
                                <div class="relative group">
                                    <img :src="route('app-request.image-support.view', image.id)" :alt="image.image_name" class="object-cover w-full border border-gray-200 rounded-lg h-32">
                                    <span class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full" :class="getVerificationStatusClass(image.verification_status)">
                                        {{ image.verification_status }}
                                    </span>
                                    <div class="absolute inset-0 flex items-center justify-center transition-all duration-200 bg-black rounded-lg bg-opacity-0 group-hover:bg-opacity-30">
                                        <a :href="route('app-request.image-support.view', image.id)" target="_blank" class="p-2 transition-opacity duration-200 bg-white rounded-full opacity-0 group-hover:opacity-100" title="Lihat Gambar">
                                            <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-xs text-gray-600 truncate" :title="image.image_name">{{ image.image_name }}</span>
                                    <button v-if="isAdmin && image.verification_status === enums.verificationStatus.MENUNGGU" @click="emit('open-verify-image-modal', image.id)" type="button" class="ml-2 text-xs text-blue-600 hover:text-blue-800" title="Verifikasi Gambar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="py-4 text-center text-gray-500">
            <p>Belum ada dokumen pendukung yang diunggah.</p>
        </div>
    </div>
</template>
```

### Key Implementation Details:

1.  **Data Processing**:
    *   **`allDocs` & `allImages`**: These computed properties flatten the nested `histories` data into simple arrays of documents and images, making them easier to work with.
    *   **`statusOrder`**: This replicates the complex sorting from Blade. It merges all items, sorts them by creation date, extracts the status label, and then creates a unique, ordered array of statuses. This ensures the groups in the UI are always ordered from newest to oldest.
    *   **`groupedDocs` & `groupedImages`**: These properties transform the flat arrays into objects, where each key is a status label (e.g., "URS") and the value is an array of items belonging to that status. This makes the `v-for` loops in the template very efficient.

2.  **Event Emitting (`emit`)**:
    *   Instead of Alpine.js's `$dispatch`, this component uses Vue's `emit` function.
    *   Buttons for adding or verifying documents/images now call `emit` with a specific event name (e.g., `'open-add-doc-modal'`).
    *   The parent component (`Show.vue`) will need to listen for these events to open the correct modals.

3.  **Dynamic Classes and Props**:
    *   The `getVerificationStatusClass` function replaces the Blade `match` statement to provide the correct Tailwind CSS classes for the status badges.
    *   All data, like file names, routes, and IDs, are dynamically bound using Vue's `:` syntax.

4.  **Conditional Rendering**:
    *   `v-if="isAdmin || isOwner"` is used to show action buttons only to authorized users.
    *   `v-if="hasContent"` and the corresponding `v-else` handle showing either the lists of documents/images or the "empty state" message.

To make this fully functional, you will need to update `Show.vue` to listen for the new events. For example:

```vue
// In d:\Laravel\urs-inertia\resources\js\Pages\AppRequest\Show.vue

<SupportingDocumentsCard
    :app-request="appRequest"
    @open-add-doc-modal="showingAddDocSupportModal = true"
    @open-add-image-modal="showingAddImageSupportModal = true"
    @open-verify-doc-modal="(docId) => openDocVerifyModal(docId)"
    @open-verify-image-modal="(imageId) => openImageVerifyModal(imageId)"
/>
```

You would then define the `openDocVerifyModal` and `openImageVerifyModal` methods in `Show.vue` to handle opening the specific verification modals for each item.
