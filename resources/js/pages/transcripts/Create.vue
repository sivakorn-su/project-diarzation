<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
  UploadCloud, 
  Save, 
  X, 
  ArrowLeft, 
  FileText, 
  AlertCircle, 
  Check,
  Plus,
  Music,
  Video,
  File
} from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Transcripts', href: '/transcripts' },
  { title: 'Create', href: '/transcripts/create' }
]

const form = useForm({
  title: '',
  description: '',
  file: null as File | null,
})

const fileInput = ref<HTMLInputElement | null>(null)
const isDragOver = ref(false)

const submit = () => {
  form.post('/transcripts', {
    forceFormData: true,
    onSuccess: () => {
      router.visit('/transcripts')
    }
  })
}

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.file = target.files[0]
  }
}

const removeFile = () => {
  form.file = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (e: DragEvent) => {
  e.preventDefault()
  isDragOver.value = false
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  isDragOver.value = false
  
  const files = e.dataTransfer?.files
  if (files && files[0]) {
    form.file = files[0]
  }
}

const getFileIcon = (file: File) => {
  if (file.type.startsWith('audio/')) return Music
  if (file.type.startsWith('video/')) return Video
  return File
}

const getFileSize = (size: number): string => {
  if (size < 1024) return `${size} B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
  if (size < 1024 * 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} MB`
  return `${(size / (1024 * 1024 * 1024)).toFixed(1)} GB`
}

const isFormValid = computed(() => {
  return form.title.trim() && form.file
})

const supportedFormats = [
  'MP3', 'WAV', 'FLAC', 'AAC', 'OGG',
  'MP4', 'MOV', 'AVI', 'MKV', 'WEBM'
]
</script>

<template>
  <Head title="Create Transcript" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">

          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create Transcript</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Upload audio or video file for transcription</p>
          </div>
        </div>
      </div>

      <!-- Main Form Card -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-8">
          <form @submit.prevent="submit" class="space-y-8">
            
            <!-- Basic Information Section -->
            <div>
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-sky-100 dark:bg-sky-900/20 rounded-lg flex items-center justify-center">
                  <component :is="FileText" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h3>
              </div>
              
              <div class="grid grid-cols-1 gap-6">
                <!-- Title -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Title <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.title"
                    type="text"
                    placeholder="Enter a descriptive title for your transcript"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-300 focus:ring-red-500': form.errors.title }"
                  />
                  <div v-if="form.errors.title" class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mt-2">
                    <component :is="AlertCircle" class="h-4 w-4" />
                    {{ form.errors.title }}
                  </div>
                </div>

                <!-- Description -->
                <!-- <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description
                  </label>
                  <textarea
                    v-model="form.description"
                    placeholder="Add an optional description..."
                    rows="4"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent resize-vertical transition-all duration-200"
                    :class="{ 'border-red-300 focus:ring-red-500': form.errors.description }"
                  />
                  <div v-if="form.errors.description" class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mt-2">
                    <component :is="AlertCircle" class="h-4 w-4" />
                    {{ form.errors.description }}
                  </div>
                </div> -->
              </div>
            </div>

            <!-- File Upload Section -->
            <div>
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center">
                  <component :is="UploadCloud" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Media File</h3>
                <span class="text-red-500 text-sm">*</span>
              </div>

              <!-- File Drop Zone -->
              <div 
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                @click="openFileDialog"
                :class="[
                  'relative border-2 border-dashed rounded-2xl p-8 cursor-pointer transition-all duration-200',
                  isDragOver 
                    ? 'border-sky-400 bg-sky-50 dark:bg-sky-900/20' 
                    : form.file 
                      ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20'
                      : 'border-gray-300 dark:border-gray-600 hover:border-sky-300 hover:bg-sky-50/50 dark:hover:bg-sky-900/10',
                  form.errors.file && 'border-red-300 bg-red-50 dark:bg-red-900/20'
                ]"
              >
                <input
                  type="file"
                  accept="audio/*,video/*"
                  ref="fileInput"
                  @change="handleFileChange"
                  class="hidden"
                />
                
                <!-- Upload State -->
                <div v-if="!form.file" class="text-center">
                  <div class="w-16 h-16 bg-sky-100 dark:bg-sky-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <component :is="UploadCloud" class="h-8 w-8 text-sky-500 dark:text-sky-400" />
                  </div>
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Drop your file here or click to browse
                  </h4>
                  <p class="text-gray-500 dark:text-gray-400 mb-4">
                    Upload audio or video files for transcription
                  </p>
                  
                  <!-- Supported Formats -->
                  <div class="flex flex-wrap gap-2 justify-center">
                    <span v-for="format in supportedFormats" :key="format" 
                          class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded-lg">
                      {{ format }}
                    </span>
                  </div>
                </div>

                <!-- File Preview -->
                <div v-else class="text-center">
                  <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <component :is="getFileIcon(form.file)" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                  </div>
                  
                  <div class="mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                      {{ form.file.name }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ getFileSize(form.file.size) }} • {{ form.file.type || 'Unknown format' }}
                    </p>
                  </div>

                  <button
                    type="button"
                    @click.stop="removeFile"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-100 dark:bg-red-900/20 hover:bg-red-200 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 transition-all duration-200"
                  >
                    <component :is="X" class="h-4 w-4" />
                    Remove File
                  </button>
                </div>
              </div>

              <div v-if="form.errors.file" class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mt-3">
                <component :is="AlertCircle" class="h-4 w-4" />
                {{ form.errors.file }}
              </div>
            </div>

            <!-- Processing Info -->
            <div class="bg-sky-50 dark:bg-sky-900/10 rounded-xl p-6 border border-sky-200 dark:border-sky-800">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-sky-200 dark:bg-sky-800 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                  <component :is="AlertCircle" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                </div>
                <div>
                  <h4 class="font-semibold text-sky-900 dark:text-sky-100 mb-2">Processing Information</h4>
                  <ul class="space-y-1 text-sm text-sky-700 dark:text-sky-300">
                    <li>• Large files may take several minutes to process</li>
                    <li>• You'll be notified when transcription is complete</li>
                    <li>• Supported formats: Audio (MP3, WAV, FLAC) and Video (MP4, MOV, AVI)</li>
                    <li>• Maximum file size: 500MB</li>
                  </ul>
                </div>
              </div>
            </div>

          </form>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
          <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <component :is="form.title && form.file ? Check : AlertCircle" 
                       :class="form.title && form.file ? 'text-emerald-500' : 'text-amber-500'" 
                       class="h-4 w-4" />
            {{ form.title && form.file ? 'Ready to create transcript' : 'Please fill required fields' }}
          </div>
          
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="router.visit('/transcripts')"
              class="px-6 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
            >
              Cancel
            </button>
            
            <button
              type="button"
              @click="submit"
              :disabled="form.processing || !isFormValid"
              class="flex items-center gap-2 px-6 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 text-white transition-all duration-200 shadow-lg shadow-sky-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
            >
              <component :is="Save" :class="['h-4 w-4', form.processing && 'animate-pulse']" />
              {{ form.processing ? 'Creating...' : 'Create Transcript' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Additional Tips Card -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/20 rounded-lg flex items-center justify-center">
              <component :is="FileText" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tips for Better Transcription</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600 dark:text-gray-400">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Audio Quality</h4>
              <ul class="space-y-1">
                <li>• Use clear, high-quality recordings</li>
                <li>• Minimize background noise</li>
                <li>• Ensure speakers are clearly audible</li>
              </ul>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white mb-2">File Preparation</h4>
              <ul class="space-y-1">
                <li>• Convert to supported formats if needed</li>
                <li>• Keep file sizes under 500MB</li>
                <li>• Use descriptive filenames</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>