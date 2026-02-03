<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['page-change']);

const links = computed(() => {
    const range = [];
    const delta = 2; // Number of pages to show before and after current page
    
    // Always add 'Previous'
    range.push({
        label: '&laquo; Previous',
        page: props.currentPage - 1,
        enabled: props.currentPage > 1,
        active: false
    });

    // Page numbers
    for (let i = 1; i <= props.totalPages; i++) {
        // Show first, last, and pages around current
        if (
            i === 1 || 
            i === props.totalPages || 
            (i >= props.currentPage - delta && i <= props.currentPage + delta)
        ) {
            range.push({
                label: i.toString(),
                page: i,
                enabled: true,
                active: i === props.currentPage
            });
        } else if (
            (i === props.currentPage - delta - 1 && i > 1) || 
            (i === props.currentPage + delta + 1 && i < props.totalPages)
        ) {
             range.push({
                label: '...',
                page: null,
                enabled: false, // disabled click
                active: false
            });
        }
    }

    // Always add 'Next'
    range.push({
        label: 'Next &raquo;',
        page: props.currentPage + 1,
        enabled: props.currentPage < props.totalPages,
        active: false
    });

    return range;
});

const handleClick = (link) => {
    if (link.enabled && link.page) {
        emit('page-change', link.page);
    }
};
</script>

<template>
    <div v-if="totalPages > 1">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, index) in links" :key="index">
                <div
                    v-if="!link.enabled && link.label === '...'"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                    v-html="link.label"
                />
                 <button
                    v-else
                    type="button"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded focus:border-indigo-500"
                    :class="{ 
                        'bg-blue-600 text-white hover:bg-blue-700 focus:text-white': link.active,
                        'bg-white text-gray-700 hover:bg-gray-50 focus:text-indigo-500': !link.active && link.enabled,
                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.enabled
                    }"
                    :disabled="!link.enabled"
                    @click="handleClick(link)"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
