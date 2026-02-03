<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    notifications: {
        type: Object,
        required: true,
    },
});

const handleNotificationClick = (notification) => {
    // Logika ini sama dengan yang ada di dropdown notifikasi.
    // Jika notifikasi belum dibaca, kirim request POST untuk menandainya sebagai dibaca.
    // Controller akan menangani pengalihan ke link notifikasi.
    if (notification.is_read) {
        router.visit(notification.link);
    } else {
        router.post(route('notifications.markAsRead', notification.id));
    }
};
</script>

<template>
    <Head title="Semua Notifikasi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Semua Notifikasi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="notifications.data.length > 0" class="divide-y divide-gray-200">
                            <!-- Daftar Notifikasi -->
                            <div v-for="notification in notifications.data" :key="notification.id"
                                @click="handleNotificationClick(notification)"
                                :class="[
                                    'p-4 hover:bg-gray-100 cursor-pointer',
                                    { 'bg-blue-50 font-semibold': !notification.is_read }
                                ]">
                                <p class="font-bold">{{ notification.title }}</p>
                                <p class="text-sm text-gray-600">{{ notification.message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ notification.created_at_for_humans }}</p>
                            </div>
                        </div>
                        <div v-else>
                            <p>Tidak ada notifikasi untuk ditampilkan.</p>
                        </div>

                        <!-- Paginasi -->
                        <div v-if="notifications.links.length > 3" class="mt-6 flex justify-between items-center">
                             <div class="flex flex-wrap -mb-1">
                                <template v-for="(link, key) in notifications.links" :key="key">
                                    <div v-if="link.url === null"
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                                        v-html="link.label" />
                                    <Link v-else
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                                        :class="{ 'bg-blue-700 text-white': link.active }" :href="link.url"
                                        v-html="link.label" />
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
