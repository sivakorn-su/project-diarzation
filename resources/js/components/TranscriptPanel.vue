<template>
  <div class="w-full sm:w-2/3 max-w-3xl aspect-video bg-white rounded-lg overflow-hidden  mb-4 flex items-center justify-center">
    <template v-if="videoPath.length">
      <template v-if="mediaType === 'video'">
        <video
          ref="meetingVideo"
          class="video-js vjs-default-skin w-full h-full object-contain"
          controls
          
          preload="auto"
        ></video>
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
   <div class="flex flex-row max-w-sm items-center justify-start gap-4 mb-2">
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
      <button
          @click="exportDocx"
          class="flex items-center justify-center ml-auto text-gray-400 hover:text-blue-600"
          :title="'Export transcript as DOCX'"
          type="button"
        >
          <component :is="FileUpIcon" class="w-5 h-5" />
        </button>
   </div>
    <div v-if="props.transcript_json" class="space-y-2 overflow-y-auto p-4">
      <div  class="flex flex-row items-center gap-4 justify-between rounded-md max-w-xs mb-4">
    </div>
      <div
        v-for="(item, index) in filteredTranscript"
        :key="index"
        :class="[
          'rounded-xl border p-4 shadow flex items-center gap-3 cursor-pointer hover:bg-slate-50 ',
          mediaType === 'video' && Number(currentTime) >= Number(item.start) && Number(currentTime) <= Number(item.end) ? 'border-l-4 border-transparent border-l-sky-500' : ''
        ]"
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
              <textarea disabled v-model="editItem.text" class="break-words px-2 py-1 border border-green-200 p-1 my-2 text-sm rounded-lg inline-block text-green-700 bg-green-50 "></textarea>           
              <textarea v-model="editItem.text" class="border rounded px-2 py-1" placeholder="Text"></textarea>
              <div class="flex gap-2 mt-2">
                <button @click.stop="saveEdit(index)" class="px-2 py-1 bg-sky-500 text-white rounded">Save</button>
                <button @click.stop="cancelEdit" class="px-2 py-1 bg-gray-300 rounded ">Cancel</button>
              </div>
            </div>
          </template>
          <template v-else>
            <div class="flex items-start justify-between mb-2">
        <p class="truncate">
          <strong>{{ item.speaker }}</strong> : {{ item.start }}s - {{ item.end }}s
        </p>
        <div class="flex items-center gap-2 flex-shrink-0">
          <span                  
            v-if="item.probability"                 
            :class="{                   
              'bg-red-100 text-red-800 border-red-200': item.probability <= 0.25,                   
              'bg-yellow-100 text-yellow-800 border-yellow-200': item.probability > 0.25 && item.probability <= 0.50,                   
              'bg-green-100 text-green-800 border-green-200': item.probability > 0.50 && item.probability <= 0.75,                   
              'bg-blue-100 text-blue-800 border-blue-200': item.probability > 0.75                 
            }"                 
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"               
          >                 
            {{ Math.round(item.probability * 100) }}%               
          </span>
          <div class="flex gap-1">
            <button @click.stop="startEdit(index, item)" class="text-gray-400 hover:text-blue-600">
              <component :is="Pencil" class="w-4 h-4" />
            </button>           
            <button @click.stop="deleteItem(index)" class="text-gray-400 hover:text-red-600">
              <component :is="Trash" class="w-4 h-4" />
            </button>         
          </div>
        </div>
      </div>
      <p class="break-words my-2">{{item.text}}</p>             
          </template>
        </div>
        <!-- <div class="flex flex-row gap-2 ml-2">
          <button @click.stop="startEdit(index, item)" class="text-gray-400 hover:text-blue-600"><component :is="Pencil" class="w-5 h-5" /></button>
          <button @click.stop="deleteItem(index)" class="text-gray-400 hover:text-red-600"><component :is="Trash" class="w-5 h-5" /></button>
        </div> -->
      </div>
    </div>
    <div v-else>
      <div class="flex flex-col items-center w-full">
  <button
    class="w-42 my-8 aspect-square rounded-lg border border-gray-300  bg-white  shadow hover:bg-gray-100  transition disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
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
      <component :is="RefreshCcw" class="h-8 w-8 text-gray-400" />
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch, PropType, onMounted, onBeforeUnmount, watchEffect } from 'vue';
import { RefreshCcw, Pencil, Trash, FileUpIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';

const meetingVideo = ref<HTMLVideoElement | null>(null);
let player: any = null;
const currentTime = ref(0);

interface TranscriptItem {
  start: number | string;
  end: number | string;
  speaker: string;
  filename: string;
  text: string;
}

const emit = defineEmits(['update:selectedSpeaker']);

const props = defineProps({
  speakers: {
    type: Array as PropType<string[]>,
    required: true
  },
  selectedSpeaker: {
    type: String,
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
  },
  transcript_json: {
    type: Object as PropType<{
      data: Array<{
        start: number | string;
        end: number | string;
        speaker: string;
        filename: string;
        text: string;
      }>;
      count_speaker: Array<{
        speaker: string;
        count: number | string;
      }>;
      summaries: string[];
      video_path: string;
      num_speakers: number;
      speaker_array: string[];
      total_sentence: number;
    }>,
    required: false
  }
});

const mediaType = computed(() => {
  if (!props.videoPath) return '';
  const ext = props.videoPath.split('.').pop()?.toLowerCase();
  if (["mp4", "webm", "ogg"].includes(ext || "")) return "video";
  if (["mp3", "wav", "aac", "m4a", "flac"].includes(ext || "")) return "audio";
  return "";
});

onMounted(() => {
  watchEffect(() => {
    if (mediaType.value === 'video' && meetingVideo.value) {
      if (player) {
        player.dispose();
        player = null;
      }
      player = videojs(meetingVideo.value, {
        controls: true,
        autoplay: false,
        preload: 'auto',
        sources: [
          {
            src: props.videoPath,
            type: 'video/mp4',
          },
        ],
      });
      player.on('timeupdate', () => {
        currentTime.value = player.currentTime();
      });
    }
  });
});

onBeforeUnmount(() => {
  if (player) {
    player.dispose();
    player = null;
  }
});

watch(() => props.videoPath, (newPath) => {
  if (mediaType.value === 'video' && player && newPath) {
    player.src({ src: newPath, type: 'video/mp4' });
    player.load();
  }
});

function jumpToTime(time: number | string) {
  if (mediaType.value === 'video') {
    if (!player) return console.error('No video.js player found');
    const seekTime = Number(time);
    if (Number.isNaN(seekTime)) return console.error('Invalid time', time);
    player.currentTime(seekTime);
    player.play().catch((e: unknown) => {
      console.error('Autoplay blocked or other error:', e);
    });
    return;
  }
  // fallback for audio
  if (!meetingVideo.value) return console.error('No media element found');
  const media = meetingVideo.value as HTMLMediaElement;
  const seekTime = Number(time);
  if (Number.isNaN(seekTime)) return console.error('Invalid time', time);
  if (media.readyState >= 1) {
    media.currentTime = seekTime;
    media.play().catch((e: unknown) => {
      console.error('Autoplay blocked or other error:', e);
    });
  } else {
    const onLoadedMetadata = () => {
      media.currentTime = seekTime;
      media.play().catch((e: unknown) => {
        console.error('Autoplay blocked after loadedmetadata:', e);
      });
      media.removeEventListener('loadedmetadata', onLoadedMetadata);
    };
    media.addEventListener('loadedmetadata', onLoadedMetadata);
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
const editItem = reactive<TranscriptItem>({
  start: '',
  end: '',
  speaker: '',
  filename: '',
  text: ''
});

const filteredTranscript = computed(() => {
  if (!props.transcript_json?.data || !Array.isArray(props.transcript_json?.data)) return [];
  if (!props.selectedSpeaker) {
    return props.transcript_json?.data;
  }
  return props.transcript_json?.data.filter((item: { speaker: string }) => item.speaker === props.selectedSpeaker);
});

const localTranscript = ref([...filteredTranscript.value]);

watch(filteredTranscript, (newVal) => {
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
    localTranscript.value[index] = { ...editItem, filename: editItem.filename ?? '' };
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
  form.post(`/meetings/${props.meetingId}/transcript`, {
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

function exportDocx() {
  const url = `/meetings/${props.meetingId}/transcript/export-docx`;
  window.open(url, '_blank');
}
</script> 