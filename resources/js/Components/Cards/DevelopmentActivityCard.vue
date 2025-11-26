<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { format, parseISO, isPast, differenceInDays, formatDistanceToNow } from 'date-fns'; // Corrected import
import { id as idLocale } from 'date-fns/locale';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value?.roles?.some(role => role.name === 'admin'));
const enums = computed(() => usePage().props.enums);

const shouldShowCard = computed(() => {
    const status = props.appRequest.status;
    const requestStatusEnum = enums.value.requestStatus;
    return status !== requestStatusEnum.PERMOHONAN && status !== requestStatusEnum.URS;
});

// --- Local State ---
const activities = ref([]);
const isEditingAll = ref(false);
const showNewActivityForm = ref(false);

// --- Drag & Drop State ---
const dragIndex = ref(null);

// --- Forms ---
const newActivityForm = useForm({
    description: '',
    sub_activities: [''],
    end_date: '',
});

const editForms = ref({}); // Holds a form for each activity being edited
const newSubActivityForms = ref({}); // Holds a form for each activity's new sub-activity

// --- Helper Functions ---
const getDeadlineInfo = (activity) => {
    if (!activity.end_date) return null;

    const deadline = parseISO(activity.end_date);
    const isOverdue = isPast(deadline) && !activity.is_completed;

    // Check if the difference is less than or equal to 7 days but not in the past
    const now = new Date();
    const diffDays = differenceInDays(deadline, now);
    const isNear = !isOverdue && diffDays >= 0 && diffDays <= 7;

    const textColor = computed(() => {
        if (isOverdue) return 'text-red-600';
        if (isNear) return 'text-yellow-600';
        return 'text-gray-500';
    });

    const statusText = computed(() => {
        const distance = formatDistanceToNow(deadline, { addSuffix: true, locale: idLocale });
        return isOverdue ? '(melewati deadline)' : `(${distance})`;
    });

    return {
        date: format(deadline, 'd MMM yyyy', { locale: idLocale }),
        status: statusText.value,
        textColor: textColor.value,
        isOverdue: isOverdue,
    };
};

// --- Methods ---

// Initialize and watch for prop changes
watch(() => props.appRequest.development_activities, (newActivities) => {
    activities.value = (newActivities || [])
        .map(activity => ({
            ...activity,
            open: activity.sub_activities?.some(s => !s.is_completed) ?? false,
        }))
        .sort((a, b) => a.iteration_count - b.iteration_count);
}, { immediate: true, deep: true });


const toggleEditMode = () => {
    isEditingAll.value = !isEditingAll.value;
    // If we exit edit mode, clear all edit forms
    if (!isEditingAll.value) {
        editForms.value = {};
    }
};

const startEdit = (activity) => {
    editForms.value[activity.id] = useForm({
        description: activity.description,
        end_date: activity.end_date ? format(parseISO(activity.end_date), 'yyyy-MM-dd') : '',
    });
};

const cancelEdit = (activityId) => {
    delete editForms.value[activityId];
};

const saveEdit = async (activityId) => {
    const form = editForms.value[activityId];
    if (!form) return;

    form.processing = true;

    try {
        await axios.patch(route('development-activity.update', activityId), form.data());

        // Manually update the local state on success
        const activity = activities.value.find(a => a.id === activityId);
        if (activity) {
            activity.description = form.description;
            activity.end_date = form.end_date; // The format is already yyyy-MM-dd, which is fine for display logic
        }

        // Close the edit form
        cancelEdit(activityId);
    } catch (error) {
        console.error('Error updating activity:', error.response?.data?.errors || error.message);
        // You can handle validation errors here, e.g., form.setError(error.response.data.errors)
    } finally {
        form.processing = false;
    }
};

const deleteActivity = (activityId) => {
    if (confirm('Anda yakin ingin menghapus aktivitas ini? Semua detail pekerjaan terkait akan ikut terhapus.')) {
        useForm({}).delete(route('development-activity.destroy', activityId), {
            preserveScroll: true,
        });
    }
};

