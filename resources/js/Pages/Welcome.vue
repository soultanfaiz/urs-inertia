<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
// Pengecekan peran admin berdasarkan 'type' atau 'roles'
const isAdmin = computed(() => user.value?.type === 'admin' || user.value?.roles?.some(role => role.name === 'admin'));

// Placeholder for image asset. In a real Laravel app, you might construct this path differently
// or use a dynamic import if Vite is configured for it.
const placeholderImage = '/storage/placeholder.png';
</script>

<template>
    <Head title="Welcome" />
    <div class="font-sans bg-white text-gray-800 antialiased">
        <div class="container mx-auto px-6 py-4">
            <header class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <Link href="/" class="text-2xl font-bold text-gray-900">
                        SIPAIS
                    </Link>
                </div>

                <nav class="flex items-center space-x-4">
                    <div v-if="canLogin">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="isAdmin ? route('dashboard') : route('app-requests.index')"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-sm font-medium text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 mx-2"
                            >
                                Log In
                            </Link>

                            <!-- <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm mx-2"
                            >
                                Sign Up
                            </Link> -->
                        </template>
                    </div>
                </nav>
            </header>

            <main class="mt-12 lg:mt-24">
                <div
                    class="flex flex-col-reverse lg:flex-row gap-12 items-center"
                >
                    <div class="text-center lg:text-left">
                        <h1
                            class="text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900"
                        >
                            Sistem Informasi Permohonan Aplikasi Internal Samarinda
                        </h1>
                        <p class="mt-6 text-lg text-gray-600">
                            Ajukan permohonan pengembangan aplikasi dan pantau perkembangannya
                        </p>
                        <div
                            class="mt-8 flex flex-col sm:flex-row justify-center lg:justify-start space-y-4 sm:space-y-0 sm:space-x-4"
                        >
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                            >
                                Mulai Sekarang
                            </Link>
                        </div>
                    </div>

                    <div class="lg:w-1/2">
                        <img
                            :src="placeholderImage"
                            alt="DottedSign Illustration"
                            class="w-full h-auto"
                        />
                    </div>
                </div>
            </main>

            <footer class="mt-20 text-center text-gray-500 text-sm">
                <!-- Trusted by -->
                <!-- You can add company logos here -->
            </footer>
        </div>
    </div>
</template>
