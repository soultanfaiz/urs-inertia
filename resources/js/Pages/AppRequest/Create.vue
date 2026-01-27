<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    instansi: {
        type: Array,
        required: true,
    },
});

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));

const form = useForm({
    title: '',
    description: '',
    // Jika bukan admin, isi otomatis dengan instansi user. Jika admin, kosongkan agar bisa memilih.
    instansi: isAdmin.value ? '' : user.value.instansi,
    file_pdf: null,
});

const submit = () => {
    form.post(route('app-requests.store'), {
        onError: () => {
            // Optional: Handle specific actions on error, like resetting the file input
            if (form.errors.file_pdf) {
                form.reset('file_pdf');
            }
        },
    });
};
</script>

<template>
    <Head title="Ajukan Permohonan Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ajukan Permohonan Baru
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 md:p-8">
                        <form @submit.prevent="submit">
                            <!-- Judul -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700">Judul Permohonan</label>
                                <input type="text" name="title" id="title" v-model="form.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.title }" required>
                                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <!-- Deskripsi -->
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="description" id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.description }" required></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <!-- Pilihan Instansi -->
                            <div class="mb-4">
                                <label for="instansi" class="block text-sm font-medium text-gray-700">Instansi</label>
                                <select
                                    id="instansi"
                                    name="instansi"
                                    v-model="form.instansi"
                                    class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm"
                                    :class="{
                                        'border-red-500': form.errors.instansi,
                                        'bg-gray-100 text-gray-500 cursor-not-allowed': !isAdmin
                                    }"
                                    :disabled="!isAdmin"
                                    required
                                >
                                    <option value="" disabled>Pilih Instansi</option>
                                    <option v-for="item in instansi" :key="item.value" :value="item.value" :disabled="!isAdmin && item.value !== user.instansi">
                                        {{ item.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.instansi" class="mt-2 text-sm text-red-600">{{ form.errors.instansi }}</p>
                            </div>

                            <!-- Upload File PDF -->
                            <div class="mb-6">
                                <label for="file_pdf" class="block text-sm font-medium text-gray-700">
                                    Upload File (PDF) <span v-if="isAdmin" class="text-gray-500 font-normal">(Opsional)</span>
                                </label>
                                <input
                                    type="file"
                                    name="file_pdf"
                                    id="file_pdf"
                                    @input="form.file_pdf = $event.target.files[0]"
                                    accept=".pdf"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                    :class="{ 'rounded-md ring-1 ring-red-500': form.errors.file_pdf }"
                                />
                                <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">
                                    {{ form.progress.percentage }}%
                                </progress>
                                <p class="mt-1 text-xs text-gray-500">Maksimal ukuran file: 2MB.</p>
                                <p v-if="form.errors.file_pdf" class="mt-2 text-sm text-red-600">{{ form.errors.file_pdf }}</p>
                            </div>

                            <!-- Tombol Submit -->
                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-8 py-3 text-base font-medium text-white hover:bg-blue-700" :disabled="form.processing">
                                    <span v-if="form.processing">Mengirim...</span>
                                    <span v-else>Kirim Permohonan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
