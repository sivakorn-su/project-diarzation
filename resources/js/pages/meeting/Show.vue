<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Dropzone from '@/components/Dropzone.vue';

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
            video_path:string;
        };
    }[];
    authUser: any;
}>();
const  meeting = props.meetings;
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meeting',
        href: '/meetings',
    },
    {
        title: 'Meeting: '+ meeting.title,
        href: '/meeting'+meeting.id,
    },
];
</script>

<template>
    <Head title="Meeting" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 bg-white dark:bg-gray-950">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Meeting: {{ meeting.title }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Meeting By: {{ meeting.user?.name }} — Email: {{ meeting.user?.email }}
                </p>
            </div>

            <div v-if="!meeting.info?.video_path" class="grid gap-4 md:grid-cols-3">
                <div class="col-span-full flex justify-center">
                    <Dropzone />
                </div>
            </div>

            <div class="relative flex flex-1 flex-col gap-4 rounded-xl border border-gray-200 dark:border-gray-700 p-6 min-h-[400px] md:min-h-[calc(100vh-300px)]">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Meeting Description:
                </h2>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ meeting.info?.description }}
                </p>
                ขาดเรื่อง Speech  filter by speaker and summary


                <h3 class="text-md font-medium text-gray-700 dark:text-gray-400">
                    Start Date: {{ meeting.start_date }} — {{ meeting.end_date }}
                </h3>
            </div>
        </div>
    </AppLayout>
</template>
