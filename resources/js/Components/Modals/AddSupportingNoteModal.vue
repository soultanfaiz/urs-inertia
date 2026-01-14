<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { watch, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appRequest: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    note: '',
    image: null,
});

const editor = useEditor({
    extensions: [
        StarterKit,
    ],
    content: '',
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[150px] p-4',
        },
    },
    onUpdate: ({ editor }) => {
        form.note = editor.getHTML();
    },
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
        if (editor.value) {
            editor.value.commands.setContent('');
        }
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});

const submit = () => {
    // Pastikan route ini ada di web.php
    form.post(route('supporting-note.store', props.appRequest.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" focusable>
        <div class="p-6">
            <form @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    Tambahkan Catatan
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Tambahkan catatan atau informasi tambahan untuk permohonan ini.
                </p>
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Judul Catatan</label>
                    <input type="text" v-model="form.title" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ringkasan singkat...">
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Isi Catatan</label>

                    <div v-if="editor" class="border border-gray-300 rounded-md shadow-sm overflow-hidden focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                        <!-- Simple Toolbar -->
                        <div class="bg-gray-50 border-b border-gray-200 p-2 flex gap-2">
                            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-gray-200 text-black': editor.isActive('bold'), 'text-gray-600': !editor.isActive('bold') }" class="p-1.5 rounded hover:bg-gray-200 font-bold text-xs uppercase">B</button>
                            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-gray-200 text-black': editor.isActive('italic'), 'text-gray-600': !editor.isActive('italic') }" class="p-1.5 rounded hover:bg-gray-200 italic text-xs font-serif">I</button>
                            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-gray-200 text-black': editor.isActive('bulletList'), 'text-gray-600': !editor.isActive('bulletList') }" class="p-1.5 rounded hover:bg-gray-200 text-xs">List</button>
                        </div>
                        <editor-content :editor="editor" />
                    </div>
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>

                <!-- <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Pendukung (Opsional)</label>
                    <input type="file" @input="form.image = $event.target.files[0]" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF. Maks: 5MB.</p>
                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">{{ form.progress.percentage }}%</progress>
                    <InputError :message="form.errors.image" class="mt-2" />
                </div> -->

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="submit">
                        Simpan Catatan
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
