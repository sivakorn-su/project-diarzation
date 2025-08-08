<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref, computed, reactive, onMounted } from 'vue'
import { Refresh, Plus, Edit2, Trash2, X, Save, User, Clock, FileAudio, Target, MessageSquare, Brain, Search, RefreshCwIcon } from 'lucide-vue-next'

interface Segment {
  id: number
  transcript_id: number
  speaker: string | null
  filename?: string | null
  start?: number | null
  end?: number | null
  avg_probability?: number | null
  text?: string | null
  llm_corrected_text?: string | null
}

const props = defineProps<{
  transcriptId: number
  editMode?: boolean
  fetchFull?: boolean
}>()

const showFull = computed(() => !!props.fetchFull)
const editMode = computed(() => !!props.editMode)

const segments = ref<Segment[]>([])
const show = ref(false)
const isEdit = ref(false)
const currentId = ref<number | null>(null)
const searchQuery = ref('')
const isLoading = ref(false)

const form = reactive<Partial<Segment>>({
  speaker: null, filename: '', start: undefined, end: undefined, avg_probability: undefined,
  text: '', llm_corrected_text: ''
})

const filteredSegments = computed(() => {
  if (!searchQuery.value) return segments.value
  
  return segments.value.filter(segment => 
    segment.speaker?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    segment.text?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    segment.llm_corrected_text?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

function refresh() {
  isLoading.value = true
  router.visit(
    route('transcripts.show', { transcript: props.transcriptId, full: props.fetchFull ? 1 : undefined }),
    { 
      preserveScroll: true, 
      preserveState: true, 
      only: ['item'], 
      method: 'get',
      onSuccess: (page: any) => {
        const item = page.props?.item
        segments.value = (item?.segments ?? []) as Segment[]
      },
      onFinish: () => {
        isLoading.value = false
      }
    }
  )
}

function openCreate() { 
  if (!editMode.value) return
  isEdit.value = false
  currentId.value = null
  Object.assign(form, { 
    speaker: null, filename: '', start: undefined, end: undefined, 
    avg_probability: undefined, text: '', llm_corrected_text: '' 
  })
  show.value = true 
}

function openEdit(s: Segment) { 
  if (!editMode.value) return
  isEdit.value = true
  currentId.value = s.id
  Object.assign(form, s)
  show.value = true 
}

function close() { 
  show.value = false 
}

function save() {
  if (!editMode.value) return
  if (isEdit.value && currentId.value) {
    router.put(route('segments.update', { transcript: props.transcriptId, segment: currentId.value }), form, { 
      onSuccess: refresh, 
      onFinish: () => show.value = false 
    })
  } else {
    router.post(route('segments.store', { transcript: props.transcriptId }), form, { 
      onSuccess: refresh, 
      onFinish: () => show.value = false 
    })
  }
}

function remove(s: Segment) {
  if (!editMode.value) return
  if (!confirm('Delete this segment? This action cannot be undone.')) return
  router.delete(route('segments.destroy', { transcript: props.transcriptId, segment: s.id }), { 
    onSuccess: refresh 
  })
}

function formatTime(seconds: number | null | undefined): string {
  if (seconds == null) return '-'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function formatProbability(prob: number | null | undefined): string {
  if (prob == null) return '-'
  return `${(prob * 100).toFixed(1)}%`
}

onMounted(refresh)
</script>

<template>
  <!-- Header Controls -->
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
      <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Segments</h4>
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ filteredSegments.length }} of {{ segments.length }} segments
      </div>
    </div>
    
    <div class="flex items-center gap-3">
      <!-- Search -->
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <component :is="Search" class="h-4 w-4 text-gray-400" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search segments..."
          class="pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm"
        />
      </div>
      
      <!-- Refresh Button -->
      <button 
        @click="refresh" 
        :disabled="isLoading"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-all duration-200 disabled:opacity-50"
      >
        <component :is="RefreshCwIcon" :class="['h-4 w-4', isLoading && 'animate-spin']" />
        Refresh
      </button>
      
      <!-- Add Button -->
      <button 
        v-if="editMode" 
        @click="openCreate"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 text-white transition-all duration-200 shadow-lg shadow-sky-500/25"
      >
        <component :is="Plus" class="h-4 w-4" />
        Add Segment
      </button>
    </div>
  </div>

  <!-- Segments List -->
  <div class="space-y-4">
    <div v-if="filteredSegments.length === 0" class="text-center py-12">
      <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
        <component :is="MessageSquare" class="h-8 w-8 text-gray-400" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
        {{ searchQuery ? 'No matching segments' : 'No segments found' }}
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'Try adjusting your search terms' : 'Segments will appear here after transcription' }}
      </p>
    </div>

    <!-- Segment Cards -->
    <div v-for="segment in filteredSegments" :key="segment.id" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-sky-200 dark:hover:border-sky-700 transition-all duration-200 hover:shadow-lg hover:shadow-sky-500/10">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-sky-100 dark:bg-sky-900/20 rounded-lg flex items-center justify-center">
              <component :is="User" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
            </div>
            <div>
              <div class="font-semibold text-gray-900 dark:text-white">
                {{ segment.speaker || 'Unknown Speaker' }}
              </div>
              <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                  <component :is="Clock" class="h-3 w-3" />
                  {{ formatTime(segment.start) }} - {{ formatTime(segment.end) }}
                </div>
                <div v-if="showFull && segment.avg_probability" class="flex items-center gap-1">
                  <component :is="Target" class="h-3 w-3" />
                  {{ formatProbability(segment.avg_probability) }}
                </div>
                <div v-if="showFull && segment.filename" class="flex items-center gap-1">
                  <component :is="FileAudio" class="h-3 w-3" />
                  {{ segment.filename }}
                </div>
              </div>
            </div>
          </div>
          
          <!-- Actions -->
          <div v-if="editMode" class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <button 
              @click="openEdit(segment)"
              class="p-2 rounded-lg bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 text-sky-600 dark:text-sky-400 transition-all duration-200"
            >
              <component :is="Edit2" class="h-4 w-4" />
            </button>
            <button 
              @click="remove(segment)"
              class="p-2 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 transition-all duration-200"
            >
              <component :is="Trash2" class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="space-y-3">
          <!-- Original Text -->
          <div v-if="segment.text" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
              <component :is="MessageSquare" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Original Text</span>
            </div>
            <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ segment.text }}</p>
          </div>

          <!-- LLM Corrected Text -->
          <div v-if="showFull && segment.llm_corrected_text" class="bg-sky-50 dark:bg-sky-900/10 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
              <component :is="Brain" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
              <span class="text-sm font-medium text-sky-700 dark:text-sky-300">AI Corrected Text</span>
            </div>
            <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ segment.llm_corrected_text }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div v-if="show" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-sky-100 dark:bg-sky-900/20 rounded-lg flex items-center justify-center">
            <component :is="isEdit ? Edit2 : Plus" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ isEdit ? 'Edit Segment' : 'Add New Segment' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ isEdit ? 'Update the segment information' : 'Create a new transcript segment' }}
            </p>
          </div>
        </div>
        
        <button 
          @click="close"
          class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-all duration-200"
        >
          <component :is="X" class="h-5 w-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Speaker -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="User" class="h-4 w-4 inline mr-2" />
              Speaker
            </label>
            <input 
              v-model="form.speaker" 
              type="text"
              placeholder="e.g., Speaker 1"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
            />
          </div>

          <!-- Filename -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="FileAudio" class="h-4 w-4 inline mr-2" />
              Filename
            </label>
            <input 
              v-model="form.filename" 
              type="text"
              placeholder="audio.mp3"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
            />
          </div>

          <!-- Start Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="Clock" class="h-4 w-4 inline mr-2" />
              Start Time (seconds)
            </label>
            <input 
              v-model.number="form.start" 
              type="number" 
              step="0.001"
              placeholder="0.000"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
            />
          </div>

          <!-- End Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="Clock" class="h-4 w-4 inline mr-2" />
              End Time (seconds)
            </label>
            <input 
              v-model.number="form.end" 
              type="number" 
              step="0.001"
              placeholder="10.000"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
            />
          </div>

          <!-- Average Probability -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="Target" class="h-4 w-4 inline mr-2" />
              Average Probability (0.0 - 1.0)
            </label>
            <input 
              v-model.number="form.avg_probability" 
              type="number" 
              step="0.0001"
              min="0"
              max="1"
              placeholder="0.8500"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
            />
          </div>

          <!-- Original Text -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="MessageSquare" class="h-4 w-4 inline mr-2" />
              Original Text
            </label>
            <textarea 
              v-model="form.text" 
              rows="4"
              placeholder="Enter the original transcribed text..."
              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent resize-vertical"
            />
          </div>

          <!-- LLM Corrected Text -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <component :is="Brain" class="h-4 w-4 inline mr-2" />
              AI Corrected Text
            </label>
            <textarea 
              v-model="form.llm_corrected_text" 
              rows="4"
              placeholder="Enter the AI-corrected text..."
              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent resize-vertical"
            />
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <button 
          @click="close"
          class="px-6 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
        >
          Cancel
        </button>
        <button 
          @click="save"
          class="px-6 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 text-white transition-all duration-200 shadow-lg shadow-sky-500/25 flex items-center gap-2"
        >
          <component :is="Save" class="h-4 w-4" />
          {{ isEdit ? 'Save Changes' : 'Create Segment' }}
        </button>
      </div>
    </div>
  </div>
</template>