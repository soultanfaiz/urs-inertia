<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

defineProps({
    user: Object,
    isAdmin: Boolean,
});

const showingSidebar = ref(false);
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <!--
            Note: Class 'md:translate-x-0 md:static md:inset-auto' ditambahkan agar
            sidebar otomatis muncul (tidak hidden) saat layar besar (Desktop).
        -->
        <aside
            class="fixed inset-y-0 left-0 z-30 flex w-64 shrink-0 transform flex-col border-r border-gray-200 bg-white text-gray-800 transition-transform duration-300 ease-in-out shadow-xl md:static md:translate-x-0 md:shadow-none"
            :class="{
                'translate-x-0': showingSidebar,
                '-translate-x-full': !showingSidebar,
            }"
        >
            <!-- PERBAIKAN 1: Hapus 'p-4', ganti dengan flex layout dan py-4 saja -->
            <div class="min-h-full flex flex-col py-4">

                <!-- PERBAIKAN 2: Tambahkan px-6 di header agar judul tidak mepet pinggir -->
                <div class="flex items-center justify-between px-6 mb-6">
                    <span class="text-xl font-bold text-gray-800">{{ isAdmin ? 'Admin Panel' : 'User Panel' }}</span>

                    <!-- Tombol Close (Hanya muncul di Mobile) -->
                    <button @click="showingSidebar = false" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto">
                    <ul class="space-y-1">
                        <li v-if="isAdmin">
                            <!-- PERBAIKAN 3: NavLink Full Width -->
                            <NavLink
                                :href="route('dashboard')"
                                :active="page.component.startsWith('Dashboard')"
                                class="flex w-full items-center px-6 py-3 text-left transition-colors duration-200"
                                active-class="bg-blue-50 border-l-4 border-blue-500 text-blue-700"
                                inactive-class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent"
                                @click="showingSidebar = false"
                            >
                                Dashboard
                            </NavLink>
                        </li>
                        <li>
                            <NavLink
                                :href="route('app-requests.index')"
                                :active="page.component.startsWith('AppRequest')"
                                class="flex w-full items-center px-6 py-3 text-left transition-colors duration-200"
                                active-class="bg-blue-50 border-l-4 border-blue-500 text-blue-700"
                                inactive-class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent"
                                @click="showingSidebar = false"
                            >
                                Permohonan
                            </NavLink>
                        </li>
                        <li v-if="isAdmin">
                            <NavLink
                                :href="route('development-activities.index')"
                                :active="page.component.startsWith('DevelopmentActivity')"
                                class="flex w-full items-center px-6 py-3 text-left transition-colors duration-200"
                                active-class="bg-blue-50 border-l-4 border-blue-500 text-blue-700"
                                inactive-class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent"
                                @click="showingSidebar = false"
                            >
                                Task
                            </NavLink>
                        </li>
                        <li v-if="isAdmin">
                             <div class="px-6 pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Manajemen
                            </div>
                            <NavLink
                                :href="route('management.pics.index')"
                                :active="page.component.startsWith('Management/Pic')"
                                class="flex w-full items-center px-6 py-3 text-left transition-colors duration-200"
                                active-class="bg-blue-50 border-l-4 border-blue-500 text-blue-700"
                                inactive-class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent"
                                @click="showingSidebar = false"
                            >
                                Manajemen PIC
                            </NavLink>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <!--
                PERBAIKAN HEADER:
                - Ganti 'h-16' menjadi 'min-h-[4rem]' agar tinggi bisa menyesuaikan isi.
                - Ganti 'py-2' menjadi 'py-4' untuk menambah jarak atas bawah.
            -->
            <header class="flex min-h-[4rem] items-center justify-between border-b bg-white px-4 py-4 sm:px-6 lg:px-8">
                 <!-- Hamburger (Hanya muncul di Mobile karena md:hidden) -->
                <button @click="showingSidebar = !showingSidebar" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 focus:outline-none md:hidden">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Heading (Jika ada slot header) -->
                <div v-if="$slots.header" class="flex-1 mx-4">
                    <slot name="header" />
                </div>

                <!-- User menu dropdown -->
                <div class="flex items-center space-x-2 ml-auto">
                    <NotificationDropdown />
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center rounded-md px-3 py-2 text-left text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none">
                                    <div>{{ user.name }}</div>
                                    <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <!-- <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink> -->
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Overlay for mobile sidebar -->
            <div
                v-if="showingSidebar"
                @click="showingSidebar = false"
                class="fixed inset-0 z-20 bg-black opacity-50 md:hidden transition-opacity"
            ></div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-100">
                <slot />
            </main>
        </div>
    </div>
</template>