const toggleSubActivity = (subActivityId, currentStatus) => {
    useForm({ is_completed: !currentStatus }).patch(route('sub-activity.toggle-status', { subActivity: subActivityId }), {
        preserveScroll: true,
    });
};

const deleteSubActivity = (subActivityId) => {
    if (confirm('Anda yakin ingin menghapus detail pekerjaan ini?')) {
        useForm({}).delete(route('sub-activity.destroy', { subActivity: subActivityId }), {
            preserveScroll: true,
        });
    }
};

const startAddSubActivity = (activityId) => {
    newSubActivityForms.value[activityId] = useForm({
        sub_activities: [''], // Start with one empty field
    });
};

const cancelAddSubActivity = (activityId) => {
    delete newSubActivityForms.value[activityId];
};

const saveSubActivity = async (activityId) => {
    const form = newSubActivityForms.value[activityId];
    if (!form) return;

    // Filter out empty strings before submitting
    const nonEmptySubActivities = form.sub_activities.filter(s => s.trim() !== '');
    if (nonEmptySubActivities.length === 0) {
        console.error("Cannot submit empty sub-activities.");
        return;
    }

    form.processing = true;

    try {
        const response = await axios.post(route('development-activity.add-sub-activities', activityId), {
            sub_activities: nonEmptySubActivities
        });

        // Manually update the local state on success
        const updatedActivity = response.data.activity;
        const activityIndex = activities.value.findIndex(a => a.id === activityId);
        if (activityIndex !== -1 && updatedActivity) {
            // Replace the old activity data with the fresh data from the server.
            // This ensures all properties, including `is_completed`, are updated.
            activities.value[activityIndex] = { ...activities.value[activityIndex], ...updatedActivity };
        }

        // Close the form
        cancelAddSubActivity(activityId);
    } catch (error) {
        console.error('Error adding sub-activity:', error.response?.data?.errors || error.message);
        // You can handle form errors here, e.g., form.setError(...)
    } finally {
        form.processing = false;
    }
};

const addSubActivityField = () => {
    newActivityForm.sub_activities.push('');
};

const removeSubActivityField = (index) => {
    if (newActivityForm.sub_activities.length > 1) {
        newActivityForm.sub_activities.splice(index, 1);
    }
};

const saveNewActivity = () => {
    newActivityForm.post(route('app-request.development-activity.store', props.appRequest.id), {
        preserveScroll: true,
        onSuccess: () => {
            newActivityForm.reset();
            newActivityForm.sub_activities = ['']; // Reset to one empty field
            showNewActivityForm.value = false; // Hide form on success
        }
    });
};

// --- Drag and Drop Handlers ---
const handleDragStart = (event, index) => {
    if (!isAdmin.value || !isEditingAll.value) {
        event.preventDefault();
        return;
    }
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
};

const handleDragEnd = () => {
    dragIndex.value = null;
};

const handleDrop = async (event, targetIndex) => {
    if (dragIndex.value === null || dragIndex.value === targetIndex || !isAdmin.value || !isEditingAll.value) {
        return;
    }

    // Optimistic UI update
    const originalActivities = JSON.parse(JSON.stringify(activities.value)); // Deep copy for rollback
    const movedItem = activities.value.splice(dragIndex.value, 1)[0];
    activities.value.splice(targetIndex, 0, movedItem);

    const orderedIds = activities.value.map(a => a.id);

    try {
        await axios.post(route('development-activity.reorder'), { ordered_ids: orderedIds });
        // On success, do nothing, the optimistic update is correct.
    } catch (error) {
        console.error('Failed to reorder activities:', error);
        // Rollback the optimistic update on failure
        activities.value = originalActivities;
        // Optionally, show an error message to the user.
    }
};

