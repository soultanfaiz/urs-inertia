<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { format, formatDistanceToNow } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    history: {
        type: Object,
        required: true,
    },
    isLast: {
        type: Boolean,
        default: false,
    },
});

const enums = computed(() => usePage().props.enums);

const statusInfo = computed(() => {
    const { status, type } = props.history;
    const { verificationStatus, requestStatus } = enums.value;

    // Handle Verification Status changes
    if (type === 'verification') {
        if (status === verificationStatus.DISETUJUI) return { color: 'green', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' };
        if (status === verificationStatus.DITOLAK) return { color: 'red', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' };
        if (status === verificationStatus.MENUNGGU) return { color: 'gray', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' };
    }

    // Handle Request Status (progress) changes
    if (type === 'status_change') {
        switch (status) {
            case requestStatus.PERMOHONAN: return { color: 'blue', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' };
            case requestStatus.URS: return { color: 'cyan', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' };
            case requestStatus.PENGEMBANGAN: return { color: 'yellow', icon: 'M10 20l4-16m4 4l-4 4-4-4-4 4' };
            case requestStatus.UAT: return { color: 'purple', icon: 'M17 20h5v-2h-5v2zM3 20h5v-2H3v2zM4 4h5v2H4V4zM15 4h5v2h-5V4z' };
            case requestStatus.SELESAI: return { color: 'green', icon: 'M5 13l4 4L19 7' };
        }
    }

    // Default
    return { color: 'gray', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.79 4 4s-1.79 4-4 4c-1.742 0-3.223-.835-3.772-2H2V9h6.228zM12 15c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' };
});

const styleClasses = computed(() => ({
    bgColor: `bg-${statusInfo.value.color}-100`,
    textColor: `text-${statusInfo.value.color}-800`,
    iconColor: `text-${statusInfo.value.color}-500`,
}));

const getDocStatusClass = (status) => {
    const { verificationStatus } = enums.value;
    switch (status) {
        case verificationStatus.DISETUJUI: return 'bg-green-100 text-green-800';
        case verificationStatus.DITOLAK: return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formattedDate = computed(() => {
    if (!props.history.created_at) return '';
    const date = new Date(props.history.created_at);
    const relative = formatDistanceToNow(date, { addSuffix: true, locale: id });
    const absolute = format(date, 'd MMM yyyy, HH:mm', { locale: id });
    return `${relative} (${absolute})`;
});

const sortedDocs = computed(() => {
    return [...(props.history.doc_supports || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const sortedImages = computed(() => {
    return [...(props.history.image_supports || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

</script>

<template>
    <li class="relative flex gap-x-4">
        <!-- Vertical line, except for the last item -->
        <div v-if="!isLast" class="absolute left-0 top-4 -bottom-4 flex w-6 justify-center">
            <div class="w-px bg-gray-200"></div>
        </div>

        <!-- Status Icon -->
        <div class="relative flex h-6 w-6 flex-none items-center justify-center rounded-full" :class="styleClasses.bgColor">
            <svg class="h-4 w-4" :class="styleClasses.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusInfo.icon"></path>
            </svg>
        </div>

        <!-- History Content -->
        <div class="min-w-0 flex-auto py-0.5 text-sm leading-5 text-gray-500">
            <p v-if="history.status === enums.requestStatus.PERMOHONAN">
                <span class="font-medium text-gray-900">{{ history.user.name }}</span>
                mengajukan
                <span class="font-semibold" :class="styleClasses.textColor">Permohonan</span>.
            </p>
            <p v-else>
                <span class="font-medium text-gray-900">{{ history.user.name }}</span>
                {{ history.type === 'verification' ? 'memverifikasi permohonan menjadi' : 'mengubah status progres menjadi' }}
                <span class="font-semibold" :class="styleClasses.textColor">{{ history.status_label }}</span>.
            </p>

            <div v-if="history.reason" class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                <p class="text-gray-700 whitespace-pre-wrap">{{ history.reason }}</p>
            </div>

            <!-- Supporting Documents List -->
            <div v-if="sortedDocs.length > 0" class="mt-2 space-y-2">
                <p class="text-xs font-medium text-gray-500">Dokumen Pendukung:</p>
                <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                    <li v-for="doc in sortedDocs" :key="doc.id" class="px-3 py-2 flex items-center justify-between text-sm">
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center">
                                <span class="text-gray-800 break-words block" :title="doc.file_name">
                                    {{ doc.file_name }}
                                </span>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getDocStatusClass(doc.verification_status)">
                                    {{ doc.verification_status_label }}
                                </span>
                            </div>
                            <div v-if="doc.verification_status === enums.verificationStatus.DITOLAK && doc.reason" class="text-xs text-red-700 bg-red-50 p-2 rounded-md border border-red-200">
                                <strong>Alasan:</strong> {{ doc.reason }}
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <a :href="route('app-request.doc-support.download', doc.id)" target="_blank" class="text-blue-600 hover:text-blue-800" title="Unduh Dokumen">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Supporting Images List -->
            <div v-if="sortedImages.length > 0" class="mt-2 space-y-2">
                <p class="text-xs font-medium text-gray-500">Gambar Pendukung:</p>
                <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                    <li v-for="image in sortedImages" :key="image.id" class="px-3 py-2 flex items-center justify-between text-sm">
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-12 h-12 mr-3">
                                    <img :src="route('app-request.image-support.view', image.id)" :alt="image.image_name" class="w-12 h-12 object-cover rounded border border-gray-200">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-gray-800 break-words block" :title="image.image_name">
                                        {{ image.image_name }}
                                    </span>
                                </div>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getDocStatusClass(image.verification_status)">
                                    {{ image.verification_status_label }}
                                </span>
                            </div>
                            <div v-if="image.verification_status === enums.verificationStatus.DITOLAK && image.reason" class="text-xs text-red-700 bg-red-50 p-2 rounded-md border border-red-200 ml-15">
                                <strong>Alasan:</strong> {{ image.reason }}
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <a :href="route('app-request.image-support.view', image.id)" target="_blank" class="text-blue-600 hover:text-blue-800" title="Lihat Gambar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>

            <time :datetime="history.created_at" class="mt-1 text-xs text-gray-400">
                {{ formattedDate }}
            </time>
        </div>
    </li>
</template>

<style scoped>
/* Add margin-left to align with the image thumbnail */
.ml-15 {
    margin-left: 3.75rem; /* 12 (w-12) + 3 (mr-3) = 15 -> 15 * 0.25rem = 3.75rem */
}
</style>
