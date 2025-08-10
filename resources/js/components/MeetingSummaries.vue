<template>
  <div class="  bg-white w-full ">
    <div v-for="(item, index) in summaries" :key="index" >
      <p> {{ item }}</p>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ summaries: string[] }>()
</script> 