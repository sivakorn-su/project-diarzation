<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Lightbulb, ListOrderedIcon, ListFilterIcon, FileUpIcon, Loader, 
  PlayCircle, UserRound, Mail, Calendar, Clock, Film, CheckCircle2, XCircle,
  UploadCloud, X, AlertCircle, Music, Users, PieChart,
  ClipboardListIcon,
  MessageSquareTextIcon
} from 'lucide-vue-next';
import Modal from '@/components/Modal.vue';

import TranscriptPanel from '@/components/TranscriptPanel.vue';
import MeetingStats from '@/components/MeetingStats.vue';
import SpeakerCountList from '@/components/SpeakerCountList.vue';
import MeetingSummaries from '@/components/MeetingSummaries.vue';

const props = defineProps<{
  meetings: {
    id: string;
    title: string;
    start_date: string;
    end_date: string;
    level: string;
    user?: { id: number; name: string; email: string; };
    info?: {
      description: string;
      media_paths: string;
      audio_path: string;
      audio_length: number;
      transcript_json: {
        data: { start: number|string; end: number|string; speaker: string; filename: string; text: string; avg_probability: number|string; llm_corrected_text: string; confidence: number|string; has_overlap?: boolean; tag?: string; }[];
        count_speaker: { speaker: string; count: number|string }[];
        summaries: string[]|string;
        video_path: string;
        num_speakers: number;
        speaker_array: string[];
        total_sentence: number;
      };
      status?: 'pending'|'processing'|'done'|'failed';
    };
  };
  authUser: any;
}>();

const meeting = props.meetings;

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Meeting', href: '/meetings' },
  { title: `Meeting: ${meeting.title}`, href: `/meeting/${meeting.id}` },
];

/* ========= Upload form state ========= */
const form = useForm({
  video: null as File|null,
  transcript: null as any,
  url: 'https://inwneon-project-voice-diarzation.hf.space',
  statusMessage: '',
  statusType: '',
});

// drag & drop helpers
const supportedFormats = ['MP3', 'WAV', 'FLAC', 'MP4', 'MOV', 'AVI'];
const fileInput = ref<HTMLInputElement | null>(null);
const isDragOver = ref(false);

const openFileDialog = () => fileInput.value?.click();

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  if (!file) { form.video = null; return; }
  form.video = file;
};

const handleDragOver = (e: DragEvent) => { e.preventDefault(); isDragOver.value = true; };
const handleDragLeave = () => { isDragOver.value = false; };
const handleDrop = (e: DragEvent) => {
  e.preventDefault(); isDragOver.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) form.video = file;
};
const removeFile = () => { form.video = null; if (fileInput.value) fileInput.value.value = ''; };
const getFileIcon = (file: File) => file.type.startsWith('audio/') ? Music : file.type.startsWith('video/') ? Film : FileUpIcon;
const getFileSize = (size: number) => { const kb = size/1024; return kb < 1024 ? `${kb.toFixed(1)} KB` : `${(kb/1024).toFixed(1)} MB`; };

const submitComment = async () => {
  form.processing = true; form.statusMessage = ''; form.statusType = '';
  if (!form.video) { alert('เลือกไฟล์ก่อนนะ'); form.processing = false; return; }
  if (!form.url)   { alert('กรอก URL ก่อน');     form.processing = false; return; }
  try {
    await form.post(`/meetings/${meeting.id}/infos`, {
      forceFormData: true,
      onSuccess: () => form.reset(),
      onError:   () => alert('อัปโหลดไม่สำเร็จ ลองใหม่อีกครั้ง'),
    });
  } catch (e) {
    console.error(e);
    alert('อัปโหลดไม่สำเร็จ ลองใหม่อีกครั้ง');
  } finally {
    form.processing = false;
  }
};

/* ========= View state ========= */
const transcript = (meeting.info?.transcript_json ?? null) as any;
const speakers = Array.isArray(transcript?.speaker_array) ? transcript!.speaker_array : [];
const selectedSpeaker = ref('');
const showFullView = ref(true);

const hasMedia = computed(() => !!meeting.info?.media_paths);
const hasTranscript = computed(() => {
  const data = meeting.info?.transcript_json?.data;
  return Array.isArray(data) && data.length > 0;
});

