<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';

const status = ref<'online' | 'offline' | 'unknown'>("unknown");
const loading = ref(true);
const httpCode = ref<number | null>(null);

const httpCodeDisplay = computed(() => {
  if (loading.value) return '...';
  if (httpCode.value === null) return 'N/A';
  return httpCode.value;
});

async function checkStatus() {
  loading.value = true;
  status.value = 'unknown';
  httpCode.value = null;
  try {
    const response = await fetch('https://inwneon-project-voice-diarzation.hf.space', {
      method: 'GET',
      mode: 'cors',
    });
    httpCode.value = response.status;
    if (response.ok) {
      status.value = 'online';
    } else {
      status.value = 'offline';
    }
  } catch {
    status.value = 'unknown';
    httpCode.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  checkStatus();
});
</script>

<template>
  <div class="flex items-center gap-4 rounded-xl bg-white dark:bg-gray-900 shadow p-6 border border-sky-100 dark:border-sky-900">
    <div class="flex items-center justify-center h-14 w-14 rounded-full bg-slate-100">
      <span
        v-if="loading"
        class="w-7 h-7 rounded-full bg-sky-400 animate-pulse"
      ></span>
      <span
        v-else-if="status === 'online'"
        class="w-7 h-7 rounded-full bg-green-400"
      ></span>
      <span
        v-else-if="status === 'offline'"
        class="w-7 h-7 rounded-full bg-red-400"
      ></span>
      <span
        v-else
        class="w-7 h-7 rounded-full bg-gray-400"
      ></span>
    </div>
    <div>
      <div class="text-gray-500 dark:text-gray-400 text-sm font-medium">Model Service Status</div>
      <div class="text-lg font-bold">
        <span v-if="loading" class="text-sky-400">Checking...</span>
        <span v-else-if="status === 'online'" class="text-green-600">Online</span>
        <span v-else-if="status === 'offline'" class="text-red-600">Offline</span>
        <span v-else class="text-gray-600">Unknown/Error</span>
      </div>
      <div class="text-xs text-gray-400 mt-1">HTTP Code: <span class="font-mono text-blue-4s00">{{ httpCodeDisplay }}</span></div>
    </div>
  </div>
</template> 