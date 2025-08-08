<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import TranscriptPanel from '@/Components/Transcripts/TranscriptPanel.vue'
import { Save, ChevronLeft, Edit, FileText, AlertCircle, CheckCircle, ArrowLeft, Eye } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps<{
  item: { 
    id: number
    title?: string | null 
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Transcripts', href: '/transcripts' },
  { title: props.item.title || 'Untitled', href: `/transcripts/${props.item.id}` },
  { title: 'Edit', href: `/transcripts/${props.item.id}/edit` },
]

const form = useForm({ 
  title: props.item.title ?? '' 
})

const hasTitle = computed(() => form.title.trim().length > 0)

function submit() {
  form.put(route('transcripts.update', props.item.id))
}
</script>

<template>
  <Head title="Edit Transcript" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
      
      <!-- Header Section -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <!-- Back Button and Actions -->
          <div class="flex items-center justify-between mb-6">
            <Link 
              :href="route('transcripts.index')" 
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200"
            >
              <component :is="ArrowLeft" class="h-4 w-4" />
              Back to Transcripts
            </Link>
            
            <div class="flex items-center gap-3">
              <Link 
                :href="route('transcripts.show', props.item.id)" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-100 dark:bg-sky-900/20 hover:bg-sky-200 dark:hover:bg-sky-900/30 text-sky-600 dark:text-sky-400 transition-all duration-200"
              >
                <component :is="Eye" class="h-4 w-4" />
                View Transcript
              </Link>
            </div>
          </div>

          <!-- Title Section -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-sky-100 dark:bg-sky-900/20 rounded-xl flex items-center justify-center">
              <component :is="Edit" class="h-6 w-6 text-sky-600 dark:text-sky-400" />
            </div>
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Transcript</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Update transcript information and manage segments
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Section -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-sky-100 dark:bg-sky-900/20 rounded-lg flex items-center justify-center">
              <component :is="FileText" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h3>
          </div>

          <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <!-- Title Input -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Transcript Title
              </label>
              <div class="relative">
                <input 
                  v-model="form.title" 
                  type="text"
                  placeholder="Enter a descriptive title for your transcript"
                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-200"
                  :class="[
                    form.errors.title ? 'border-red-300 dark:border-red-700' : 'border-gray-200 dark:border-gray-700',
                    hasTitle ? 'ring-2 ring-sky-500/20' : ''
                  ]"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                  <component 
                    :is="hasTitle ? CheckCircle : AlertCircle" 
                    :class="[
                      'h-5 w-5',
                      hasTitle ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600'
                    ]"
                  />
                </div>
              </div>
              
              <!-- Error Message -->
              <div v-if="form.errors.title" class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                <component :is="AlertCircle" class="h-4 w-4" />
                {{ form.errors.title }}
              </div>
              
              <!-- Helper Text -->
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Choose a clear, descriptive title that helps identify this transcript
              </p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
              <button 
                type="submit" 
                :disabled="form.processing || !hasTitle"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white disabled:text-gray-500 dark:disabled:text-gray-400 transition-all duration-200 shadow-lg shadow-sky-500/25 disabled:shadow-none font-medium"
              >
                <component :is="Save" :class="['h-4 w-4', form.processing && 'animate-pulse']" />
                {{ form.processing ? 'Saving…' : 'Save Changes' }}
              </button>
              
              <Link 
                :href="route('transcripts.show', props.item.id)"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200 font-medium"
              >
                Cancel
              </Link>
            </div>
          </form>
        </div>
      </div>

      <!-- Segment Editor Section -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center">
                <component :is="Edit" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Segment Editor</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Manage individual transcript segments with full editing capabilities
                </p>
              </div>
            </div>
            
            <!-- Editor Status -->
            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
              <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
              <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Edit Mode Active</span>
            </div>
          </div>

          <!-- Advanced Editor Features Notice -->
          <div class="mb-6 p-4 bg-sky-50 dark:bg-sky-900/10 border border-sky-200 dark:border-sky-800 rounded-xl">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-sky-200 dark:bg-sky-800 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                <component :is="Edit" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
              </div>
              <div>
                <h4 class="font-medium text-sky-900 dark:text-sky-100 mb-1">Advanced Editing Enabled</h4>
                <p class="text-sm text-sky-700 dark:text-sky-300 mb-2">
                  You can now add, edit, and delete segments. All columns are visible for detailed editing.
                </p>
                <ul class="text-sm text-sky-600 dark:text-sky-400 space-y-1">
                  <li>• Add new segments with custom timing and speaker information</li>
                  <li>• Edit existing segments including text and AI corrections</li>
                  <li>• Adjust speaker assignments and timing data</li>
                  <li>• View probability scores and audio file references</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Transcript Panel -->
          <TranscriptPanel :transcript-id="props.item.id" :edit-mode="true" :fetch-full="true" />
        </div>
      </div>

    </div>
  </AppLayout>
</template>