const statusBadge = computed(() => {
  const s = meeting.info?.status;
  if (!s) return { text: '—', cls: 'bg-gray-100 text-gray-600' };
  const map: Record<string, string> = {
    pending: 'bg-slate-100 text-slate-700',
    processing: 'bg-amber-100 text-amber-700',
    done: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-rose-100 text-rose-700',
  };
  return { text: s, cls: map[s] ?? 'bg-gray-100 text-gray-600' };
});

/* ========= Quick Stats ========= */
const tjson = computed(() => meeting.info?.transcript_json ?? null);
const segs = computed(() => Array.isArray(tjson.value?.data) ? tjson.value!.data : []);
const totalSegments = computed(() => segs.value.length);

const countList = computed(() => {
  if (Array.isArray(tjson.value?.count_speaker) && tjson.value!.count_speaker.length) {
    return tjson.value!.count_speaker.map((cs: any) => ({ speaker: String(cs.speaker), count: Number(cs.count) || 0 }));
  }
  const map = new Map<string, number>();
  for (const s of segs.value) map.set(s.speaker, (map.get(s.speaker) ?? 0) + 1);
  return Array.from(map.entries()).map(([speaker, count]) => ({ speaker, count }));
});

const totalSpeakers = computed(() => {
  if (typeof tjson.value?.num_speakers === 'number') return tjson.value!.num_speakers;
  return new Set(segs.value.map((s: any) => s.speaker)).size;
});

const mostActive = computed(() => {
  if (!countList.value.length) return { speaker: '—', count: 0 };
  return countList.value.reduce((a, b) => (b.count > a.count ? b : a));
});

