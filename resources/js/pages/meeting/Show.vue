<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref,computed } from 'vue';
import { EyeIcon, ListIcon,ListFilterIcon } from 'lucide-vue-next';


const props = defineProps<{
    meetings: {
        id: string;
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
            video_path: string;
            transcript_json: {
                start: number;
                end: number;
                speaker: string;
                filename: string;
                text: string;
            }[];
        };
    };
    authUser: any;
}>();

const meeting = props.meetings;
const results = ref<any[]>([]);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meeting',
        href: '/meetings',
    },
    {
        title: 'Meeting: ' + meeting.title,
        href: '/meeting/' + meeting.id,
    },
];

const form = useForm({
    video: null,
    transcript: null as any,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    if (file) {
        console.log('Selected file:', file);
        form.video = file;
    } else {
        form.video = null;
    }
};

const submitComment = async () => {
    if (!form.video) {
        alert('Please select a video file before uploading.');
        return;
    }
    const formData = new FormData();
    formData.append('file', form.video);

    try {
        const response = await fetch('https://fbdc-34-125-219-186.ngrok-free.app/upload_video/', {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Upload failed: ${response.statusText}`);
        }

        const result = await response.json();


        if (!Array.isArray(result)) {
            throw new Error('Invalid response format: expected an array.');
        }

        console.log('Transcript result:', result);

        form.transcript = result;


        form.post(`/meetings/${meeting.id}/infos`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                window.location.reload();
            },
        });
    } catch (error) {
        console.error('Upload error:', error);
        alert('Failed to upload video. Please try again.');
    }
};
const transcript = props.meetings.info?.transcript_json || [];

const selectedSpeaker = ref('');

const speakers = [...new Set(transcript.map(item => item.speaker))];

const filteredTranscript = computed(() => {
    if (!selectedSpeaker.value) {
        return transcript;
    }
    return transcript.filter(item => item.speaker === selectedSpeaker.value);
});
const showFullView = ref(true);
</script>

<template>
    <Head title="Meeting" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl bg-white p-6 dark:bg-gray-950">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Meeting: {{ meeting.title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 my-4">Meeting By: {{ meeting.user?.name }} — Email: {{ meeting.user?.email }}</p>
            </div>

            <div v-if="!meeting.info?.transcript_json" class="grid gap-4 md:grid-cols-3">
                <div class="col-span-full flex flex-col items-start gap-2">
                    <input
                        type="file"
                        name="file"
                        accept="video/mp4"
                        @change="handleFileChange"
                        class="mt-2 w-full rounded-md border px-4 py-2 focus:ring-1 focus:ring-blue-600 focus:outline-none"
                    />
                    <button @click="submitComment" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-white">Upload</button>
                    <p v-if="form.errors.video" class="text-sm text-red-600">
                        {{ form.errors.video }}
                    </p>
                </div>
            </div>

            <div class="relative flex min-h-[400px] flex-col gap-4 rounded-xl border border-gray-200 p-6 dark:border-gray-700">
                <button
                    @click="showFullView = !showFullView"
                    class="absolute right-4 top-4 inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                >
                    <component
                        :is="showFullView ? ListIcon : EyeIcon"
                        class="h-5 w-5"
                    />
                </button>

                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Meeting Description:</h2>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ meeting.info?.description }}
                </p>

                <h3 class="text-md font-medium text-gray-700 dark:text-gray-400">Start Date: {{ meeting.start_date }} — {{ meeting.end_date }}</h3>
                <div v-if="meetings.info?.transcript_json?.length && showFullView" class="mt-6 space-y-2">
                    <div class="flex items-center gap-2 rounded-md border border-gray-300 bg-white p-4 shadow-md max-w-xs">
                        <component
                            :is="ListFilterIcon"
                            class="h-5 w-5 text-gray-500"
                        />
                        <select
                            id="speaker-select"
                            v-model="selectedSpeaker"
                            class="flex-1 rounded-md border border-gray-300 bg-white py-2 px-3 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                        >
                            <option value="">
                                All Speakers
                            </option>
                            <option v-for="speaker in speakers" :key="speaker" :value="speaker">
                                {{ speaker }}
                            </option>
                        </select>
                    </div>
                    <div v-if="filteredTranscript.length" class="mt-6 space-y-2">
                        <div
                            v-for="(item, index) in filteredTranscript"
                            :key="index"
                            class="rounded-md border p-4 shadow"
                        >
                            <p><strong>Speaker : </strong> {{ item.speaker }}</p>
                            <p><strong>เริ่มวินาทีที่ : </strong> {{ item.start }}</p>
                            <p><strong>จบวินาทีที่ : </strong> {{ item.end }}</p>
                            <p><strong>คำพูดที่ถอดมาได้ : </strong> {{ item.text }}</p>
                        </div>
                    </div>
                </div>
                <div v-else-if="meetings.info?.transcript_json?.length && !showFullView" class="mt-6 space-y-3 text-base leading-relaxed text-gray-800 dark:text-gray-200 border rounded-md p-4">
                    <div v-for="(item, index) in filteredTranscript" :key="index">
                            <p><strong>{{ item.speaker }} : </strong> {{ item.text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
