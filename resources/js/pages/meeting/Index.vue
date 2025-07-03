<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm,router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import thLocale from '@fullcalendar/core/locales/th';
import Modal from '@/components/Modal.vue';
import { watchEffect, reactive, ref } from 'vue';
import { Trash, Pencil, InfoIcon, XIcon, Clock, Calendar, CircleUserRoundIcon, Mail } from 'lucide-vue-next';

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
const renderEventContent = (eventInfo: any) => {
    const level = eventInfo.event.extendedProps.calendar?.toLowerCase();

    const levelStyles: { [key: string]: { dot: string; bg: string } } = {
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
        dot: 'bg-slate-400',
        bg: 'bg-slate-100',
    };

    const title = eventInfo.event.title ?? '';
    let timeText = '';
    if (eventInfo.event.start) {
        const start = new Date(eventInfo.event.start);
        const timeOptions: Intl.DateTimeFormatOptions = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'Asia/Bangkok',
        };
        timeText = start.toLocaleTimeString('th-TH', timeOptions).replace(/^0/, '');
    }
    if (eventInfo.event.end) {
        const end = new Date(eventInfo.event.end);
        const timeOptions: Intl.DateTimeFormatOptions = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'Asia/Bangkok',
        };
        const endTime = end.toLocaleTimeString('th-TH', timeOptions).replace(/^0/, '');
        timeText = `${timeText} – ${endTime}`;
    }

    return {
        html: `
            <div class="flex w-full items-center gap-2 overflow-hidden rounded-md ${bgColor} p-2">
                <div class="w-2.5 h-2.5 rounded-full ${dotColor} flex-shrink-0"></div>
                
                <div class="fc-event-time text-xs text-gray-700 whitespace-nowrap font-semibold flex-shrink-0">
                    ${timeText}
                </div>
                
                <div class="fc-event-title text-xs text-gray-900 truncate font-medium w-0 flex-grow" title="${title}">
                    ${title}
                </div>
            </div>
        `,
    };
};

