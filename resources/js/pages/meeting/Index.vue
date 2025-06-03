<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm,router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import Modal from '@/components/Modal.vue';
import { watchEffect, reactive, ref } from 'vue';

const props = defineProps<{
    meetings: {
        id: number;
        title: string;
        start_date: string;
        end_date: string;
        level: string;
        user?: {
            id:number,
            name: string;
            email:string;
        };
        info?:{
            description:string;
        };
    }[];
    authUser: any;
}>();

const isOpen = ref(false);
const selectedEvent = ref(null);
const events = ref<any[]>([]);
const form = useForm({
    id: null,
    title: '',
    start_date: '',
    end_date: '',
    level: '',
    user:'',
    email:'',
    user_id:'',
    description:'',
});
const openModal = () => {
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
    resetModalFields();
};

const resetModalFields = () => {
    form.reset();
    selectedEvent.value = null;
};

const handleDateSelect = (selectInfo: { startStr: string; endStr: string }) => {
    form.reset();
    form.start_date = formatDateTimeLocal(selectInfo.startStr);
    form.end_date = formatDateTimeLocal(selectInfo.endStr);
    openModal();
};

const handleEventClick = (clickInfo: { event: any }) => {
    const event = clickInfo.event;
    console.log(event);
    form.id = event.id;
    form.title = event.title;
    form.start_date = formatDateTimeLocal(event.start);
    form.end_date = formatDateTimeLocal(event.end);
    form.level = event.extendedProps.calendar;
    form.user = event.extendedProps.user.name || '-';
    form.email = event.extendedProps.user.email || '-';
    form.user_id = event.extendedProps.user.id;
    form.description = event.extendedProps.info.description || '-';
    openModal();
};

const handleAddOrUpdateEvent = () => {
    if (form.id) {
        form.put(`/meetings/${form.id}`);
    } else {
        form.post('/meetings');
    }
    form.reset();
    closeModal();
};

const handleDeleteEvent = () => {
    if (form.id) {
        form.delete(`/meetings/${form.id}`);
        closeModal();
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meeting',
        href: '/meeting',
    },
];
const renderEventContent = (eventInfo) => {
    const level = eventInfo.event.extendedProps.calendar?.toLowerCase();

    const levelStyles = {
        info: {
            dot: 'bg-green-500',
            bg: 'bg-green-100',
        },
        warning: {
            dot: 'bg-yellow-400',
            bg: 'bg-yellow-100',
        },
        danger: {
            dot: 'bg-red-500',
            bg: 'bg-red-100',
        },
    };

    const { dot: dotColor, bg: bgColor } = levelStyles[level] || {
        dot: 'bg-gray-400',
        bg: 'bg-gray-100',
    };

    const title = eventInfo.event.title ?? '';
    const timeText = eventInfo.timeText ?? '';

    return {
        html: `
            <div class="flex w-full items-center gap-1 overflow-hidden rounded-sm ${bgColor} p-2">
                <div class="w-2.5 h-2.5 rounded-full ${dotColor}"></div>
                <div class="fc-event-time text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">${timeText}</div>
                <div class="fc-event-title text-xs text-gray-900 dark:text-white truncate w-full" title="${title}">
                    ${title}
                </div>
            </div>
        `,
    };
};

const calendarOptions = reactive({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next addEventButton',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    weekends: true,
    selectable: true,
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventContent: renderEventContent,
    customButtons: {
        addEventButton: {
            text: 'Add Event +',
            click: openModal,
        },
    },
    events: events,
    eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    },
});

watchEffect(() => {
    if (props.meetings?.length) {
        events.value = props.meetings.map(meeting => ({
            id: meeting.id,
            title: meeting.title,
            start: meeting.start_date,
            end: meeting.end_date,
            extendedProps: {
                calendar: meeting.level,
                user:{
                    id: meeting.user?.id ?? '-',
                    name: meeting.user?.name ?? '-',
                    email: meeting.user?.email ?? '-',
                },
                info:{
                    description:meeting.info?.description||'-',
                },
            },
        }));
    }
});
const formatDateTimeLocal = (date: Date | string | undefined): string => {
    if (!date) return ''

    const d = typeof date === 'string' ? new Date(date) : date
    const pad = (n: number) => n.toString().padStart(2, '0')

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}
const Info = (id:number) => {
    if (!id) return
    router.visit(route('meetings.show', id))
}
</script>

<template>
    <Head title="Meeting" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative flex-1 rounded-xl border p-10 md:min-h-min">
                <FullCalendar :options="calendarOptions" class="min-h-screen">
                </FullCalendar>
            </div>
        </div>
        <Modal v-if="isOpen" @close="() => (closeModal = false)">
            <template #body>
                <div v-if="authUser && authUser.id !== form.user_id && form.id" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
                    <h5 class="modal-title text-theme-xl mb-2 font-semibold text-gray-800 lg:text-2xl dark:text-white/90">
                        {{'Event ' + form.title}}
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">From: {{form.email}}</p>
                    <div class="mt-8">
                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Start Date: {{ formatDateTimeLocal(form.start_date) }} Until {{ formatDateTimeLocal(form.end_date) }}</label>
                        </div>
                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Description: {{form.description}}</label>
                        </div>
                    </div>

                    <div class="modal-footer mt-6 flex items-center gap-3 sm:justify-end">
                        <button
                            @click="closeModal"
                            class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                        >
                            Close
                        </button>
                        <button
                            @click="Info(form.id)"
                            class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                        >
                            Info
                        </button>
                    </div>
                </div>

                <div v-else class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
                    <h5 class="modal-title text-theme-xl mb-2 font-semibold text-gray-800 lg:text-2xl dark:text-white/90">
                        {{ form.id ? 'Edit Event ' + form.title : 'Add Event' }}
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400"> {{ form.id ?  'From: '+form.email : ''}}</p>

                    <div class="mt-8">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"> Event Title </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            />
                        </div>

                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"> Enter Start Date </label>
                            <input
                                v-model="form.start_date"
                                type="datetime-local"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            />
                        </div>

                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"> Enter End Date </label>
                            <input
                                v-model="form.end_date"
                                type="datetime-local"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            />
                        </div>
                    </div>

                    <div class="modal-footer mt-6 flex items-center gap-3 sm:justify-end">
                        <button
                            @click="closeModal"
                            class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                        >
                            Close
                        </button>
                        <button
                            @click="handleAddOrUpdateEvent"
                            class="btn btn-update-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-medium text-white sm:w-auto"
                        >
                            {{ form.id ? 'Update Changes' : 'Add Event' }}
                        </button>
                        <button
                            v-if="form.id"
                            @click="handleDeleteEvent"
                            class="border-error-500 bg-error-500 hover:bg-error-600 flex w-full justify-center rounded-lg border bg-red-500 px-4 py-2.5 text-sm font-medium text-white sm:w-auto"
                        >
                            Delete Event
                        </button>
                        <button
                            v-if="form.id"
                            @click="Info(form.id)"
                            class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                        >
                            Info
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
