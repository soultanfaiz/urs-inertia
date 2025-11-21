<script setup>
import { computed } from 'vue';
import { format, parseISO, isPast, differenceInDays, formatDistanceToNow } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    appRequest: {
        type: Object,
        required: true,
    },
});

// --- Progress Steps Logic ---

// Define the sequence of statuses for the progress bar.
const stepOrder = ['permohonan', 'urs', 'pengembangan', 'uat', 'selesai'];

// Find the index of the current status in the defined order.
const currentStepIndex = computed(() => {
    return stepOrder.indexOf(props.appRequest.status?.toLowerCase());
});

// Create a reactive array of step objects with their calculated status (completed, current, pending).
const processedSteps = computed(() => {
    return stepOrder.map((stepName, index) => {
        let status = 'pending';
        if (index < currentStepIndex.value || props.appRequest.status === 'selesai') {
            status = 'completed';
        } else if (index === currentStepIndex.value) {
            status = 'current';
        }

        // Custom labels for specific steps
        let label = stepName.charAt(0).toUpperCase() + stepName.slice(1);
        if (stepName === 'urs') label = 'URS';
        if (stepName === 'uat') label = 'UAT';

        return {
            name: stepName,
            label: label,
            status: status,
        };
    });
});

// Get the name of the current step for the title.
const currentStepName = computed(() => {
    const currentStep = processedSteps.value[currentStepIndex.value];
    return currentStep ? currentStep.label : 'Proses Selesai';
});

// --- Deadline Calculation Logic ---

const deadline = computed(() => {
    return props.appRequest.end_date ? parseISO(props.appRequest.end_date) : null;
});

const isOverdue = computed(() => {
    return deadline.value && isPast(deadline.value) && props.appRequest.status !== 'selesai';
});

const isNearDeadline = computed(() => {
    if (!deadline.value || isOverdue.value || props.appRequest.status === 'selesai') {
        return false;
    }
    return differenceInDays(deadline.value, new Date()) <= 7;
});

const deadlineTextColorClass = computed(() => {
    if (isOverdue.value) return 'text-red-600';
    if (isNearDeadline.value) return 'text-yellow-600';
    return 'text-gray-500';
});

const showDeadlineInfo = computed(() => {
    return deadline.value && !['permohonan', 'selesai'].includes(props.appRequest.status);
});

const formattedDeadline = computed(() => {
    return deadline.value ? format(deadline.value, 'd MMM yyyy', { locale: id }) : '';
});

const deadlineForHumans = computed(() => {
    return deadline.value ? formatDistanceToNow(deadline.value, { addSuffix: true, locale: id }) : '';
});

</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <h3 class="font-semibold text-gray-800">Status Permohonan: <span class="font-normal">{{ currentStepName }}</span></h3>

        <!-- Progress Bar -->
        <div class="flex items-start mt-4">
            <template v-for="(step, index) in processedSteps" :key="step.name">
                <!-- Step Icon and Label -->
                <div class="flex flex-col items-center w-24">
                    <!-- Icon -->
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-full text-white"
                        :class="{
                            'bg-green-500': step.status === 'completed',
                            'bg-blue-500': step.status === 'current',
                            'bg-gray-300 text-gray-500': step.status === 'pending',
                        }"
                    >
                        <svg v-if="step.status === 'completed'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg v-else-if="step.status === 'current'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>

                    <!-- Label -->
                    <span class="mt-2 text-xs text-center text-gray-500">{{ step.label }}</span>
                </div>

                <!-- Connecting Line -->
                <template v-if="index < processedSteps.length - 1">
                    <div
                        v-if="index < currentStepIndex || appRequest.status === 'selesai'"
                        class="flex-1 h-1 mt-3.5 bg-green-500"
                    ></div>
                    <div
                        v-else
                        class="flex-1 h-1 mt-3.5 bg-repeat-x"
                        style="background-image: url('data:image/svg+xml,%3Csvg width=\'10\' height=\'2\' viewBox=\'0 0 10 2\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 1H10\' stroke=\'%23D1D5DB\' stroke-width=\'2\' stroke-dasharray=\'2 3\'/%3E%3C/svg%3E');"
                    ></div>
                </template>
            </template>
        </div>

        <!-- Deadline Information -->
        <div v-if="showDeadlineInfo" class="flex items-center justify-end pt-4 mt-4 text-sm border-t border-gray-200" :class="deadlineTextColorClass">
            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.414-1.415L11 9.586V6z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">Deadline:</span>
            <span class="ml-1">{{ formattedDeadline }}</span>
            <span class="ml-1">({{ deadlineForHumans }})</span>
        </div>
    </div>
</template>
