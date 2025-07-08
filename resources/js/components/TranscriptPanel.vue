<template>
  <div class="w-full sm:w-2/3 max-w-3xl aspect-video bg-white rounded-lg overflow-hidden  mb-4 flex items-center justify-center">
    <template v-if="videoPath.length">
      <template v-if="mediaType === 'video'">
        <video ref="meetingVideo" controls class="w-full h-full object-contain">
          <source :src="videoPath" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      </template>
      <template v-else-if="mediaType === 'audio'">
        <audio ref="meetingVideo" controls class="w-2/3">
          <source :src="videoPath" type="audio/mpeg">
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
        <span class="text-base text-gray-500 dark:text-gray-400">No video available</span>
      </div>
    </template>
  </div>
  <div class="flex-1 flex flex-col">
    <div class="flex items-center gap-2 rounded-md max-w-xs mb-4">
      <component
        :is="ListFilterIcon"
        class="h-5 w-5 text-gray-500"
      />
      <select
        id="speaker-select"
        :value="selectedSpeaker"
        @change="onSelectSpeaker"
        class="flex-1 rounded-md border border-gray-300 bg-white py-2 px-3 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
      >
        <option value="">
          Sort by All Speakers
        </option>
        <option v-for="speaker in speakers" :key="speaker" :value="speaker">
          {{ speaker }}
        </option>
      </select>
    </div>
    <div v-if="filteredTranscript.length" class="space-y-2 overflow-y-auto p-4">
      <div
        v-for="(item, index) in filteredTranscript"
        :key="index"
        class="rounded-md border p-4 shadow flex items-center gap-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800"
        @click="jumpToTime(item.start)"
      >
        <img
          src="/avatar-boy-svgrepo-com.svg"
          alt="Avatar"
          class="w-8 h-8 rounded-full object-cover"
        />
        <div class="flex-1 overflow-hidden">
          <template v-if="editingIndex === index">
            <div class="flex flex-col gap-2">
              <input v-model="editItem.speaker" class="border rounded px-2 py-1" placeholder="Speaker" />
              <div class="flex gap-2">
                <input v-model="editItem.start" class="border rounded px-2 py-1 w-20" placeholder="Start" />
                <input v-model="editItem.end" class="border rounded px-2 py-1 w-20" placeholder="End" />
              </div>
              <textarea v-model="editItem.text" class="border rounded px-2 py-1" placeholder="Text"></textarea>
              <div class="flex gap-2 mt-2">
                <button @click.stop="saveEdit(index)" class="px-2 py-1 bg-blue-500 text-white rounded">Save</button>
                <button @click.stop="cancelEdit" class="px-2 py-1 bg-gray-300 rounded">Cancel</button>
              </div>
            </div>
          </template>
          <template v-else>
            <p class="truncate">
              <strong>{{ item.speaker }}</strong> : {{ item.start }}s - {{ item.end }}s
            </p>
            <p class="break-words">{{ item.text }}</p>
          </template>
        </div>
        <div class="flex flex-row gap-2 ml-2">
          <button @click.stop="startEdit(index, item)" class="text-gray-400 hover:text-blue-600"><component :is="Pencil" class="w-5 h-5" /></button>
          <button @click.stop="deleteItem(index)" class="text-gray-400 hover:text-red-600"><component :is="Trash" class="w-5 h-5" /></button>
        </div>
      </div>
    </div>
    <div v-else>
      <div class="flex flex-col items-center w-full">
        <button
          class="w-full max-w-xs md:max-w-md rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow flex items-center justify-center gap-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition disabled:opacity-50 font-semibold text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
          @click="reTranscript"
          :disabled="loading"
          type="button"
        >
          <span class="flex items-center gap-2">
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="w-5 h-5 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
              Processing...
            </span>
            <span v-else class="flex flex-col-1 items-center gap-2">
              <component :is="RefreshCcw" class="h-5 w-5" />
              <span>Transcript</span>
            </span>
          </span>
        </button>
        <transition name="fade">
          <div v-if="success" class="mt-3 text-green-600 text-sm font-medium">Transcript updated!</div>
        </transition>
        <transition name="fade">
          <div v-if="error" class="mt-3 text-red-600 text-sm font-medium">{{ error }}</div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue';
import { RefreshCcw, Pencil, Trash } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
const meetingVideo = ref<HTMLVideoElement | HTMLAudioElement | null>(null);

interface TranscriptItem {
  start: number | string;
  end: number | string;
  speaker: string;
  filename?: string;
  text: string;
}

const emit = defineEmits(['update:selectedSpeaker']);

const props = defineProps({
  speakers: {
    type: Array as () => string[],
    required: true
  },
  selectedSpeaker: {
    type: String,
    required: true
  },
  filteredTranscript: {
    type: Array as () => {
      start: number | string;
      end: number | string;
      speaker: string;
      filename?: string;
      text: string;
    }[],
    required: true
  },
  ListFilterIcon: {
    type: [Object, Function, String],
    required: true
  },
  videoPath: {
    type: String,
    required: true
  },
  meetingId: {
    type: [String, Number],
    required: true
  }
});

const mediaType = computed(() => {
  if (!props.videoPath) return '';
  const ext = props.videoPath.split('.').pop()?.toLowerCase();
  if (['mp4', 'webm', 'ogg'].includes(ext || '')) return 'video';
  if (['mp3', 'wav', 'aac', 'm4a', 'flac'].includes(ext || '')) return 'audio';
  return '';
});

function jumpToTime(time: number | string) {
  if (meetingVideo.value) {
    (meetingVideo.value as HTMLMediaElement).currentTime = Number(time);
    (meetingVideo.value as HTMLMediaElement).play();
  }
}

function onSelectSpeaker(event: Event) {
  const target = event.target as HTMLSelectElement | null;
  if (target) {
    emit('update:selectedSpeaker', target.value);
  }
}

const form = useForm({
  transcript_json: ''
});
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref(false);

const editingIndex = ref<number|null>(null);
const editItem = reactive({ start: '', end: '', speaker: '', text: '' });

const localTranscript = ref([...props.filteredTranscript]);

watch(() => props.filteredTranscript, (newVal) => {
  localTranscript.value = [...newVal];
});

function startEdit(index: number, item: TranscriptItem) {
  editingIndex.value = index;
  Object.assign(editItem, item);
}
function cancelEdit() {
  editingIndex.value = null;
}
async function saveEdit(index: number) {
  if (index !== null && localTranscript.value[index]) {
    localTranscript.value[index] = { ...editItem };
    editingIndex.value = null;
    await saveTranscript();
  }
}
async function deleteItem(index: number) {
  if (index !== null) {
    localTranscript.value.splice(index, 1);
    await saveTranscript();
  }
}
async function saveTranscript() {
  loading.value = true;
  error.value = null;
  success.value = false;

  if (!localTranscript.value.length) {
    error.value = 'Transcript cannot be empty.';
    loading.value = false;
    return;
  }

  form.transcript_json = JSON.stringify(localTranscript.value);

  form.put(`/meetings/${props.meetingId}/transcript/update`, {
    onSuccess: () => { success.value = true; },
    onError: (errors: any) => { error.value = errors?.error || 'Unknown error'; },
    onFinish: () => { loading.value = false; }
  });
}

const reTranscript = async () => {
  loading.value = true;
  error.value = null;
  success.value = false;
  form.post(`/meetings/${props.meetingId}/infos`, {
    preserveScroll: true,
    onSuccess: () => {
      success.value = true;
    },
    onError: (errors) => {
      error.value = errors?.error || 'Unknown error';
    },
    onFinish: () => {
      loading.value = false;
    },
  });
}
</script> 