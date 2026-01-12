<script setup>
import { computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import { usePage, Link, router } from '@inertiajs/vue3';

// Ambil notifikasi dari props global yang dibagikan oleh HandleInertiaRequests
const notifications = computed(() => usePage().props.auth.notifications || []);
const notifications_count = computed(() => usePage().props.auth.notifications_count || 0);

const handleNotificationClick = (notification) => {
    // Gunakan router.post untuk menandai notifikasi sebagai telah dibaca.
    // Controller akan menangani redirect ke link notifikasi.
    if (notification.is_read) {
        // Jika sudah dibaca, langsung kunjungi tautannya.
        router.visit(notification.link);
    } else {
        // Jika belum dibaca, panggil rute untuk menandai sebagai telah dibaca.
        // Controller akan menangani redirect ke link notifikasi setelahnya.
        router.post(route('notifications.markAsRead', notification.id));
    }
};
</script>

<template>
    <div class="relative">
        <Dropdown align="right" width="48" widthClasses="w-[95vw] max-w-sm sm:w-96">
            <template #trigger>
                <button
                    class="relative rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none">
                    <!-- Notification Bell Icon -->
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 00-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Notification Badge -->
                    <span v-if="notifications_count > 0"
                        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                        {{ notifications_count }}
                    </span>
                </button>
            </template>

            <template #content>
                <div class="p-2">
                    <div class="flex items-center justify-between px-2 py-1">
                        <span class="font-bold text-gray-800">Notifikasi</span>
                        <Link
                            v-if="notifications_count > 0"
                            :href="route('notifications.markAllAsRead')"
                            method="post"
                            as="button"
                            class="text-xs text-blue-600 hover:text-blue-800 hover:underline"
                        >
                            Tandai sudah baca semua
                        </Link>
                    </div>
                    <div v-if="notifications.length > 0" class="divide-y divide-gray-100">
                        <a v-for="notification in notifications" :key="notification.id" :href="notification.link"
                            @click.prevent="handleNotificationClick(notification)" :class="[
                                'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 whitespace-normal',
                                { 'bg-blue-50': !notification.is_read, 'font-semibold': !notification.is_read, 'cursor-pointer': true },
                            ]">
                            <p class="font-bold">{{ notification.title }}</p>
                            <p class="text-xs text-gray-500">{{ notification.message }}</p>
                        </a>
                    </div>
                    <div v-else class="px-4 py-2 text-sm text-gray-500">
                        Tidak ada notifikasi baru.
                    </div>
                    <div class="border-t border-gray-200 mt-1">
                        <Link :href="route('notifications.index')"
                            class="block w-full text-center px-4 py-2 text-sm font-medium text-blue-600 hover:bg-gray-100" as="a">
                            Lihat semua notifikasi
                        </Link>
                    </div>
                </div>
            </template>
        </Dropdown>
    </div>
</template>