const calendarOptions: any = reactive({
    locales: [ thLocale ],
    locale: 'th',
    timeZone: 'Asia/Bangkok',
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'addEventButton',
    },
    weekends: true,
    height: 760,
    selectable: true,
    dayMaxEventRows: 3,
    eventBorderColor: 'transparent',
    eventColor:'transparent',
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventContent: renderEventContent,
    customButtons: {
        addEventButton: {
            text: 'Add +',
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

function toUTCString(localDateTime: string): string {
  if (!localDateTime) return '';
  const date = new Date(localDateTime);
  return date.toISOString();
}

function toLocalInputValue(utcString: string): string {
  if (!utcString) return '';
  const date = new Date(utcString);
  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function formatMeetingDateTime(start: string, end?: string): string {
    if (!start) return '';
    const startDate = new Date(start);
    let dateStr = '';
    let startTime = '';
    let endTime = '';

    // Day of week, Month, Day in Thai
    const dateOptions: Intl.DateTimeFormatOptions = {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        timeZone: 'Asia/Bangkok'
    };
    dateStr = startDate.toLocaleDateString('th-TH', dateOptions);

    // Time (24-hour) in Thai
    const timeOptions: Intl.DateTimeFormatOptions = {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Bangkok'
    };
    startTime = startDate.toLocaleTimeString('th-TH', timeOptions).replace(/^0/, '');

    if (end) {
        const endDate = new Date(end);
        endTime = endDate.toLocaleTimeString('th-TH', timeOptions).replace(/^0/, '');
        return `${dateStr} • ${startTime} – ${endTime}`;
    } else {
        return `${dateStr} • ${startTime}`;
    }
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
        <Modal v-if="isOpen" @close="closeModal">
            <template #body>
                <div v-if="authUser && authUser.id !== form.user_id && form.id" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11">
                    <h5 class="modal-title text-theme-xl mb-2 font-semibold text-gray-800 lg:text-2xl dark:text-white/90">
                        {{form.title}}
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">From: {{form.email}}</p>
                    <div class="mt-8">
                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ formatMeetingDateTime(form.start_date, form.end_date) }}
                            </label>
                        </div>
                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Description: {{form.description}}</label>
                        </div>
                        <div class="mt-6">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">

                                Level: {{form.level ? form.level : '-' }}
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:justify-end absolute top-0 right-0 m-2 p-6">
                        <button
                            v-if="form.id"
                            @click="Info(form.id)"
                            class="btn btn-update-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg text-gray-400 hover:text-gray-500 px-2 py-2.5 text-sm font-medium  sm:w-auto"
                        >
                        <component
                            :is="InfoIcon"
                            class="h-5 w-5"
                        />
                        </button>
                        <button
                            @click="closeModal"
                            class="flex w-full justify-center text-sm font-medium text-gray-400 hover:text-gray-700 sm:w-auto rounded-full"
                        >
                        <component
                            :is="XIcon"
                            class="h-5 w-5"
                        />
                        </button>
                        
                    </div>
                    
                </div>

                <div v-else class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-4 lg:p-11 dark:bg-gray-900">
                    <div class="mt-8">
                        <div>
                            <template v-if="!form.id">
                                <input
                                    v-model="form.title"
                                    type="text"
                                    :placeholder="form.title || 'Example meeting'"
                                    class="my-4 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-blue-400 focus:outline-none focus:outline-hidden"
                                />
                            </template>
                            <template v-else>
                                <div class="text-xl font-semibold text-gray-800 dark:text-white mb-2">{{ form.title }}</div>
                            </template>
                            <span v-if=" form.id" class="text-sm text-gray-500 flex gap-4 my-4">
                                <component
                                    :is="Mail"
                                    class="h-5 w-5"
                                />
                            <p class="text-sm "> {{ form.id ?  form.email : ''}}</p>
                        
                            </span>
                            </div>

                        <div class="flex flex-col md:flex-row md:items-end gap-4 py-4 items-center justify-center">
                            <span class="text-gray-400 font-medium my-2"> 
                            <component
                            :is="Clock"
                            class="h-5 w-5"
                            />
                            </span>    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Start</label>
                                <template v-if="!form.id">
                                    <input
                                        :value="toLocalInputValue(form.start_date)"
                                        @input="form.start_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                                        type="datetime-local"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
                                        required
                                    />
                                </template>
                                <template v-else>
                                    <div class="text-gray-800 dark:text-white">{{ formatMeetingDateTime(form.start_date) }}</div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">End</label>
                                <template v-if="!form.id">
                                    <input
                                        :value="toLocalInputValue(form.end_date)"
                                        @input="form.end_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                                        type="datetime-local"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
                                        required
                                    />
                                </template>
                                <template v-else>
                                    <div class="text-gray-800 dark:text-white">{{ formatMeetingDateTime(form.end_date) }}</div>
                                </template>
                            </div>
                            <div class="flex flex-col items-center justify-end">
                                <!-- <label class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    <input type="checkbox" class="rounded" />
                                    All day
                                </label> -->
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-end gap-4 py-4 items-center justify-center">
                            <span class="text-gray-400 font-medium my-2"> 
                            <component
                            :is="Calendar"
                            class="h-5 w-5"
                            />
                            </span>    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Priority</label>
                                <template v-if="!form.id">
                                    <select
                                        v-model="form.level"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
                                        required
                                        >
                                        <option value="info">Info</option>
                                        <option value="warning">Warning</option>
                                        <option value="danger">Danger</option>
                                        </select>
                                </template>
                                <template v-else>
                                    <div class="text-gray-800 dark:text-white">{{ form.level? form.level : 'None Priority' }}</div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div v-if="!form.id" class="model-footer flex justify-end gap-3 pt-4 border-gray-100 mt-2 ">
                        <button
                            @click="handleAddOrUpdateEvent"
                            class="flex items-center gap-2 rounded-lg bg-blue-500 hover:bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition shadow"
                            >
                            Create
                        </button>
                        <button
                            @click="closeModal"
                            class="flex w-full justify-center text-sm font-medium text-gray-400 hover:text-gray-700 sm:w-auto rounded-full absolute top-0 right-0 m-2 p-6"
                        >
                        <component
                            :is="XIcon"
                            class="h-5 w-5"
                        />
                        </button>
                    </div>
                    <div v-if="form.id && authUser && authUser.id === form.user_id" class="flex items-center gap-3 sm:justify-end absolute top-0 right-0 m-2 p-6">
                        <button
                            @click="router.visit(`/meetings/${form.id}/edit`)"
                            class="flex w-full justify-center rounded-lg text-gray-400 hover:text-blue-500 px-2 py-2.5 text-sm font-medium sm:w-auto "
                        >
                        <component
                            :is="Pencil"
                            class="h-5 w-5"
                        />
                        </button>
                        <button
                            v-if="form.id"
                            @click="handleDeleteEvent"
                            class="border-error-500 bg-error-500 hover:bg-error-600 flex w-full justify-center px-2 text-gray-400 hover:text-red-500  py-2.5 text-sm font-medium  sm:w-auto "
                        >
                        <component
                            :is="Trash"
                            class="h-5 w-5"
                        />
                        </button>
                        <button
                            v-if="form.id"
                            @click="Info(form.id)"
                            class="btn btn-update-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg text-gray-400 hover:text-gray-500 px-2 py-2.5 text-sm font-medium  sm:w-auto"
                        >
                        <component
                            :is="InfoIcon"
                            class="h-5 w-5"
                        />
                        </button>
                        <button
                            @click="closeModal"
                            class="flex w-full justify-center text-sm font-medium text-gray-400 hover:text-gray-700 sm:w-auto rounded-full"
                        >
                        <component
                            :is="XIcon"
                            class="h-5 w-5"
                        />
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
<style>

.fc .fc-button {
    background-color: white;
    color: #9ca3af;
    border: 1px solid #d1d5db;
    border-radius: 0.75rem; /* rounded-xl */
    padding: 0.5rem 1rem; /* ปรับขนาดใหญ่ขึ้น */
    font-weight: 500;
    font-size: 0.95rem;
    min-height: 2.5rem; /* สูงขึ้น ดูเต็มมือ */
    min-width: 2.5rem; /* ถ้าเป็นปุ่ม icon จะไม่ดูเล็ก */
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    outline: none;
}

.fc .fc-button:hover {
    color: #6b7280; /* text-gray-500 */
    background-color: #f3f4f6; /* เพิ่มพื้นหลังบางๆ ให้ดูมีมิติ */
    border-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.fc .fc-button:focus {
    background-color: #f3f4f6;
    border-color: #f3f4f6;
    box-shadow: 0 0 0 3px rgba(212, 215, 217, 0.3);
}

.fc .fc-today-button {
    background-color: #f9fafb;
    font-weight: 600;
}

.fc .fc-button.fc-button-active {
    background-color: #e5e7eb;
    border-color: #d1d5db;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}



</style>