</script>
<template>
    <div v-if="shouldShowCard" class="p-4 sm:p-6 bg-white rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Aktivitas Pengembangan</h3>
        </div>

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div v-if="!activities || activities.length === 0" class="py-4 text-center text-gray-500">
                <p>Belum ada aktivitas pengembangan yang tercatat.</p>
            </div>

            <div v-else class="divide-y divide-gray-200" @dragover.prevent @drop.prevent>
                <div v-for="(activity, index) in activities" :key="activity.id"
                    :draggable="isAdmin && isEditingAll"
                    @dragstart="handleDragStart($event, index)"
                    @dragend="handleDragEnd"
                    @drop="handleDrop($event, index)"
                    class="group border-t border-gray-200 first:border-t-0"
                    :class="{ 'opacity-50 bg-blue-50': dragIndex === index, 'cursor-move': isAdmin && isEditingAll }"
                >
                    <!-- Activity Item -->
                    <div class="p-4 hover:bg-gray-50">
                        <!-- Edit Form -->
                        <div v-if="isAdmin && isEditingAll && editForms[activity.id]">
                            <form @submit.prevent="saveEdit(activity.id)">
                                <input type="text" v-model="editForms[activity.id].description" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm mb-2" placeholder="Deskripsi aktivitas...">
                                <div class="mt-2 mb-2">
                                    <label class="text-xs text-gray-600">Deadline</label>
                                    <input type="date" v-model="editForms[activity.id].end_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <button type="submit" :disabled="editForms[activity.id].processing" class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                                        {{ editForms[activity.id].processing ? 'Menyimpan...' : 'Simpan' }}
                                    </button>
                                    <button type="button" @click="cancelEdit(activity.id)" :disabled="editForms[activity.id].processing" class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Display View -->
                        <div v-else class="flex items-start justify-between gap-3" >
                            <!-- Left: Drag Handle & Content -->
                            <div class="flex items-start flex-1 min-w-0 gap-3">
                                <div v-if="isAdmin && isEditingAll" class="pt-1 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                </div>
                                <div @click="activity.open = !activity.open" class="flex-1 min-w-0 cursor-pointer">
                                    <p class="text-sm font-medium text-gray-800">{{ activity.description }}</p>
                                    <!-- Status & Deadline -->
                                    <div class="flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center gap-x-2 gap-y-1 mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="activity.is_completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                            {{ activity.is_completed ? 'Selesai' : 'Dalam Proses' }}
                                        </span>
                                        <template v-if="getDeadlineInfo(activity) && !activity.is_completed">
                                            <div class="text-xs flex items-center" :class="getDeadlineInfo(activity).textColor">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.414-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                                <span class="font-medium">Deadline:</span>
                                                <span class="ml-1 truncate">{{ getDeadlineInfo(activity).date }}</span>
                                                <span class="ml-1">{{ getDeadlineInfo(activity).status }}</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <!-- Right: Actions & Arrow -->
                            <div class="flex items-center gap-2">
                                <template v-if="isAdmin && isEditingAll">
                                    <button @click.stop="startEdit(activity)" type="button" class="text-gray-400 hover:text-blue-600" title="Edit aktivitas" :disabled="editForms[activity.id]">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button @click.stop="deleteActivity(activity.id)" type="button" class="text-gray-400 hover:text-red-600" title="Hapus aktivitas">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </template>
                                <div @click="activity.open = !activity.open" class="pl-2 pt-1 cursor-pointer">
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': activity.open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Activities -->
                    <div v-show="activity.open" class="p-4 bg-gray-50 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Detail Pekerjaan:</h4>
                        <ul v-if="activity.sub_activities?.length > 0" class="space-y-3 pl-5">
                            <li v-for="subActivity in activity.sub_activities" :key="subActivity.id" class="flex items-center gap-3 group">
                                <input v-if="isAdmin" type="checkbox" :checked="subActivity.is_completed" @change="toggleSubActivity(subActivity.id, subActivity.is_completed)" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span class="text-sm flex-1" :class="{ 'line-through text-gray-500': subActivity.is_completed, 'text-gray-800': !subActivity.is_completed }">
                                    {{ subActivity.name }}
                                </span>
                                <button v-if="isAdmin && isEditingAll" @click="deleteSubActivity(subActivity.id)" type="button" class="ml-auto text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus detail pekerjaan">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09.92-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 italic">Tidak ada detail pekerjaan untuk aktivitas ini.</p>

                        <!-- Add Sub-Activity Form -->
                        <div v-if="isAdmin && isEditingAll" class="mt-3 pt-3 border-t border-gray-200">
                            <form v-if="newSubActivityForms[activity.id]" @submit.prevent="saveSubActivity(activity.id)" class="mb-3 p-3 bg-white border border-gray-200 rounded-md">
                                <label class="text-xs font-medium text-gray-700">Detail Pekerjaan Baru</label>
                                <div class="space-y-2 mt-1">
                                    <div v-for="(sub, index) in newSubActivityForms[activity.id].sub_activities" :key="index" class="flex items-center space-x-2">
                                        <input type="text" v-model="newSubActivityForms[activity.id].sub_activities[index]" @keydown.enter.prevent="newSubActivityForms[activity.id].sub_activities.push('')" class="block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Membuat endpoint API">
                                        <button type="button" @click="newSubActivityForms[activity.id].sub_activities.splice(index, 1)" v-show="newSubActivityForms[activity.id].sub_activities.length > 1" class="text-red-500 hover:text-red-700" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" @click="newSubActivityForms[activity.id].sub_activities.push('')" class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Tambah Detail</button>

                                <div class="flex items-center gap-2 mt-3">
                                    <button type="submit" :disabled="newSubActivityForms[activity.id].processing" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50">
                                        {{ newSubActivityForms[activity.id].processing ? 'Menyimpan...' : 'Simpan Detail' }}
                                    </button>
                                    <button type="button" @click="cancelAddSubActivity(activity.id)" :disabled="newSubActivityForms[activity.id].processing" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                                        Batal
                                    </button>
                                </div>
                            </form>
                            <button v-else @click="startAddSubActivity(activity.id)" type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 bg-white hover:bg-gray-50 border border-blue-200 hover:border-blue-300 rounded-md">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Tambah Detail
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="isAdmin" class="mt-6 pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <button @click="showNewActivityForm = !showNewActivityForm" type="button" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" :class="showNewActivityForm ? 'bg-gray-600 hover:bg-gray-700' : 'bg-blue-600 hover:bg-blue-700'">
                <span class="truncate">{{ showNewActivityForm ? 'Batal' : '+ Tambah Aktivitas' }}</span>
            </button>
            <button @click="toggleEditMode" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                <span class="truncate">{{ isEditingAll ? 'Selesai Mengedit' : 'Edit Aktivitas' }}</span>
            </button>
        </div>

        <!-- Add Activity Form (Collapsible) -->
        <div v-if="isAdmin && showNewActivityForm" class="mt-4 border border-dashed border-gray-300 rounded-lg p-4">
            <form @submit.prevent="saveNewActivity">
                <div class="space-y-4">
                    <div>
                        <label for="new_description" class="block text-sm font-medium text-gray-700">Deskripsi Aktivitas Utama Baru</label>
                        <textarea id="new_description" v-model="newActivityForm.description" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" placeholder="Contoh: Implementasi Modul Keuangan" required></textarea>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-800 mb-2">Detail Pekerjaan</h4>
                        <div class="space-y-2">
                            <div v-for="(sub, index) in newActivityForm.sub_activities" :key="index" class="flex items-center space-x-2">
                                <input type="text" v-model="newActivityForm.sub_activities[index]" @keydown.enter.prevent="addSubActivityField" class="block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Membuat endpoint API">
                                <button type="button" @click="removeSubActivityField(index)" v-show="newActivityForm.sub_activities.length > 1" class="text-red-500 hover:text-red-700" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" @click="addSubActivityField" class="mt-3 text-sm text-blue-600 hover:text-blue-800">+ Tambah Detail</button>
                    </div>
                    <div>
                        <label for="new_end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai (Opsional)</label>
                        <input type="date" id="new_end_date" v-model="newActivityForm.end_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2">
                     <button type="button" @click="showNewActivityForm = false" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" :disabled="newActivityForm.processing" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                        {{ newActivityForm.processing ? 'Menyimpan...' : 'Simpan Aktivitas' }}
                    </button>
                </div>
                </div>
            </form>
        </div>
    </div>
</template>
<style>
/* Native HTML5 Drag & Drop Styling */
[draggable="true"] {
    cursor: move;
}
</style>
