<template>
  <div class="w-full sm:w-2/3 max-w-3xl aspect-video bg-black rounded-lg overflow-hidden shadow-lg mb-4 flex items-center justify-center">
    <template v-if="videoPath.value">
      <video ref="meetingVideo" controls class="w-full h-full object-contain">
        <source :src="videoPath" type="video/mp4">
        Your browser does not support the video tag.
      </video>
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
    <div v-if="filteredTranscript.length" class="space-y-2 overflow-y-auto">
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
          <p class="truncate">
            <strong>{{ item.speaker }}</strong> : {{ item.start }}s - {{ item.end }}s
          </p>
          <p class="break-words">{{ item.text }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const meetingVideo = ref<HTMLVideoElement | null>(null);

interface TranscriptItem {
  start: number | string;
  end: number | string;
  speaker: string;
  filename?: string;
  text: string;
}

const emit = defineEmits(['update:selectedSpeaker']);

defineProps({
  speakers: {
    type: Array as () => string[],
    required: true
  },
  selectedSpeaker: {
    type: String,
    required: true
  },
  filteredTranscript: {
    type: Array as () => TranscriptItem[],
    required: true
  },
  ListFilterIcon: {
    type: [Object, Function, String],
    required: true
  },
  videoPath: {
    type: String,
    required: true
  }
});

function jumpToTime(time: number | string) {
  if (meetingVideo.value) {
    meetingVideo.value.currentTime = Number(time);
    meetingVideo.value.play();
  }
}

function onSelectSpeaker(event: Event) {
  const target = event.target as HTMLSelectElement | null;
  if (target) {
    emit('update:selectedSpeaker', target.value);
  }
}
</script> 