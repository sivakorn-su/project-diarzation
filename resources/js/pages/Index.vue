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
    level: 'info',
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
    form.level = 'info';
    selectedEvent.value = null;
};

const handleDateSelect = (selectInfo: any) => {
    form.reset();
    form.level = 'info';
    
    let start = selectInfo.start;
    let end = selectInfo.end;

    if (selectInfo.allDay) {
        // If all day, end date is exclusive (00:00 of next day).
        // Subtract 1 second to make it 23:59:59 of the intended last day.
        end = new Date(end.getTime() - 1000);
    }

    form.start_date = formatDateTimeLocal(start);
    form.end_date = formatDateTimeLocal(end);
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
            dot: 'bg-sky-500',
            bg: 'bg-sky-100',
        },
        warning: {
            dot: 'bg-orange-400',
            bg: 'bg-orange-100',
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
                <!-- View Mode (Read-only for others) -->
                <div v-if="authUser && authUser.id !== form.user_id && form.id" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900 transition-all transform duration-300">
                    <!-- Header Banner -->
                    <div class="h-24 bg-gradient-to-r from-blue-500 to-indigo-600 p-6 flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white tracking-wide truncate">{{ form.title }}</h3>
                        <div class="flex gap-2">
                             <button @click="closeModal" class="rounded-full bg-white/20 p-2 text-white hover:bg-white/30 transition">
                                <component :is="XIcon" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- User Info -->
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                                {{ form.user ? form.user.charAt(0).toUpperCase() : 'U' }}
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Organizer</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ form.user }}</p>
                                <p class="text-xs text-gray-500">{{ form.email }}</p>
                            </div>
                        </div>

                        <!-- Time -->
                        <div class="flex items-start gap-4">
                            <div class="p-2 rounded-lg bg-orange-50 text-orange-500">
                                <component :is="Clock" class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Time</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ formatMeetingDateTime(form.start_date, form.end_date) }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="flex items-start gap-4">
                            <div class="p-2 rounded-lg bg-green-50 text-green-500">
                                <component :is="InfoIcon" class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Description</p>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ form.description || 'No description provided.' }}</p>
                            </div>
                        </div>

                        <!-- Level Badge -->
                        <div class="flex items-center gap-4">
                             <div class="p-2 rounded-lg bg-purple-50 text-purple-500">
                                <component :is="Calendar" class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Priority Level</p>
                                <span :class="{
                                    'bg-sky-100 text-sky-700': form.level === 'info',
                                    'bg-orange-100 text-orange-700': form.level === 'warning',
                                    'bg-red-100 text-red-700': form.level === 'danger',
                                    'bg-gray-100 text-gray-700': !form.level
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 capitalize">
                                    {{ form.level || 'None' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-8 py-4 flex justify-end gap-3">
                         <button
                            v-if="form.id"
                            @click="Info(form.id)"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm font-medium text-sm"
                        >
                            <component :is="InfoIcon" class="h-4 w-4" />
                            Details
                        </button>
                    </div>
                </div>

                <!-- Edit/Create Mode -->
                <div v-else class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900 transition-all transform duration-300">
                    <!-- Header -->
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/30">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ form.id ? 'Edit Meeting' : 'Schedule Meeting' }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Fill in the details below to {{ form.id ? 'update' : 'create' }} your event.</p>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                            <component :is="XIcon" class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Title Input -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Event Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="e.g., Q4 Strategy Meeting"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <!-- Date Time Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Start Time</label>
                                <div class="relative">
                                    <input
                                        :value="toLocalInputValue(form.start_date)"
                                        @input="form.start_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                                        type="datetime-local"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">End Time</label>
                                <div class="relative">
                                    <input
                                        :value="toLocalInputValue(form.end_date)"
                                        @input="form.end_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                                        type="datetime-local"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Priority Level -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Priority Level</label>
                            <div class="flex gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" v-model="form.level" value="info" class="peer sr-only" />
                                    <div class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 transition-all flex items-center gap-2 hover:bg-gray-50">
                                        <div class="w-2 h-2 rounded-full bg-sky-500"></div>
                                        Info
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" v-model="form.level" value="warning" class="peer sr-only" />
                                    <div class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition-all flex items-center gap-2 hover:bg-gray-50">
                                        <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                        Warning
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" v-model="form.level" value="danger" class="peer sr-only" />
                                    <div class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 transition-all flex items-center gap-2 hover:bg-gray-50">
                                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                        Danger
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-8 py-5 flex items-center justify-between border-t border-gray-100 dark:border-gray-800">
                        <div>
                             <button
                                v-if="form.id"
                                @click="handleDeleteEvent"
                                class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-2 transition"
                            >
                                <component :is="Trash" class="h-4 w-4" />
                                Delete Event
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="closeModal"
                                class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-200/50 font-medium transition dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            
                            <button
                                v-if="form.id"
                                @click="Info(form.id)"
                                class="px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm font-medium flex items-center gap-2"
                            >
                                <component :is="InfoIcon" class="h-4 w-4" />
                                Details
                            </button>

                            <button
                                @click="handleAddOrUpdateEvent"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all transform hover:scale-[1.02] active:scale-[0.98]"
                            >
                                {{ form.id ? 'Save Changes' : 'Create Event' }}
                            </button>
                        </div>
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
    color: oklch(70.7% 0.165 254.624); /* text-gray-500 */
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

.fc-toolbar-title {
  padding: 4px 8px;
  border-radius: 4px;
  color: oklch(70.7% 0.165 254.624);
}

</style>