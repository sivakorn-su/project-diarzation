<template>
  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm bg-white dark:bg-gray-900 w-full sm:w-60">
    <div class="my-4">
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
        จำนวนคนที่พูดในวิดีโอ
      </p>
      <h1 class="text-3xl font-semibold text-blue-600 dark:text-blue-400">
        ~ {{ numSpeakers ?? '-' }}
        <span class="text-base font-normal"> คน</span>
      </h1>
    </div>
    <div>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
        จำนวนประโยคที่พูดในวิดีโอ
      </p>
      <h1 class="text-3xl font-semibold text-blue-600 dark:text-blue-400">
        ~ {{ totalSentence ?? '-' }}
        <span class="text-base font-normal"> ประโยค</span>
      </h1>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ numSpeakers: number|null|undefined, totalSentence: number|null|undefined }>()
</script> 