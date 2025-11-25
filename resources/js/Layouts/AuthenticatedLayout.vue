<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayoutDesktop from './AuthenticatedLayoutDesktop.vue';
import AuthenticatedLayoutMobile from './AuthenticatedLayoutMobile.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));

const isMobile = ref(false);
const mobileBreakpoint = 1024; // Tailwind's 'lg' breakpoint

const checkScreenSize = () => {
    isMobile.value = window.innerWidth < mobileBreakpoint;
};

onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize);
});
</script>

<template>
    <div v-if="isMobile">
        <AuthenticatedLayoutMobile :user="user" :isAdmin="isAdmin">
            <template #header v-if="$slots.header">
                <slot name="header" />
            </template>
            <slot />
        </AuthenticatedLayoutMobile>
    </div>
    <div v-else>
        <AuthenticatedLayoutDesktop :user="user" :isAdmin="isAdmin">
            <template #header v-if="$slots.header">
                <slot name="header" />
            </template>
            <slot />
        </AuthenticatedLayoutDesktop>
    </div>
</template>
