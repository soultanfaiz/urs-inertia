<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import { format, parseISO, isPast, differenceInDays, formatDistanceToNow } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
    requestStatusEnum: {
        type: Object,
        required: true,
    }
});

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value.roles && user.value.roles.includes('admin'));

// --- Local State ---
const activities = ref([]);
const isEditingAll = ref(false);

// --- Drag & Drop State ---
const dragIndex = ref(null);

// --- Forms ---
const addActivityForm = useForm({
    description: '',
    sub_activities: [''],
    end_date: '',
});

const editActivityForm = useForm({
    id: null,
    description: '',
    end_date: '',
});

const addSubActivityForm = useForm({
    development_activity_id: null,
    name: '',
});

// --- Helper Functions (migrated from Blade PHP/JS) ---
const getDeadlineInfo = (activity) => {
    if (!activity.end_date) return null;

    const deadline = parseISO(activity.end_date);
    const isOverdue = isPast(deadline) && !activity.is_completed;
    const isNear = !isOverdue && differenceInDays(deadline, new Date()) <= 7;

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
    activities.value = (newActivities || []).map(activity => ({
        ...activity,
        open: activity.sub_activities?.some(s => !s.is_completed) ?? false,
        showAddSubForm: false, // For inline adding sub-activities
    })).sort((a, b) => a.iteration_count - b.iteration_count);
}, { immediate: true, deep: true });


const toggleEditMode = () => {
    isEditingAll.value = !isEditingAll.value;
    // If we exit edit mode, close any open edit forms
    if (!isEditingAll.value) {
        editActivityForm.reset();
        editActivityForm.clearErrors();
    }
};

const startEdit = (activity) => {
    editActivityForm.id = activity.id;
    editActivityForm.description = activity.description;
    editActivityForm.end_date = activity.end_date ? format(parseISO(activity.end_date), 'yyyy-MM-dd') : '';
};

const cancelEdit = () => {
    editActivityForm.reset();
};

const saveEdit = (activityId) => {
    editActivityForm.patch(route('development-activity.update', activityId), {
        preserveScroll: true,
        onSuccess: () => {
            editActivityForm.reset();
        },
    });
};

const deleteActivity = (activityId) => {
    if (confirm('Anda yakin ingin menghapus aktivitas ini? Semua detail pekerjaan terkait akan ikut terhapus.')) {
        useForm({}).delete(route('development-activity.destroy', activityId), {
            preserveScroll: true,
        });
    }
};

const toggleSubActivity = (subActivityId, isCompleted) => {
    useForm({ is_completed: isCompleted }).patch(route('sub-activity.toggle-status', subActivityId), {
        preserveScroll: true,
    });
};

const deleteSubActivity = (subActivityId) => {
     if (confirm('Anda yakin ingin menghapus detail pekerjaan ini?')) {
        useForm({}).delete(route('sub-activity.destroy', subActivityId), {
            preserveScroll: true,
        });
    }
};

const showSubActivityForm = (activity) => {
    activity.showAddSubForm = true;
    addSubActivityForm.development_activity_id = activity.id;
};

const hideSubActivityForm = (activity) => {
    activity.showAddSubForm = false;
    addSubActivityForm.reset();
};

const saveSubActivity = (activity) => {
    addSubActivityForm.post(route('sub-activity.store'), {
        preserveScroll: true,
        onSuccess: () => {
            hideSubActivityForm(activity);
        }
    });
};

const addSubActivityField = () => {
    addActivityForm.sub_activities.push('');
};

const removeSubActivityField = (index) => {
    if (addActivityForm.sub_activities.length > 1) {
        addActivityForm.sub_activities.splice(index, 1);
    }
};

const saveActivity = () => {
    addActivityForm.post(route('app-request.development-activity.store', props.appRequest.id), {
        preserveScroll: true,
        onSuccess: () => {
            addActivityForm.reset();
            addActivityForm.sub_activities = ['']; // Reset to one empty field
        }
    });
};

// --- Drag and Drop Handlers ---
const handleDragStart = (event, index) => {
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
};

const handleDragEnd = () => {
    dragIndex.value = null;
};

const handleDrop = (event, targetIndex) => {
    if (dragIndex.value === null || dragIndex.value === targetIndex) {
        return;
    }

    const movedItem = activities.value.splice(dragIndex.value, 1)[0];
    activities.value.splice(targetIndex, 0, movedItem);

    const orderedIds = activities.value.map(a => a.id);

    useForm({ ordered_ids: orderedIds }).post(route('development-activity.reorder'), {
        preserveScroll: true,
        // No need for onSuccess, the UI is already updated optimistically
    });
};

</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Aktivitas Pengembangan</h3>
            <div v-if="isAdmin" class="flex items-center gap-2">
                 <button @click="toggleEditMode" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                    <span>{{ isEditingAll ? 'Selesai Mengedit' : 'Edit Aktivitas' }}</span>
                </button>
            </div>
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
                        <div v-if="isEditingAll && editActivityForm.id === activity.id">
                           <!-- Content of edit form here -->
                        </div>

                        <!-- Display View -->
                        <div v-else class="flex items-start justify-between gap-3">
                            <!-- Left: Drag Handle & Content -->
                            <div class="flex items-start flex-1 min-w-0 gap-3">
                                <div v-if="isAdmin && isEditingAll" class="pt-1 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                </div>
                                <div @click="activity.open = !activity.open" class="flex-1 min-w-0 cursor-pointer">
                                    <p class="text-sm font-medium text-gray-800">{{ activity.description }}</p>
                                    <!-- Status & Deadline -->
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="activity.is_completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                            {{ activity.is_completed ? 'Selesai' : 'Dalam Proses' }}
                                        </span>
                                        <template v-if="getDeadlineInfo(activity) && (getDeadlineInfo(activity).isOverdue || !activity.is_completed)">
                                            <div class="text-xs flex items-center" :class="getDeadlineInfo(activity).textColor">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.414-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                                <span class="font-medium">Deadline:</span>
                                                <span class="ml-1">{{ getDeadlineInfo(activity).date }}</span>
                                                <span class="ml-1">{{ getDeadlineInfo(activity).status }}</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <!-- Right: Actions & Arrow -->
                            <div class="flex items-center gap-2">
                                <template v-if="isAdmin && isEditingAll">
                                    <button @click.stop="startEdit(activity)" type="button" class="text-gray-400 hover:text-blue-600" title="Edit aktivitas">
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
                        <!-- Sub-activity list -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Activity Form -->
        <div v-if="isAdmin" class="mt-6">
            <!-- Form content here -->
        </div>
    </div>
</template>

<style>
/* Native HTML5 Drag & Drop Styling */
[draggable="true"] {
    cursor: move;
}

.dragging {
    opacity: 0.5;
    background-color: #eff6ff;
}
</style>
