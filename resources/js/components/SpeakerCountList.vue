<template>
  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm bg-white dark:bg-gray-900 flex-1 min-w-[300px] space-y-4">
    <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
      จำนวนครั้งที่แต่ละคนพูด
    </p>
    <div
      v-for="(item, index) in countSpeaker"
      :key="index"
      class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
    >
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm shadow">
          {{ item.speaker.split('_')[1] }}
        </div>
        <div class="flex flex-col">
          <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
            {{ item.speaker }}
          </span>
        </div>
      </div>
      <span class="text-sm text-gray-700 dark:text-gray-200 font-mono">
        {{ item.count }} ครั้ง
      </span>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ countSpeaker: { speaker: string, count: number|string }[] }>()
</script> 