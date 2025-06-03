<template>
    <div class="file-uploader">
        <form
            ref="dropzoneForm"
            :id="dropzoneId"
            :action="uploadUrl"
            class="dropzone border-dashed rounded-xl border bg-gray-50 p-7 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500 lg:p-10"
        >
            <div class="dz-message flex flex-col items-center text-center">
                <div class="mb-6 flex items-center justify-center h-[68px] w-[68px] rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <svg class="fill-current" width="29" height="28" viewBox="0 0 29 28">
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M14.5 3.917a.75.75 0 00-.547.239l-5.38 5.376a.75.75 0 101.06 1.06l4.118-4.115v12.19a.75.75 0 001.5 0V6.476l4.113 4.116a.75.75 0 101.06-1.06l-5.342-5.38a.75.75 0 00-.582-.235zM5.916 18.667a.75.75 0 00-1.5 0v3.167A2.25 2.25 0 006.666 24.084h15.668a2.25 2.25 0 002.25-2.25v-3.167a.75.75 0 10-1.5 0v3.167a.75.75 0 01-.75.75H6.666a.75.75 0 01-.75-.75v-3.167z"
                        />
                    </svg>
                </div>

                <h4 class="mb-3 font-semibold text-gray-800 text-lg dark:text-white/90">
                    Drag & Drop File Here
                </h4>
                <p class="mb-4 max-w-sm text-sm text-gray-600 dark:text-gray-400">
                    MP4, MP3, WAV files are supported.
                </p>

                <span
                    class="cursor-pointer text-sm font-medium text-brand-500 underline hover:text-brand-600"
                >
          Browse File
        </span>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css'

const props = defineProps({
    uploadUrl: {
        type: String,
        default: '/upload',
    },
})

const dropzoneForm = ref<HTMLFormElement | null>(null)
const dropzoneId = `dropzone-${Math.random().toString(36).substring(2, 9)}`
let dropzoneInstance: Dropzone | null = null

onMounted(() => {
    Dropzone.autoDiscover = false

    dropzoneInstance = new Dropzone(`#${dropzoneId}`, {
        url: props.uploadUrl,
        thumbnailWidth: 150,
        maxFilesize: 5000, // MB
        acceptedFiles: 'video/mp4,audio/mp3,audio/mpeg,audio/wav',
        headers: {
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
        },
        init() {
            this.on('addedfile', (file) => {
                console.log('[Dropzone] File added:', file)
            })
            this.on('success', (file, response) => {
                console.log('[Dropzone] Upload success:', file, response)
            })
            this.on('error', (file, errorMessage) => {
                console.error('[Dropzone] Upload error:', file, errorMessage)
            })
        },
    })
})

onBeforeUnmount(() => {
    dropzoneInstance?.destroy()
})
</script>

<style>
.dropzone {
    border: 2px dashed #d1d5db;
    transition: all 0.3s ease;
    border-radius: 2rem;
    padding-left: 50px;
    padding-right: 50px;
}
.dropzone:hover {
    border-color: #6366f1;
}
.dropzone .dz-preview {
    margin-top: 1rem;
}
.dropzone .dz-preview .dz-image {
    border-radius: 0.5rem;
}
.dropzone .dz-progress {
    height: 0.5rem;
}
.dropzone .dz-upload {
    background-color: #6366f1;
}
.dark .dropzone {
    background-color: #1f2937;
    border-color: #374151;
}
.dark .dropzone:hover {
    border-color: #6366f1;
}
</style>
