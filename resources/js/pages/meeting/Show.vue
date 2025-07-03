<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref,computed } from 'vue';
import { Lightbulb, ListOrderedIcon,ListFilterIcon,FileUpIcon,Loader } from 'lucide-vue-next';
import TranscriptPanel from '@/components/TranscriptPanel.vue'
import MeetingStats from '@/components/MeetingStats.vue'
import SpeakerCountList from '@/components/SpeakerCountList.vue'
import MeetingSummaries from '@/components/MeetingSummaries.vue'


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
            audio_path: string;
            audio_length: number,
            transcript_json: {
                data: {
                    start: number | string;
                    end: number | string;
                    speaker: string;
                    filename: string;
                    text: string;
                }[];
                count_speaker: {
                    speaker: string;
                    count: number | string;
                }[];
                summaries:string[];
                video_path: string;
                num_speakers: number;
                speaker_array: string[];
                total_sentence: number;
            };
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
    url: 'https://inwneon-project-voice-diarzation.hf.space',
    statusMessage:'',
    statusType:'',
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    if (file) {
        form.video = file;
    } else {
        form.video = null;
    }
};
const submitComment = async () => {
    form.processing = true;
    form.statusMessage = '';
    form.statusType = '';

    if (!form.video) {
        alert('Please select a video file before uploading.');
        form.processing = false;
        return;
    }if(!form.url){
        alert('Please enter a url before uploading.');
        form.processing = false;
        return;
    }

    let baseUrl = form.url.trim();
    if (baseUrl.endsWith('/')) baseUrl = baseUrl.slice(0, -1);
    try {
        const response = await fetch(baseUrl, { method: 'GET' })
        if (response.ok) {
            form.statusMessage = 'Connected to server'
            form.statusType = 'success'
        } else {
            form.statusMessage = `Server responded: ${response.status}`
            form.statusType = 'error'
            return
        }
    } catch (err) {
        form.statusMessage = `Connection failed: ${err.message}`
        form.statusType = 'error'
        return
    }

    const formData = new FormData();
    formData.append('file', form.video);
    const uploadUrl = `${baseUrl}/upload_video/`

    try {
        const response = await fetch(uploadUrl, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Upload failed: ${response.statusText}`);
        }

        const result = await response.json();

        if (!Array.isArray(result.data) || result.data.length === 0) {
            throw new Error('No transcript data received.');
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
    }finally {
        form.processing = false;
    }
};
const transcript = props.meetings.info?.transcript_json || [];

const speakers = Array.isArray(transcript?.speaker_array)
    ? transcript?.speaker_array
    : [];

const selectedSpeaker = ref('');

const filteredTranscript = computed(() => {
    if (!selectedSpeaker.value) {
        return transcript.data;
    }
    return transcript.data.filter(item => item.speaker === selectedSpeaker.value);
});

const showFullView = ref(true);
</script>

<template>
    <Head title="Meeting" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl bg-white p-6 dark:bg-gray-950">
            <div>
                <h1 class="text-3xl font-bold text-blue-600 dark:text-white">Meeting: {{ meeting.title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 my-4">Meeting By: {{ meeting.user?.name }} — Email: {{ meeting.user?.email }}</p>
            </div>

            <form
                v-if="!meeting.info?.transcript_json"
                @submit.prevent="submitComment"
                class="grid gap-4 md:grid-cols-3"
            >
                <div class="col-span-full flex flex-col items-start gap-2">
                    <label class="block mb-2 font-semibold">Status Model</label>
                    <div
                        :class="['text-sm my-2', form.statusType === 'success' ? 'text-green-600' : 'text-red-600']"
                    >
                        {{ form.statusMessage ? form.statusMessage : 'Not Connecting Server Please Upload File.'}}
                    </div>

                    <label class="block mb-2 font-semibold">Select Video File</label>
                    <input
                        type="file"
                        name="file"
                        @change="handleFileChange"
                        class="mt-2 w-full rounded-md border px-4 py-2 focus:ring-1 focus:ring-blue-600 focus:outline-none"
                    />

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                              'flex items-center gap-2 rounded-md px-4 py-2 text-white transition-colors duration-300 my-4',
                              form.processing ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
                            ]">
                        {{ form.processing ? 'Loading...' : 'Upload' }}
                        <component
                            :is="form.processing ? Loader : FileUpIcon"
                            class="h-5 w-5"
                        />
                    </button>

                    <p v-if="form.errors.video" class="text-sm text-red-600">
                        {{ form.errors.video }}
                    </p>
                </div>
            </form>
            
            <div class="relative flex min-h-[400px] flex-col gap-4 rounded-xl border border-gray-200 p-6 dark:border-gray-700 ">
                <button
                    @click="showFullView = !showFullView"
                    class="absolute right-4 top-4 inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-blue-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                >
                    {{ showFullView ? 'Transcript': 'Summaries'}}
                    <component
                        :is="showFullView ? ListOrderedIcon : Lightbulb"
                        class="h-5 w-5"
                    />
                </button>
                
                <h2 class="text-lg font-semibold text-blue-600 dark:text-white">{{ showFullView ? " Meeting Summary :" : "Meeting Transcript:"}}</h2>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ meeting.info?.description }}
                </p>

                <div v-if="meetings.info?.transcript_json?.data?.length && !showFullView" class="mt-6">
                    <div class="flex flex-col sm:flex-row gap-6 w-full h-[750px]">
                        <TranscriptPanel
                            :speakers="speakers"
                            v-model:selectedSpeaker="selectedSpeaker"
                            :filteredTranscript="filteredTranscript"
                            :ListFilterIcon="ListFilterIcon"
                            :videoPath="meetings.info?.transcript_json?.video_path"
                        />
                    </div>
                </div>

                <div v-else-if="meetings.info?.transcript_json?.data?.length && showFullView" class="mt-6 space-y-3 text-base leading-relaxed text-gray-800 dark:text-gray-200  rounded-md p-4">
                    <div class="flex flex-wrap justify-between gap-4 mb-4">
                        <MeetingStats
                            :numSpeakers="meeting.info?.transcript_json.num_speakers"
                            :totalSentence="meeting.info?.transcript_json.total_sentence"
                        />
                        <SpeakerCountList
                            :countSpeaker="meeting.info?.transcript_json.count_speaker"
                        />
                    </div>
                    <MeetingSummaries
                        :summaries="meetings.info?.transcript_json?.summaries"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>