<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    pics: Array,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    id: null,
    name: '',
    position: '',
});

const openModal = (pic = null) => {
    isModalOpen.value = true;
    if (pic) {
        isEditing.value = true;
        form.id = pic.id;
        form.name = pic.name;
        form.position = pic.position;
    } else {
        isEditing.value = false;
        form.reset();
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const savePic = () => {
    if (isEditing.value) {
        form.patch(route('management.pics.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('management.pics.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (pic) => {
    itemToDelete.value = pic;
    deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    deleteModalOpen.value = false;
    itemToDelete.value = null;
};

const deletePic = () => {
    if (itemToDelete.value) {
        useForm({}).delete(route('management.pics.destroy', itemToDelete.value.id), {
            onSuccess: () => closeDeleteModal(),
        });
    }
};
</script>

<template>
    <Head title="Manajemen PIC" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen PIC</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Daftar PIC</h3>
                        <PrimaryButton @click="openModal()">+ Tambah PIC</PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pic in pics" :key="pic.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ pic.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ pic.position }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button @click="openModal(pic)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button @click="confirmDelete(pic)" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="pics.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data PIC.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ isEditing ? 'Edit PIC' : 'Tambah PIC Baru' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Nama" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="position" value="Jabatan" />
                    <TextInput
                        id="position"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.position"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.position" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="savePic"
                    >
                        Simpan
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="deleteModalOpen" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Konfirmasi Hapus</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Apakah Anda yakin ingin menghapus PIC <strong>{{ itemToDelete?.name }}</strong>?
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeDeleteModal">Batal</SecondaryButton>
                    <DangerButton class="ml-3" @click="deletePic">Hapus</DangerButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
