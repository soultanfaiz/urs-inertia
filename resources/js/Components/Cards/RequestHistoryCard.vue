<script setup>
import { computed } from 'vue';
// This component will render each individual history entry.
// We will assume it will be created in a subsequent step.
import HistoryItem from '@/Components/HistoryItem.vue';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

// A computed property to check if there are histories, for a cleaner template.
const hasHistories = computed(() => {
    return props.appRequest.histories && props.appRequest.histories.length > 0;
});
</script>

<template>
    <div v-if="hasHistories" class="p-6 bg-white rounded-lg shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Riwayat Proses</h3>
        <ul role="list" class="space-y-6">
            <HistoryItem v-for="(history, index) in appRequest.histories" :key="history.id" :history="history"
                :is-last="index === appRequest.histories.length - 1" />
        </ul>
    </div>
</template>
