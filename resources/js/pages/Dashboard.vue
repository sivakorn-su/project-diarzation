<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ListMeetings from '../components/ListMeetings.vue';

const props = defineProps<{
  meetings: any[],
  totalMeetings: number,
  totalUsers: number,
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 bg-gray-50 dark:bg-gray-950">
            <!-- Stats Row -->
            <div class="grid gap-4 md:grid-cols-2 grid-cols-1">
                <!-- Total Meetings Card -->
                <div class="flex items-center gap-4 rounded-xl bg-white dark:bg-gray-900 shadow p-6 border border-blue-100 dark:border-blue-900">
                    <div class="flex items-center justify-center h-14 w-14 rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Total Meetings</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ props.totalMeetings }}</div>
                    </div>
                </div>
                <!-- Total Users Card -->
                <div class="flex items-center gap-4 rounded-xl bg-white dark:bg-gray-900 shadow p-6 border border-blue-100 dark:border-blue-900">
                    <div class="flex items-center justify-center h-14 w-14 rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-5a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Total Users</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ props.totalUsers }}</div>
                    </div>
                </div>
            </div>
            <!-- Recent Meetings Table -->
            <div class="rounded-xl bg-white dark:bg-gray-900 shadow p-6 border border-gray-100 dark:border-gray-800">
                <h2 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">Recent Meetings</h2>
                <ListMeetings :meetings="props.meetings" />
            </div>
        </div>
    </AppLayout>
</template>
