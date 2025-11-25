<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
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
        <!-- Sidebar (Mobile - hidden by default, shown as overlay) -->
        <aside
            class="z-30 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white text-gray-800 transition-all h-screen overflow-y-auto fixed inset-y-0 left-0 shadow-xl transform"
            :class="{
                'translate-x-0 ease-out': showingSidebar,
                '-translate-x-full ease-in': !showingSidebar,
            }"
        >
            <div class="p-4 min-h-full">
                <div class="flex items-center mb-8">
                    <span class="text-xl font-bold text-gray-800">Admin Panel</span>
                    <button @click="showingSidebar = false" class="ml-auto text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
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
                                @click="showingSidebar = false"
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
                                @click="showingSidebar = false"
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
            <header class="flex min-h-[4rem] items-center justify-between border-b bg-white px-4 py-2 sm:px-6 lg:px-8">
                 <!-- Hamburger -->
                <button @click="showingSidebar = !showingSidebar" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Heading -->
                <div v-if="$slots.header" class="flex-1 mx-4">
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

            <!-- Overlay for mobile sidebar -->
            <div v-if="showingSidebar" @click="showingSidebar = false" class="fixed inset-0 z-20 bg-black opacity-50"></div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
