<template>
    <div class="max-w-lg mx-auto mt-10 p-6 bg-white rounded shadow">
      <h1 class="text-2xl font-bold mb-4">Upload Video or Audio</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block font-medium mb-1" for="media">Video/Audio File</label>
          <input
            id="media"
            type="file"
            accept="video/mp4,video/quicktime,audio/mpeg,audio/wav"
            @change="e => form.video = e.target.files[0]"
            class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
          />
          <p v-if="form.errors.video" class="text-red-500 text-sm mt-1">{{ form.errors.video }}</p>
        </div>
        <div>
          <label class="block font-medium mb-1" for="transcript">Transcript (optional)</label>
          <textarea
            id="transcript"
            v-model="form.transcript"
            rows="3"
            class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
          ></textarea>
          <p v-if="form.errors.transcript" class="text-red-500 text-sm mt-1">{{ form.errors.transcript }}</p>
        </div>
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ form.processing ? 'Uploading...' : 'Upload' }}
        </button>
        <p v-if="success" class="text-green-600 mt-2">Upload successful!</p>
        <p v-if="errorMsg" class="text-red-600 mt-2">{{ errorMsg }}</p>
      </form>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref } from 'vue';
  import { useForm } from '@inertiajs/vue3';
  
  const props = defineProps<{ meetingId: number }>();
  
  const success = ref(false);
  const errorMsg = ref('');
  
  const form = useForm({
    video: null as File | null,
    transcript: '',
  });
  
  function submit() {
    success.value = false;
    errorMsg.value = '';
    
    // const response = await fetch(uploadUrl, {
        //     method: 'POST',
        //     body: formData,
        // });

    form.post(`/meetings/${props.meetingId}/infos`, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: (page) => {
        console.log('Success response:', page);
        success.value = true;
        form.reset('video', 'transcript');
      },
      onError: (errors) => {
        console.log('Error response:', errors);
        errorMsg.value = 'Upload failed. Please try again.';
      },
      onFinish: () => {
        console.log('Request finished');
      },
      onProgress: (progress) => {
        console.log('Upload progress:', progress);
      }
    });
  }
  </script>