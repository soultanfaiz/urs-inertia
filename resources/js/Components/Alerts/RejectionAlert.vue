<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

// Get enums globally from shared props
const enums = computed(() => usePage().props.enums);

const isRejected = computed(() => {
    // Use optional chaining for safety during initial render
    return props.appRequest.verification_status === enums.value?.verificationStatus?.DITOLAK;
});
</script>

<template>
    <div v-if="isRejected" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow" role="alert">
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 110 2 1 1 0 010-2z"/></svg>
            </div>
            <div>
                <p class="font-bold">Permohonan Ditolak</p>
                <p class="text-sm">Permohonan ini telah ditolak dan tidak dapat dilanjutkan.</p>
            </div>
        </div>
    </div>
</template>
