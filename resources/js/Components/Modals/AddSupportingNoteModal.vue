<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { marked } from 'marked';
import { watch, onBeforeUnmount, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appRequest: {
        type: Object,
        required: true,
    },
    noteToEdit: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

// Tab state
const activeTab = ref('manual'); // 'manual' or 'generated'

// Generated text state
const generatedNote = ref('');
const isGenerating = ref(false);
const generateError = ref('');
// generateContext removed, replaced by aiEditor content

const form = useForm({
    title: '',
    note: '',
    generated_note: '',
    image: null,
    _method: 'POST',
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

const aiEditor = useEditor({
    extensions: [
        StarterKit,
    ],
    content: '',
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[150px] p-4',
        },
    },
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.clearErrors();
        form.reset();
        activeTab.value = 'manual';
        generateError.value = '';
        
        if (editor.value) {
            editor.value.commands.setContent('');
        }
        if (aiEditor.value) {
            aiEditor.value.commands.setContent('');
        }
        
        if (props.noteToEdit) {
            // Edit Mode
            form.title = props.noteToEdit.title;
            form.note = props.noteToEdit.reason; // Note content from DB is 'reason' in SupportingNotesCard but mapping? 
            // Wait, SupportingNotesCard mapped it: reason: note.note. 
            // But here raw data passed from Show.vue -> SupportingNotesCard -> note object.
            // Let's check SupportingNotesCard emits 'note' object from 'notes' array which has 'reason' mapped from 'note.note'.
            // So noteToEdit.reason is the content.
            // But wait, SupportingNotesCard emits the `note` object from its `notes` array.
            // In SupportingNotesCard:
            // notes.value = (newNotes || []).map(note => ({ ..., reason: note.note, ... }))
            // So the object passed to open-edit-note-modal has `reason` property, NOT `note`.
            // So here form.note = props.noteToEdit.reason.
            
            // However, noteToEdit also has 'generated_note' property? 
            // SupportingNotesCard map does NOT include generated_note.
            // I need to update SupportingNotesCard to include generated_note in the mapped object!
            
            form.note = props.noteToEdit.reason;
            // form.generated_note = props.noteToEdit.generated_note; // Need to ensure it's passed
            form._method = 'PUT';
            
            if (editor.value) {
                editor.value.commands.setContent(props.noteToEdit.reason);
            }
            
            // If there is a generated note associated, populate it
            if (props.noteToEdit.generated_note) {
                 generatedNote.value = props.noteToEdit.generated_note;
                 form.generated_note = props.noteToEdit.generated_note;
                 // Set content of aiEditor to generated note if exists so user can edit it
                 // Convert plain text to HTML for display
                 if (aiEditor.value) {
                     const htmlContent = props.noteToEdit.generated_note
                        .split('\n\n')
                        .map(para => `<p>${para.replace(/\n/g, '<br>')}</p>`)
                        .join('');
                     aiEditor.value.commands.setContent(htmlContent);
                 }
            } else if (aiEditor.value) {
                aiEditor.value.commands.setContent('');
            }
        } else {
            // Add Mode
            form._method = 'POST';
            generatedNote.value = '';
            if (editor.value) {
                editor.value.commands.setContent('');
            }
        }
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
    if (aiEditor.value) {
        aiEditor.value.destroy();
    }
});

// Generate note using AI
const generateNote = async () => {
    if (!form.title) {
        generateError.value = 'Silakan isi judul catatan terlebih dahulu.';
        return;
    }

    isGenerating.value = true;
    generateError.value = '';

    try {
        // Use content from aiEditor as source/context
        let sourceContent = '';
        if (aiEditor.value) {
             // We use getText() to get raw text for the prompt, or getHTML() if we want to preserve structure
             // Using getText() is usually safer for API prompts unless we want to parse HTML
             // But stripping tags from getHTML might be better to preserve line breaks as newlines?
             // Tiptap getText() joins blocks with line breaks usually.
             sourceContent = aiEditor.value.getText();
        }

        const response = await axios.post(route('ai.generate-note', props.appRequest.id), {
            title: form.title,
            context: '', // We use existing_note for the draft content
            existing_note: sourceContent, 
        });

        // ...

        if (response.data.success) {
            form.generated_note = response.data.generated_note;
            generatedNote.value = response.data.generated_note; // Keep ref for internal use if needed
            
            // Populate aiEditor with result
             if (aiEditor.value) {
                // Parse Markdown to HTML using marked
                const htmlContent = marked.parse(response.data.generated_note);
                aiEditor.value.commands.setContent(htmlContent);
            }
        } else {
            generateError.value = response.data.message || 'Gagal menghasilkan teks.';
        }
    } catch (error) {
        generateError.value = error.response?.data?.message || 'Terjadi kesalahan saat menghubungi server.';
    } finally {
        isGenerating.value = false;
    }
};

// Use generated text as note
const useGeneratedText = () => {
    if (aiEditor.value && editor.value) {
        const content = aiEditor.value.getHTML();
        editor.value.commands.setContent(content);
        form.note = content;
        // form.generated_note is already updated during generation
        activeTab.value = 'manual';
    }
};

const submit = () => {
    if (props.noteToEdit) {
        form.post(route('supporting-note.update', props.noteToEdit.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('supporting-note.store', props.appRequest.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" focusable max-width="2xl">
        <div class="p-6">
            <form @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ noteToEdit ? 'Edit Catatan' : 'Tambahkan Catatan' }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Tambahkan catatan atau informasi tambahan untuk permohonan ini.
                </p>

                <!-- Title Field (always visible) -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Judul Catatan</label>
                    <input type="text" v-model="form.title" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ringkasan singkat...">
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <!-- Tabs -->
                <div class="mt-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                            <button
                                type="button"
                                @click="activeTab = 'manual'"
                                :class="[
                                    activeTab === 'manual'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Isi Catatan
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'generated'"
                                :class="[
                                    activeTab === 'generated'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-1'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                AI Generate
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Content: Manual -->
                <div v-show="activeTab === 'manual'" class="mt-4">
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

                <!-- Tab Content: Generated -->
                <div v-show="activeTab === 'generated'" class="mt-4">
                    <div class="space-y-4">
                        <!-- Context/Draft Editor -->
                        <div v-if="aiEditor">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Draft Notulen / Konteks
                            </label>
                            <div class="border border-gray-300 rounded-md shadow-sm overflow-hidden focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                                <!-- Simple Toolbar -->
                                <div class="bg-gray-50 border-b border-gray-200 p-2 flex gap-2">
                                    <button type="button" @click="aiEditor.chain().focus().toggleBold().run()" :class="{ 'bg-gray-200 text-black': aiEditor.isActive('bold'), 'text-gray-600': !aiEditor.isActive('bold') }" class="p-1.5 rounded hover:bg-gray-200 font-bold text-xs uppercase">B</button>
                                    <button type="button" @click="aiEditor.chain().focus().toggleBullets().run()" :class="{ 'bg-gray-200 text-black': aiEditor.isActive('bulletList'), 'text-gray-600': !aiEditor.isActive('bulletList') }" class="p-1.5 rounded hover:bg-gray-200 text-xs">List</button>
                                </div>
                                <editor-content :editor="aiEditor" />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Tulis poin-poin rapat di sini, lalu klik 'Generate' untuk merapikan dengan AI.</p>
                        </div>

                        <!-- Generate Button -->
                        <div>
                            <button
                                type="button"
                                @click="generateNote"
                                :disabled="isGenerating || !form.title"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isGenerating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ isGenerating ? 'Generating...' : 'Generate dengan AI' }}
                            </button>
                            <p v-if="!form.title" class="mt-1 text-xs text-amber-600">
                                * Isi judul catatan terlebih dahulu
                            </p>
                        </div>

                        <!-- Error Message -->
                        <div v-if="generateError" class="p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-600">{{ generateError }}</p>
                        </div>

                        <!-- Result Actions -->
                        <div v-if="aiEditor && !aiEditor.isEmpty">
                           <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-500 italic">Hasil generate akan muncul di editor di atas. Silakan edit jika perlu.</span>
                                <button
                                    type="button"
                                    @click="useGeneratedText"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Gunakan Teks Ini
                                </button>
                           </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
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
