<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Pencil, XIcon } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  meeting: {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    level: string;
    user?: {
      id: number;
      name: string;
      email: string;
    };
    info?: {
      description: string;
    };
  };
}>();

const form = useForm({
  id: props.meeting.id,
  title: props.meeting.title,
  start_date: props.meeting.start_date,
  end_date: props.meeting.end_date,
  description: props.meeting.info?.description || '',
  level: props.meeting.level,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meeting',
        href: '/meetings',
    },
    {
        title: 'Meeting: ' + props.meeting.title,
        href: '/meeting/' + props.meeting.id,
    },
];

function handleUpdate() {
  form.put(`/meetings/${form.id}`);
}

function handleCancel() {
  router.visit('/meeting');
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
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
</script>

<template>
  <Head :title="`Edit Meeting: ${form.title}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col items-start justify-start min-h-screen p-4">
      <div class="w-full max-w-2xl bg-white dark:bg-gray-900 p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Edit Meeting</h2>
        <form @submit.prevent="handleUpdate" class="space-y-8">
          <!-- Date/Time Row -->
          <div class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-500 mb-1">Start</label>
              <input
                :value="toLocalInputValue(form.start_date)"
                @input="form.start_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                type="datetime-local"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
                required
              />
            </div>
            <span class="text-gray-400 font-medium">to</span>
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-500 mb-1">End</label>
              <input
                :value="toLocalInputValue(form.end_date)"
                @input="form.end_date = toUTCString(($event.target as HTMLInputElement)?.value || '')"
                type="datetime-local"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
                required
              />
            </div>
            <div class="flex flex-col items-center justify-end">
              <label class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                <input type="checkbox" class="rounded" />
                All day
              </label>
            </div>
          </div>
          <!-- Recurrence -->
          <div class="flex items-center gap-4">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Recurrence</label>
            <select class="rounded border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-900">
              <option>Does not repeat</option>
              <option>Every weekday (Monday to Friday)</option>
              <option>Daily</option>
              <option>Weekly</option>
              <option>Monthly</option>
            </select>
          </div>
          <!-- Location -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Location</label>
            <input type="text" placeholder="Add a location" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm" />
          </div>
          <!-- Notification -->
          <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Notification</label>
            <select class="rounded border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-900">
              <option>Notification</option>
              <option>Email</option>
            </select>
            <input type="number" min="0" value="2" class="w-16 rounded border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-900" />
            <select class="rounded border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-900">
              <option>minutes</option>
              <option>hour</option>
              <option>day</option>
            </select>
            <button type="button" class="text-xs text-blue-600 hover:underline ml-2">Add notification</button>
          </div>
          <!-- Level -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Level</label>
            <select
              v-model="form.level"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
              required
            >
              <option value="info">Info</option>
              <option value="warning">Warning</option>
              <option value="danger">Danger</option>
            </select>
          </div>
          <!-- Participants (placeholder) -->
          <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Participants</label>
              <input type="text" placeholder="Add participants" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm" />
            </div>
            <div class="flex flex-col gap-2 mt-2 md:mt-0">
              <label class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                <input type="checkbox" class="rounded" />
                Edit activity
              </label>
              <label class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                <input type="checkbox" class="rounded" checked />
                Invite others
              </label>
              <label class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                <input type="checkbox" class="rounded" checked />
                View the list of participants
              </label>
            </div>
          </div>
          <!-- Description -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
            <textarea
              v-model="form.description"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm"
              rows="4"
              placeholder="Add a description"
            ></textarea>
          </div>
          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800 mt-8">
            <button
              type="button"
              @click="handleCancel"
              class="flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition"
            >
              <XIcon class="h-5 w-5" />
              Cancel
            </button>
            <button
              type="submit"
              class="flex items-center gap-2 rounded-lg bg-blue-500 hover:bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition shadow"
            >
              <Pencil class="h-5 w-5" />
              Update
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template> 