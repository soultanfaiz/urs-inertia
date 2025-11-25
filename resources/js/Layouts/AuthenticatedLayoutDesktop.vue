<script setup>
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

defineProps({
    user: Object,
    isAdmin: Boolean,
});
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar (Desktop - always visible) -->
        <aside
            class="z-30 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white text-gray-800 transition-all h-screen overflow-y-auto relative"
        >
            <div class="p-4 min-h-full">
                <div class="flex items-center mb-8">
                    <span class="text-xl font-bold text-gray-800">Admin Panel</span>
                </div>

                <nav>
                    <ul class="space-y-2">
                        <li v-if="isAdmin">
                            <NavLink
                                :href="route('dashboard')"
                                :active="page.component.startsWith('Dashboard')"
                                class="flex items-center px-4 py-2 text-gray-700 rounded-md"
                                active-class="bg-gray-100 text-gray-900"
                                inactive-class="hover:bg-gray-100 hover:text-gray-900"
                            >
                                Dashboard
                            </NavLink>
                        </li>
                        <li>
                            <NavLink
                                :href="route('app-requests.index')"
                                :active="page.component.startsWith('AppRequest')"
                                class="flex items-center px-4 py-2 text-gray-700 rounded-md"
                                active-class="bg-gray-100 text-gray-900"
                                inactive-class="hover:bg-gray-100 hover:text-gray-900"
                            >
                                Permohonan
                            </NavLink>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="flex flex-1 flex-col">
            <!-- Top bar -->
            <header class="flex h-16 items-center justify-end border-b bg-white px-4 sm:px-6 lg:px-8">
                <!-- Page Heading -->
                <div v-if="$slots.header" class="flex-1">
                    <slot name="header" />
                </div>

                <!-- User menu dropdown -->
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
                            <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
