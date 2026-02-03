<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select options...',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const inputRef = ref(null);

const filteredOptions = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        // Filter out already selected options so they don't appear in the dropdown again (optional style)
        // OR keep them and just mark as selected. 
        // For this design (tags), usually we hide selected ones or show them differently. 
        // Let's hide them from the list to make it clean like the screenshot request implies "Search Selection".
        const isSelected = props.modelValue.includes(option.value);
        const matchesSearch = option.label.toLowerCase().includes(query);
        return !isSelected && matchesSearch;
    });
});

const selectedOptions = computed(() => {
    return props.options.filter(option => props.modelValue.includes(option.value));
});

const selectOption = (option) => {
    emit('update:modelValue', [...props.modelValue, option.value]);
    searchQuery.value = ''; // Clear search after selection
    // Keep open for multiple selections or close? usually keep open for multiple.
    // isOpen.value = false; 
    // But refocus input
};

const removeOption = (value) => {
    emit('update:modelValue', props.modelValue.filter(v => v !== value));
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        if (inputRef.value) {
            inputRef.value.focus();
        }
        if (containerRef.value) {
            // focus input (optional, if we have ref to input)
            // Auto scroll to view
            setTimeout(() => {
                 containerRef.value.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            }, 100);
        }
    }
};

const handleContainerClick = () => {
    if (inputRef.value) {
        inputRef.value.focus();
    }
    if (!isOpen.value) {
        isOpen.value = true;
    }
};

const handleFocus = () => {
    isOpen.value = true;
    if (containerRef.value) {
        setTimeout(() => {
             containerRef.value.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }, 100);
    }
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Main Input Container -->
        <div
            @click="handleContainerClick"
            class="min-h-[38px] w-full bg-white border border-gray-300 rounded-md shadow-sm px-2 py-1 flex flex-wrap gap-1 items-center cursor-text focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500 transition-colors"
        >
            <!-- Tags -->
            <div
                v-for="option in selectedOptions"
                :key="option.value"
                class="bg-gray-100 text-gray-700 text-sm rounded-md px-2 py-0.5 flex items-center gap-1 border border-gray-200"
            >
                <span>{{ option.label }}</span>
                <button
                    @click.stop="removeOption(option.value)"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Search Input -->
            <input
                ref="inputRef"
                v-model="searchQuery"
                type="text"
                class="flex-1 min-w-[60px] border-none outline-none focus:ring-0 p-0 text-sm bg-transparent"
                :placeholder="selectedOptions.length === 0 ? placeholder : ''"
                @focus="handleFocus"
            />
            
            <!-- Chevron Icon -->
            <div class="ml-auto text-gray-400 cursor-pointer" @click.stop="toggleDropdown">
                 <svg class="w-4 h-4" :class="{'rotate-180': isOpen}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                 </svg>
            </div>
        </div>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
        >
            <div
                v-if="filteredOptions.length === 0"
                class="text-gray-500 cursor-default select-none relative py-2 px-3"
            >
                No options found.
            </div>

            <div
                v-for="option in filteredOptions"
                :key="option.value"
                @click="selectOption(option)"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50 text-gray-900"
            >
                <span class="block truncate">
                    {{ option.label }}
                </span>
            </div>
        </div>
    </div>
</template>
