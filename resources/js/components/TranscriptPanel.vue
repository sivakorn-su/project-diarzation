<template>
  <!-- Media panel -->
  <div class="w-full sm:w-2/3 max-w-3xl aspect-video bg-black dark:bg-gray-900 rounded-lg overflow-hidden mb-4 flex items-center justify-center border border-gray-200 dark:border-gray-700">
    <template v-if="videoPath?.length">
      <template v-if="mediaType === 'video'">
        <video ref="meetingVideo" class="video-js vjs-default-skin w-full h-full object-contain" controls preload="auto"></video>
      </template>
      <template v-else-if="mediaType === 'audio'">
        <audio ref="meetingVideo" controls class="w-2/3">
          <source :src="videoPath" :type="audioMime"/>
          Your browser does not support the audio element.
        </audio>
      </template>
    </template>
    <template v-else>
      <div class="flex flex-col items-center justify-center w-full h-full text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mb-2 text-gray-400 drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <rect x="3" y="5" width="18" height="14" rx="3" fill="currentColor" fill-opacity="0.1"/>
          <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.5"/>
          <polygon points="10,9 16,12 10,15" fill="currentColor" fill-opacity="0.5"/>
        </svg>
        <span class="text-base text-gray-500 dark:text-gray-400">No media available</span>
      </div>
    </template>
  </div>

  <!-- Toolbar -->
  <div class="flex-1 flex flex-col">
    <div class="flex flex-wrap items-center gap-3 mb-3">
      <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <component :is="ListFilterIcon" class="h-5 w-5 text-gray-500" />
        <select
          id="speaker-select"
          :value="selectedSpeaker"
          @change="onSelectSpeaker"
          class="min-w-[220px] rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 py-2 px-3 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
        >
          <option value="">All Speakers</option>
          <option v-for="sp in speakers" :key="sp" :value="sp">{{ sp }}</option>
        </select>
      </div>

      <button
        @click="exportDocx"
        class="ml-auto inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
        type="button"
        title="Export transcript as DOCX"
      >
        <FileUpIcon class="w-4 h-4" />
        Export
      </button>
    </div>

    <!-- Transcript list -->
    <div v-if="transcript_json?.data.length" class="space-y-2 overflow-y-auto max-h-[70vh] p-2 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
      <div
        v-for="row in displayList"
        :key="`${row.item.id}`"
        :class="[
          'group rounded-xl border p-4 shadow-sm flex items-start gap-3 cursor-pointer bg-white dark:bg-gray-900 hover:bg-slate-50 dark:hover:bg-gray-800 transition',
          isCurrent(row.item) ? 'ring-1 ring-sky-500/40 border-sky-300 dark:border-sky-700' : 'border-gray-200 dark:border-gray-700'
        ]"
        @click="jumpToTime(row.item.start)"
      >
        <img src="/avatar-boy-svgrepo-com.svg" alt="Avatar" class="w-8 h-8 rounded-full object-cover" />

        <div class="flex-1 overflow-hidden">
          <div class="flex items-start justify-between mb-2">
            <p class="truncate">
              <strong>{{ row.item.speaker }}</strong> :
              {{ row.item.start }}s - {{ row.item.end }}s
            </p>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span v-if="row.item.avg_probability != null" :class="probBadge(row.item.avg_probability)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border">
                {{ Math.round(Number(row.item.avg_probability) * 100) }}%
              </span>
              <div class="flex gap-1">
                <button @click.stop="openEditModal(row.item)" class="text-gray-400 hover:text-blue-500">
                  <PencilIcon class="h-4 w-4" />
                </button>
                <button @click.stop="openDeleteModal(row.item)" class="text-gray-400 hover:text-red-500">
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
          <p class="break-words leading-relaxed text-gray-800 dark:text-gray-200">{{ row.item.text }}</p>
        </div>
      </div>

      <div class="flex items-center justify-center gap-3 py-4" v-if="!displayList.length">
        <span class="text-sm text-gray-500">No segments</span>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center w-full">
      <button
        class="w-42 my-8 aspect-square rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow hover:bg-gray-100 dark:hover:bg-gray-800 transition disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
        @click="reTranscript"
        :disabled="loading"
        type="button"
      >
        <div v-if="loading" class="flex flex-col items-center justify-center gap-2 text-sm text-blue-600">
          <svg class="w-10 h-10 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
          </svg>
          <span>Processing</span>
        </div>
        <div v-else class="flex flex-col items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-200">
          <RefreshCcw class="h-8 w-8 text-gray-400" />
          <span>Transcript</span>
        </div>
      </button>
      <transition name="fade">
        <div v-if="success" class="mt-3 text-green-600 text-sm font-medium">Transcript updated!</div>
      </transition>
      <transition name="fade">
        <div v-if="error" class="mt-3 text-red-600 text-sm font-medium">{{ error }}</div>
      </transition>
    </div>
  </div>

  <!-- Edit Modal -->
  <Modal v-if="editOpen" @close="closeEditModal">
    <template #body>
      <div class="relative w-full max-w-[720px] mx-auto rounded-2xl bg-white dark:bg-gray-900 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2 ">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Segment</h3>
          <span v-if="editModel.avg_probability != null" :class="probBadge( editModel.avg_probability)" class="px-2.5 py-0.5 rounded-full text-xs font-medium border">
                {{ Math.round(Number( editModel.avg_probability) * 100) }}%
          </span>
          </div>
          <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="closeEditModal">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-blue-500">Speaker</label>
            <input v-model="editModel.speaker" class="mt-1 w-full rounded border px-3 py-2 text-sm bg-sky-50 border-sky-500" />
          </div>
          <div class="flex-row md:flex justify-between gap-3">
            <div>
            <label class="text-xs text-blue-500">Start (s)</label>
            <input v-model="editModel.start" type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2 text-sm bg-sky-50 border-sky-500" />
          </div>
          <div>
            <label class="text-xs text-blue-500">End (s)</label>
            <input v-model="editModel.end" type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2 text-sm bg-sky-50 border-sky-500" />
          </div>
          </div>
          <div class="md:col-span-2">
            <label class="text-xs text-green-500">LLM Suggested</label>
            <textarea  disabled v-model="editModel.llm_corrected_text" rows="4" class="mt-1 h-fit w-full rounded border px-3 py-2 text-sm bg-green-50 border-green-500"></textarea>
          </div>
          <div class="md:col-span-2">
            <label class="text-xs text-blue-500">Text</label>
            <textarea v-model="editModel.text" rows="4" class="mt-1 h-fit w-full rounded border px-3 py-2 text-sm bg-sky-50 border-sky-500"></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
          <button class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200" @click="closeEditModal">Cancel</button>
          <button :disabled="formEdit.processing" class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white" @click="saveEdit">
            {{ formEdit.processing ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </div>
    </template>
  </Modal>

  <!-- Delete Modal -->
  <Modal v-if="deleteOpen" @close="closeDeleteModal">
    <template #body>
      <div class="relative w-full max-w-[520px] mx-auto rounded-2xl bg-white dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Segment</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300">ยืนยันลบ segment นี้หรือไม่? การลบไม่สามารถย้อนกลับได้</p>
        <div class="mt-5 flex justify-end gap-2">
          <button class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200" @click="closeDeleteModal">Cancel</button>
          <button :disabled="formDelete.processing" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white" @click="confirmDelete">
            {{ formDelete.processing ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch, PropType, onMounted, onBeforeUnmount, watchEffect } from 'vue';
import { RefreshCcw, FileUpIcon, PencilIcon, TrashIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';

const emit = defineEmits(['update:selectedSpeaker']);

const props = defineProps({
  speakers: { type: Array as PropType<string[]>, required: true },
  selectedSpeaker: { type: String, required: true },
  ListFilterIcon: { type: [Object, Function, String], required: true },
  videoPath: { type: String, required: true },
  meetingId: { type: [String, Number], required: true },
  transcript_json: {
    type: Object as PropType<{
      data: Array<{
        id: string;
        start: number | string;
        end: number | string;
        speaker: string;
        filename: string;
        text: string;
        avg_probability?: number | string;
        llm_corrected_text?: string;
        has_overlap?: boolean;
      }>;
      count_speaker?: Array<{ speaker: string; count: number | string }>;
      summaries?: string[];
      video_path?: string;
      audio_path?: string;
      audio_length?: number;
      num_speakers?: number;
      speaker_array?: string[];
      total_sentence?: number;
    }>,
    required: false
  }
});

/* ===== Player / Time highlight ===== */
const meetingVideo = ref<HTMLVideoElement | null>(null);
let player: any = null;
const currentTime = ref(0);

const mediaType = computed(() => {
  if (!props.videoPath) return '';
  const ext = props.videoPath.split('.').pop()?.toLowerCase();
  if (['mp4','webm','ogg'].includes(ext || '')) return 'video';
  if (['mp3','wav','aac','m4a','flac'].includes(ext || '')) return 'audio';
  return '';
});

const audioMime = computed(() => {
  const ext = props.videoPath.split('.').pop()?.toLowerCase();
  const map: Record<string,string> = { mp3:'audio/mpeg', wav:'audio/wav', aac:'audio/aac', m4a:'audio/mp4', flac:'audio/flac' };
  return map[ext || 'mp3'] || 'audio/mpeg';
});

onMounted(() => {
  watchEffect(() => {
    if (mediaType.value === 'video' && meetingVideo.value) {
      if (player) { player.dispose(); player = null; }
      player = videojs(meetingVideo.value, {
        controls: true, autoplay: false, preload: 'auto',
        sources: [{ src: props.videoPath, type: 'video/mp4' }],
      });
      player.on('timeupdate', () => { currentTime.value = player.currentTime(); });
    }
  });
});

onBeforeUnmount(() => { if (player) { player.dispose(); player = null; } });

watch(() => props.videoPath, (newPath) => {
  if (mediaType.value === 'video' && player && newPath) {
    player.src({ src: newPath, type: 'video/mp4' });
    player.load();
  }
});

function jumpToTime(time: number | string) {
  const seekTime = Number(time);
  if (Number.isNaN(seekTime)) return;
  if (mediaType.value === 'video') {
    if (!player) return;
    player.currentTime(seekTime);
    player.play().catch(() => {});
    return;
  }
  if (!meetingVideo.value) return;
  const media = meetingVideo.value as HTMLMediaElement;
  const go = () => { media.currentTime = seekTime; media.play().catch(()=>{}); };
  (media.readyState >= 1) ? go() : media.addEventListener('loadedmetadata', () => { go(); }, { once:true });
}

function isCurrent(item: { start: number|string; end: number|string }) {
  const now = Number(currentTime.value);
  const s = Number(item.start), e = Number(item.end);
  return Number.isFinite(now) && now >= s && now <= e;
}

/* ===== Dataset & filtering ===== */
type Seg = {
  id: string;
  start: number | string;
  end: number | string;
  speaker: string;
  filename: string;
  text: string;
  avg_probability?: number | string;
  llm_corrected_text?: string;
};

const masterTranscript = ref<Seg[]>([]);

const rawData = computed(() => {
  const tj = props.transcript_json as any;
  if (!tj) return [];
  if (Array.isArray(tj.data)) return tj.data;
  if (typeof tj === 'string') {
    try {
      const obj = JSON.parse(tj);
      return Array.isArray(obj?.data) ? obj.data : [];
    } catch { return []; }
  }
  return [];
});

watchEffect(() => {
  const all = rawData.value ?? [];
  masterTranscript.value = all.map(s => ({ ...s }));
});

const displayList = computed(() => {
  const want = (props.selectedSpeaker || '').trim();
  const rows: { item: Seg; idx: number }[] = [];
  masterTranscript.value.forEach((item, idx) => {
    if (!want || item.speaker === want) rows.push({ item, idx });
  });
  return rows;
});

/* ===== Edit in Modal (PATCH per segment) ===== */
const editOpen = ref(false);
const editModel = reactive<{
  id: string;
  start: string | number;
  end: string | number;
  speaker: string;
  filename: string;
  text: string;
  avg_probability?: string | number;
  llm_corrected_text?: string;
}>({
  id: '',
  start: '',
  end: '',
  speaker: '',
  filename: '',
  text: '',
  avg_probability: '',
  llm_corrected_text: ''
});

function openEditModal(seg: Seg) {
  editModel.id = seg.id;
  editModel.start = seg.start;
  editModel.end = seg.end;
  editModel.speaker = seg.speaker;
  editModel.text = seg.text;
  editOpen.value = true;
}
function closeEditModal() { editOpen.value = false; }

const formEdit = useForm({
  start: '', end: '', speaker: '', filename: '', text: ''
});

async function saveEdit() {
  // map model -> form payload
  formEdit.start = String(editModel.start);
  formEdit.end = String(editModel.end);
  formEdit.speaker = editModel.speaker;
  formEdit.text = editModel.text;
  console.log('Saving edit:',editModel.id);
  // PATCH /meetings/{meetingId}/transcript/segments/{id}
  await formEdit.put(`/meetings/${props.meetingId}/transcript/segments/${editModel.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      // optimistic update in local list
      const i = masterTranscript.value.findIndex(s => s.id === editModel.id);
      if (i !== -1) {
        masterTranscript.value[i] = {
          ...masterTranscript.value[i],
          start: editModel.start,
          end: editModel.end,
          speaker: editModel.speaker,
          filename: editModel.filename,
          text: editModel.text,
          avg_probability: editModel.avg_probability as any,
          llm_corrected_text: editModel.llm_corrected_text
        };
      }
      closeEditModal();
    }
  });
}

/* ===== Delete with Modal (DELETE per segment) ===== */
const deleteOpen = ref(false);
const deleteTargetId = ref<string>('');

function openDeleteModal(seg: Seg) {
  deleteTargetId.value = seg.id;
  deleteOpen.value = true;
}
function closeDeleteModal() { deleteOpen.value = false; }

const formDelete = useForm({});

async function confirmDelete() {
  const id = deleteTargetId.value;
  if (!id) return;

  await formDelete.delete(`/meetings/${props.meetingId}/transcript/segments/${encodeURIComponent(id)}`, {
    preserveScroll: true,
    onSuccess: () => {
      const i = masterTranscript.value.findIndex(s => s.id === id);
      if (i !== -1) masterTranscript.value.splice(i, 1);
      closeDeleteModal();
    }
  });
}

/* ===== Other actions ===== */
const form = useForm({}); // for reTranscript only
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref(false);

function onSelectSpeaker(e: Event) {
  const target = e.target as HTMLSelectElement | null;
  if (target) emit('update:selectedSpeaker', target.value);
}

const reTranscript = () => {
  loading.value = true; 
  error.value = null; 
  success.value = false;
  form.post(`/meetings/${props.meetingId}/transcript`, {
    preserveScroll: true,
    onSuccess: () => { success.value = true; },
    onError: (errors: any) => { error.value = errors?.error || 'Unknown error'; },
    onFinish: () => { loading.value = false; }
  });
};

function exportDocx() {
  window.open(`/meetings/${props.meetingId}/transcript/export-docx`, '_blank');
}

/* UI helpers */
function probBadge(p: number | string) {
  const v = Number(p);
  if (!Number.isFinite(v)) return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700';
  if (v <= 0.25) return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800';
  if (v <= 0.50) return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-800';
  if (v <= 0.75) return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800';
  return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800';
}
</script>
