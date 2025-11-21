<script setup>
defineProps({
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">DialogCard</h3>

        <div class="flex items-center">
            <img class="object-cover w-12 h-12 rounded-full" :src="user.avatar_url" :alt="user.name">
            <div class="ml-4">
                <p class="font-semibold text-gray-900">{{ user.name }}</p>
                <span class="text-sm text-gray-500">{{ user.email }}</span>
            </div>
        </div>
    </div>
</template>