const distributionTop3 = computed(() => {
  const total = totalSegments.value || 1;
  return countList.value
    .map(c => ({ speaker: c.speaker, count: c.count, percent: Math.round((c.count / total) * 100) }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 3);
});

// ทั้งหมดสำหรับ Modal
const distributionAll = computed(() => {
  const total = totalSegments.value || 1;
  return countList.value
    .map(c => ({ speaker: c.speaker, count: c.count, percent: Math.round((c.count / total) * 100) }))
    .sort((a, b) => b.count - a.count);
});

/* ========= Modal: Speaker Distribution detail ========= */
const speakerModalOpen = ref(false);
const openSpeakerModal = () => speakerModalOpen.value = true;
const closeSpeakerModal = () => speakerModalOpen.value = false;
</script>

<template>
  <Head title="Meeting" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 rounded-xl bg-white p-6 dark:bg-gray-950">

      <!-- Header card -->
      <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="space-y-1">
            <div class="flex items-center gap-2 text-xs">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                <Calendar class="h-4 w-4" /> {{ new Date(meeting.start_date).toLocaleDateString('th-TH') }}
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                <Clock class="h-4 w-4" /> {{ new Date(meeting.start_date).toLocaleTimeString('th-TH',{hour:'2-digit',minute:'2-digit',hour12:false}) }}
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                <Film class="h-4 w-4" /> {{ hasMedia ? 'มีไฟล์แนบ' : 'ไม่มีไฟล์' }}
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full" :class="statusBadge.cls">
                <component :is="(meeting.info?.status==='done')?CheckCircle2:(meeting.info?.status==='failed')?XCircle:Loader" class="h-4 w-4"/>
                {{ statusBadge.text }}
              </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-white">
              {{ meeting.title }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              <UserRound class="inline h-4 w-4" /> {{ meeting.user?.name ?? '-' }}
              • <Mail class="inline h-4 w-4" /> {{ meeting.user?.email ?? '-' }}
            </p>
          </div>
        </div>

        <div class="mt-4 text-gray-700 dark:text-gray-300">
          <div class="font-semibold mb-1">Description</div>
          <p class="leading-relaxed">{{ meeting.info?.description || '-' }}</p>
        </div>
      </div>
     
      <!-- Quick Stats -->
      <div v-if="hasTranscript" class="rounded-xl border border-gray-200 dark:border-gray-800 p-5 bg-white dark:bg-gray-950">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Stats</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">ภาพรวมสรุปจาก transcript ล่าสุด</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Most Active -->
          <div class="flex items-center gap-4 p-4 rounded-xl bg-sky-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
              <UserRound class="h-5 w-5 text-blue-600 dark:text-blue-300" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Most Active</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ mostActive.speaker }}
              </p>
            </div>
          </div>

          <!-- Total Speakers -->
          <div class="flex items-center gap-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
              <Users class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Total Speakers</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ totalSpeakers }}</p>
            </div>
          </div>

          <!-- Total Segments -->
          <div class="flex items-center gap-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-800 flex items-center justify-center">
              <ListOrderedIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Total Segments</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ totalSegments }}</p>
            </div>
          </div>

          <!-- Speaker Distribution (mini) -->
          <div
            class="bg-sky-50 rounded-xl border border-sky-200 p-4 cursor-pointer
                    hover:bg-sky-100 transition shadow-sm hover:shadow
                    focus:outline-none focus:ring-2 focus:ring-sky-300"
            role="button"
            tabindex="0"
            @click="openSpeakerModal"
            @keydown.enter.prevent="openSpeakerModal"
            @keydown.space.prevent="openSpeakerModal"
            >
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs font-semibold text-sky-700">
                Speaker Distribution
                </div>
                <div class="text-xs text-sky-600">
                Top 3
                </div>
            </div>

            <!-- Bars -->
            <div class="space-y-3 max-h-36 overflow-auto pr-1">
                <div v-for="d in distributionTop3" :key="d.speaker">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-medium text-sky-800 truncate">{{ d.speaker }}</span>
                    <span class="text-sky-600">{{ d.percent }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-white overflow-hidden">
                    <div
                    class="h-2 rounded-full bg-sky-500 transition-all"
                    :style="{ width: `${d.percent}%` }"
                    />
                </div>
                </div>
            </div>

            <!-- Footer hint (optional) -->
            <div class="mt-3 text-[11px] text-sky-600/80">
                Click to see all speakers
            </div>
</div>

        </div>

        <!-- Modal: Speaker detail -->
        <Modal v-if="speakerModalOpen" @close="closeSpeakerModal">
          <template #body>
            <div class="relative w-full max-w-[720px] mx-auto rounded-2xl bg-white dark:bg-gray-900 p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Speaker Distribution</h3>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="closeSpeakerModal">
                  <X class="h-5 w-5" />
                </button>
              </div>

              <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-1">
                <div v-for="d in distributionAll" :key="d.speaker" class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <UserRound class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                      <span class="font-medium text-gray-800 dark:text-gray-100">{{ d.speaker }}</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ d.count }} segments • {{ d.percent }}%
                    </div>
                  </div>
                  <div class="mt-2 w-full h-2 rounded bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-2 rounded bg-sky-500 dark:bg-sky-600" :style="{ width: `${d.percent}%` }" />
                  </div>
                </div>
              </div>

              <div class="mt-5 flex justify-end">
                <button class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm" @click="closeSpeakerModal">Close</button>
              </div>
            </div>
          </template>
        </Modal>
      </div>

      <!-- Upload card (Drag & Drop) -->
      <form v-if="!hasMedia" @submit.prevent="submitComment" class="space-y-6">
        <div>
          <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center">
              <UploadCloud class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Media File</h3>
            <span class="text-red-500 text-sm">*</span>
          </div>

          <div 
            @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop" @click="openFileDialog"
            :class="[
              'relative border-2 border-dashed rounded-2xl p-8 cursor-pointer transition-all duration-200',
              isDragOver 
                ? 'border-sky-400 bg-sky-50 dark:bg-sky-900/20' 
                : form.video 
                  ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-sky-300 hover:bg-sky-50/50 dark:hover:bg-sky-900/10',
              form.errors.video && 'border-red-300 bg-red-50 dark:bg-red-900/20'
            ]"
          >
            <input type="file" accept="audio/*,video/*" ref="fileInput" @change="handleFileChange" class="hidden" />
            <div v-if="!form.video" class="text-center">
              <div class="w-16 h-16 bg-sky-100 dark:bg-sky-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <UploadCloud class="h-8 w-8 text-sky-500 dark:text-sky-400" />
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Drop your file here or click to browse</h4>
              <p class="text-gray-500 dark:text-gray-400 mb-4">Upload audio or video files for transcription</p>
              <div class="flex flex-wrap gap-2 justify-center">
                <span v-for="format in supportedFormats" :key="format" class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded-lg">{{ format }}</span>
              </div>
            </div>
            <div v-else class="text-center">
              <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <component :is="getFileIcon(form.video)" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div class="mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ form.video.name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ getFileSize(form.video.size) }} • {{ form.video.type || 'Unknown format' }}</p>
              </div>
              <button type="button" @click.stop="removeFile" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-100 dark:bg-red-900/20 hover:bg-red-200 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 transition-all duration-200">
                <X class="h-4 w-4" /> Remove File
              </button>
            </div>
          </div>

          <div v-if="form.errors.video" class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mt-3">
            <AlertCircle class="h-4 w-4" /> {{ form.errors.video }}
          </div>
        </div>

        <div class="bg-sky-50 dark:bg-sky-900/10 rounded-xl p-6 border border-sky-200 dark:border-sky-800">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-sky-200 dark:bg-sky-800 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
              <AlertCircle class="h-4 w-4 text-sky-600 dark:text-sky-400" />
            </div>
            <div>
              <h4 class="font-semibold text-sky-900 dark:text-sky-100 mb-2">Processing Information</h4>
              <ul class="space-y-1 text-sm text-sky-700 dark:text-sky-300">
                <li>• Large files may take several minutes to process</li>
                <li>• You'll be notified when transcription is complete</li>
                <li>• Supported formats: Audio (MP3, WAV, FLAC) and Video (MP4, MOV, AVI)</li>
                <li>• Maximum file size: 500MB</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" :disabled="form.processing"
            :class="['inline-flex items-center gap-2 rounded-md px-4 py-2 text-white transition',
                     form.processing ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700']">
            <span>{{ form.processing ? 'กำลังอัปโหลด…' : 'อัปโหลด' }}</span>
            <component :is="form.processing ? Loader : FileUpIcon" class="h-5 w-5" />
          </button>
        </div>
      </form>

      <!-- Content card -->
      <div v-if="hasMedia" class="relative rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-blue-600 dark:text-white">
            {{ showFullView ? 'Meeting Transcript' : 'Meeting Summary' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ showFullView
                ? 'Full transcript with timestamps and speaker labels'
                : 'Key points and highlights from the meeting' }}
            </p>
            </div>
          
          <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <button type="button" @click="showFullView = true"
              :class="['px-3 py-1.5 text-sm transition',
                       showFullView ? 'bg-sky-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800']">
              <MessageSquareTextIcon class="inline h-4 w-4 mr-1" /> Transcript
            </button>
            <button type="button" @click="showFullView = false"
              :class="['px-3 py-1.5 text-sm transition border-l border-gray-200 dark:border-gray-700',
                       !showFullView ? 'bg-sky-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800']">
              <ClipboardListIcon class="inline h-4 w-4 mr-1" /> Summaries
            </button>
          </div>
        </div>

        <!-- Transcript view -->
        <div v-if="showFullView" class="mt-6">
          <div v-if="hasMedia" class="flex flex-col sm:flex-row gap-6 w-full min-h-[600px]">
            <TranscriptPanel
              :meeting-id="meeting.id"
              :speakers="speakers"
              v-model:selectedSpeaker="selectedSpeaker"
              :transcriptData="meeting.info!.transcript_json"
              :ListFilterIcon="ListFilterIcon"
              :videoPath="meeting.info?.media_paths"
              :transcript_json="meeting.info!.transcript_json"
            />
          </div>
          <!-- <div v-else class="mt-10 flex flex-col items-center justify-center text-gray-500">
            <PlayCircle class="h-10 w-10 mb-2" />
            <p class="text-sm">ยังไม่มี transcript — กดประมวลผลจากหลังบ้าน หรืออัปโหลดใหม่</p>
          </div> -->
        </div>

        <!-- Summaries view -->
        <div v-else class="mt-6 space-y-4">
          <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4">
            <MeetingSummaries :summaries="meeting.info?.transcript_json?.summaries" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
