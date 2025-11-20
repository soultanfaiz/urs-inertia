<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => {
    // Asumsikan role ada di dalam user.roles sebagai array of string
    // atau sesuaikan dengan struktur data yang Anda kirim dari backend.
    return user.value.roles && user.value.roles.includes('admin');
});

const showingSidebar = ref(false);
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            class="relative z-30 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white text-gray-800 transition-all"
            :class="{
                'fixed inset-y-0': showingSidebar,
                'hidden lg:flex': !showingSidebar,
            }"
        >
            <div class="flex-1">
                <!-- Logo -->
                <div class="flex h-16 items-center justify-center border-b">
                    <Link :href="route('dashboard')">
                        <ApplicationLogo class="block h-9 w-auto" />
                    </Link>
                </div>

                <!-- Navigation Links -->
                <nav class="mt-5 flex-1 space-y-1 px-2">
                    <!-- Menu Dashboard (hanya untuk admin) -->
                    <NavLink
                        v-if="isAdmin"
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        Dashboard
                    </NavLink>
                    <!-- Menu Permohonan -->
                    <!-- Ganti `route('app-requests.index')` dengan nama route yang sesuai -->
                    <NavLink
                        :href="route('app-requests.index')"
                        :active="route().current('app-requests.index')"
                    >
                        Permohonan
                    </NavLink>
                </nav>
            </div>
            <!-- User Menu -->
            <div class="border-t p-4 absolute bottom-0 w-full">
                <div class="relative">
                    <Dropdown align="top" width="48">
                        <template #trigger>
                            <button class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none">
                                <div>{{ user.name }}</div>
                                <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </aside>

        <div class="flex flex-1 flex-col">
            <!-- Top bar -->
            <header class="flex h-16 items-center justify-between border-b bg-white px-4 sm:px-6 lg:px-8">
                 <!-- Hamburger -->
                <button @click="showingSidebar = !showingSidebar" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 focus:outline-none lg:hidden">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Heading -->
                <div v-if="$slots.header" class="flex-1">
                    <slot name="header" />
                </div>
            </header>

            <!-- Overlay for mobile sidebar -->
            <div v-if="showingSidebar" @click="showingSidebar = false" class="fixed inset-0 z-20 bg-black opacity-50 lg:hidden"></div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
