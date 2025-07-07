// Service Status Page: Checks if https://inwneon-project-voice-diarzation.hf.space is online or offline and displays the result.
<template>
  <Head title="Service Status" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-white min-h-screen py-8 px-4">
      <h1 class="text-3xl font-extrabold text-sky-400 mb-2 tracking-tight">Service Status</h1>
      <p class="text-gray-500 mb-6">Check the current status and details of the diarization service.</p>
      <div class="mb-6">
        <div v-if="loading" class="flex items-center space-x-2 animate-pulse">
          <span class="w-4 h-4 rounded-full bg-sky-400"></span>
          <span class="text-sky-400 font-medium">Checking status...</span>
        </div>
        <div v-else class="flex items-center space-x-2">
          <span
            v-if="status === 'running'"
            class="w-4 h-4 bg-green-400 rounded-full"
          ></span>
          <span
            v-else
            class="w-4 h-4 bg-red-400 rounded-full"
          ></span>
          <span
            v-if="status === 'running'"
            class="text-lg font-semibold text-green-600"
          >Running</span>
          <span
            v-else
            class="text-lg font-semibold text-red-600"
          >Offline/Error</span>
        </div>
      </div>
      <div v-if="!loading && details" class="mb-6">
        <div class="mb-2">
          <span class="text-sm text-gray-500">Pipelines Loaded:</span>
          <span class="text-base font-mono text-sky-400 ml-2">{{ details.models_loaded?.pipelines ?? 'N/A' }}</span>
        </div>
        <div class="mb-2">
          <span class="text-sm text-gray-500">Whisper Models Loaded:</span>
          <span class="text-base font-mono text-sky-400 ml-2">{{ details.models_loaded?.whisper_models ?? 'N/A' }}</span>
        </div>
        <div class="mb-2">
          <span class="text-sm text-gray-500">CUDA Available:</span>
          <span class="text-base font-mono ml-2" :class="details.cuda_available ? 'text-green-600' : 'text-gray-400'">
            {{ details.cuda_available ? 'Yes' : 'No' }}
          </span>
        </div>
        <div class="mb-2">
          <span class="text-sm text-gray-500">CUDA Devices:</span>
          <span class="text-base font-mono text-sky-400 ml-2">{{ details.cuda_devices ?? 'N/A' }}</span>
        </div>
      </div>
      <div v-if="!loading && error" class="mb-6 text-red-500 font-medium">{{ error }}</div>
      <a
        href="https://inwneon-project-voice-diarzation.hf.space"
        target="_blank"
        rel="noopener noreferrer"
        class="text-sky-400 underline hover:text-sky-600 transition font-medium"
      >
        Visit Service
      </a>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Service Status', href: '/status' },
];

const status = ref<string>('unknown');
const loading = ref(true);
const error = ref<string | null>(null);
const details = ref<any>(null);

async function checkStatus() {
  loading.value = true;
  status.value = 'unknown';
  error.value = null;
  details.value = null;
  try {
    const response = await fetch('https://inwneon-project-voice-diarzation.hf.space/', {
      method: 'GET',
      mode: 'cors',
    });
    if (!response.ok) {
      throw new Error('Service returned HTTP ' + response.status);
    }
    const data = await response.json();
    status.value = data.status || 'unknown';
    details.value = data;
  } catch (e: any) {
    status.value = 'offline';
    error.value = e?.message || 'Unknown error';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  checkStatus();
});
</script> 