<template>
  <div class="rounded-xl border border-gray-200 p-8 shadow-sm bg-white w-full ">
    <h2 class="text-lg font-semibold text-blue-600 mb-4">Meeting Summaries:</h2>
    <div v-for="(item, index) in summaries" :key="index" >
      <p> {{ item }}</p>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ summaries: string[] }>()
</script> 