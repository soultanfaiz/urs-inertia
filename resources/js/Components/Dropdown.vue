<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    widthClasses: {
        type: String,
        default: null,
    },
    alignmentClasses: {
        type: String,
        default: null,
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    // Prioritaskan widthClasses jika ada, jika tidak, gunakan prop width.
    if (props.widthClasses) {
        return props.widthClasses;
    }
    return ({
        '48': 'w-48', // 12rem
        '64': 'w-64', // 16rem
        '80': 'w-80', // 20rem
        '96': 'w-96', // 24rem
        '128': 'w-[32rem]', // 32rem, menggunakan nilai arbitrer
    })[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.alignmentClasses) {
        return props.alignmentClasses;
    }

    // PERUBAHAN DI SINI:
    // Tambahkan prefix 'sm:' agar alignment ini tidak mengganggu centering di mobile
    if (props.align === 'left') {
        return 'sm:origin-top-left sm:start-0';
    } else if (props.align === 'right') {
        return 'sm:origin-top-right sm:end-0';
    } else {
        return 'sm:origin-top';
    }
});

const open = ref(false);
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-show="open" class="z-50 rounded-md shadow-lg" :class="[
                widthClass,
                alignmentClasses,

                /* --- UPDATE LOGIKA MOBILE --- */
                /* Ubah top-16 menjadi top-24 (sekitar 96px dari atas layar) */
                /* Tambahkan mt-4 untuk jarak ekstra aman dari tombol */
                'fixed top-28 mt-4 start-1/2 -translate-x-1/2',

                /* --- LOGIKA DESKTOP --- */
                /* Reset kembali posisi ke absolute dan nempel tombol di desktop */
                'sm:absolute sm:top-10 sm:mt-2 sm:start-auto sm:translate-x-0'
            ]" style="display: none" @click="open = false">
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
