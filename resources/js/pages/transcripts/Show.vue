<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import SpeakerCountList from '@/components/Transcripts/SpeakerCountList.vue'
import TranscriptPanel from '@/components/Transcripts/TranscriptPanel.vue'
import { RotateCcw, Edit, FileText, Users, MessageSquare, Play, Clock, CheckCircle, AlertCircle, Loader, ArrowLeft, Download, Share2 } from 'lucide-vue-next'
import { ref, computed } from 'vue'

const props = defineProps<{
  item: {
    id: number
    title?: string|null
    media_path?: string|null
    status: string
    num_speakers: number
    total_sentence: number
    count_speaker: Array<{ speaker: string|null; count: number }>
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Transcripts', href: '/transcripts' },
  { title: props.item.title ?? 'Show', href: `/transcripts/${props.item.id}` },
]

const processing = ref(false)

function requeue() {
  processing.value = true
  router.post(route('transcripts.transcribe', props.item.id), {}, { 
    onFinish: () => processing.value = false 
  })
}

const getStatusConfig = (status: string) => {
  const configs = {
    done: {
      icon: CheckCircle,
      class: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      dotClass: 'bg-emerald-500'
    },
    processing: {
      icon: Loader,
      class: 'bg-amber-50 text-amber-700 border border-amber-200',
      dotClass: 'bg-amber-500'
    },
    failed: {
      icon: AlertCircle,
      class: 'bg-red-50 text-red-700 border border-red-200',
      dotClass: 'bg-red-500'
    },
    pending: {
      icon: Clock,
      class: 'bg-sky-50 text-sky-700 border border-sky-200',
      dotClass: 'bg-sky-500'
    }
  }
  return configs[status] || configs.pending
}
</script>

<template>
  <Head :title="`Transcript: ${props.item.title ?? 'Untitled'}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
      
      <!-- Header Section -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <!-- Back Button and Actions -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex-1">
              <div class="flex items-center gap-4 mb-3">
                <div class="w-12 h-12 bg-sky-100 dark:bg-sky-900/20 rounded-xl flex items-center justify-center">
                  <component :is="FileText" class="h-6 w-6 text-sky-600 dark:text-sky-400" />
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ props.item.title ?? 'Untitled Transcript' }}
                  </h1>
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-3">
              <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200">
                <component :is="Download" class="h-4 w-4" />
                Export
              </button>
              
              <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200">
                <component :is="Share2" class="h-4 w-4" />
                Share
              </button>
              
              <Link 
                :href="route('transcripts.edit', props.item.id)" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-100 dark:bg-sky-900/20 hover:bg-sky-200 dark:hover:bg-sky-900/30 text-sky-600 dark:text-sky-400 transition-all duration-200"
              >
                <component :is="Edit" class="h-4 w-4" />
                Edit
              </Link>
              
              <button 
                @click="requeue" 
                :disabled="processing" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-sky-500/25"
              >
                <component :is="RotateCcw" :class="['h-4 w-4', processing && 'animate-spin']" />
                {{ processing ? 'Queuing…' : 'Transcribe again' }}
              </button>
            </div>
          </div>

          <!-- Title and Status -->
          <div class="flex items-end justify-end">
            <div :class="getStatusConfig(props.item.status).class + ' px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2'">
              <div :class="getStatusConfig(props.item.status).dotClass + ' w-2.5 h-2.5 rounded-full'"></div>
              <component :is="getStatusConfig(props.item.status).icon" class="h-4 w-4" />
              <span class="capitalize">{{ props.item.status }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Status Card -->
        <div class="bg-gradient-to-br from-sky-50 to-sky-100 dark:from-sky-900/20 dark:to-sky-800/20 rounded-2xl border border-sky-200 dark:border-sky-800 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-sky-200 dark:bg-sky-800 rounded-xl flex items-center justify-center">
              <component :is="getStatusConfig(props.item.status).icon" class="h-6 w-6 text-sky-600 dark:text-sky-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-sky-600 dark:text-sky-400">Current Status</p>
              <p class="text-2xl font-bold text-sky-700 dark:text-sky-300 capitalize">{{ props.item.status }}</p>
            </div>
          </div>
        </div>

        <!-- Speakers Card -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-2xl border border-purple-200 dark:border-purple-800 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-200 dark:bg-purple-800 rounded-xl flex items-center justify-center">
              <component :is="Users" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Speakers</p>
              <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ props.item.num_speakers }}</p>
            </div>
          </div>
        </div>

        <!-- Sentences Card -->
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-2xl border border-emerald-200 dark:border-emerald-800 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-200 dark:bg-emerald-800 rounded-xl flex items-center justify-center">
              <component :is="MessageSquare" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Total Sentences</p>
              <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ props.item.total_sentence }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Speaker Count List -->
        <div class="xl:col-span-1">
          <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
            <div class="p-6">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-sky-100 dark:bg-sky-900/20 rounded-lg flex items-center justify-center">
                  <component :is="Users" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Speaker Distribution</h3>
              </div>
              <SpeakerCountList :items="props.item.count_speaker" />
            </div>
          </div>
        </div>

        <!-- Transcript Panel -->
        <div class="xl:col-span-2">
          <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
            <div class="p-6">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center">
                  <component :is="MessageSquare" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transcript Segments</h3>
              </div>
              <TranscriptPanel :transcript-id="props.item.id" />
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <button class="flex items-center gap-3 p-4 rounded-xl bg-sky-50 dark:bg-sky-900/10 hover:bg-sky-100 dark:hover:bg-sky-900/20 text-sky-600 dark:text-sky-400 transition-all duration-200">
              <component :is="Play" class="h-5 w-5" />
              <span class="font-medium">Play Audio</span>
            </button>
            
            <button class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 transition-all duration-200">
              <component :is="Download" class="h-5 w-5" />
              <span class="font-medium">Export Text</span>
            </button>
            
            <button class="flex items-center gap-3 p-4 rounded-xl bg-purple-50 dark:bg-purple-900/10 hover:bg-purple-100 dark:hover:bg-purple-900/20 text-purple-600 dark:text-purple-400 transition-all duration-200">
              <component :is="Share2" class="h-5 w-5" />
              <span class="font-medium">Share Link</span>
            </button>
            
            <Link 
              :href="route('transcripts.edit', props.item.id)"
              class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200"
            >
              <component :is="Edit" class="h-5 w-5" />
              <span class="font-medium">Edit Details</span>
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>