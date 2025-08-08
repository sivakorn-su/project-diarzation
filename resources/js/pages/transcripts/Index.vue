<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { 
  Plus, 
  Eye, 
  Pencil, 
  Trash2, 
  FileText, 
  Clock, 
  Users, 
  MessageSquare, 
  HardDrive, 
  AlertCircle, 
  CheckCircle, 
  Loader,
  Search,
  Filter
} from 'lucide-vue-next'

const props = defineProps<{
  items: {
    data: Array<{
      id: number
      title?: string|null
      media_path?: string|null
      storage_driver?: string|null
      status: 'pending'|'processing'|'done'|'failed'
      num_speakers: number
      total_sentence: number
    }>
    current_page: number
    last_page: number
    total: number
    links: Array<{ url: string|null; label: string; active: boolean }>
  }
  filters?: {
    search?: string
    status?: string
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Transcripts', href: '/transcripts' }]

const searchQuery = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

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

function destroy(id: number) {
  if (confirm('Delete this transcript?')) router.delete(route('transcripts.destroy', id))
}

const handleSearch = () => {
  const params = new URLSearchParams()
  if (searchQuery.value.trim()) {
    params.set('search', searchQuery.value.trim())
  }
  if (statusFilter.value) {
    params.set('status', statusFilter.value)
  }
  
  const queryString = params.toString()
  router.visit(`/transcripts${queryString ? '?' + queryString : ''}`, {
    preserveState: true,
    replace: true
  })
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  router.visit('/transcripts', {
    preserveState: true,
    replace: true
  })
}

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'done', label: 'Completed' },
  { value: 'failed', label: 'Failed' }
]
</script>

<template>
  <Head title="Transcripts" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
      <!-- Header Section -->
      <div class="flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Transcripts</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your audio transcriptions</p>
        </div>
        
        <!-- Search and Filter Bar -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex gap-2">
            <!-- Search Input -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <component :is="Search" class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                type="text"
                placeholder="Search transcripts..."
                class="block w-64 pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-200"
              />
            </div>
            
            <!-- Status Filter -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <component :is="Filter" class="h-5 w-5 text-gray-400" />
              </div>
              <select
                v-model="statusFilter"
                @change="handleSearch"
                class="block w-40 pl-10 pr-8 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer"
              >
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            
            <!-- Search Button -->
            <button
              @click="handleSearch"
              class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-all duration-200 shadow-lg shadow-sky-500/25"
            >
              Search
            </button>
            
            <!-- Clear Filters -->
            <button
              v-if="searchQuery || statusFilter"
              @click="clearFilters"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-xl transition-all duration-200"
            >
              Clear
            </button>
          </div>
          
          <!-- New Transcript Button -->
          <Link 
            :href="route('transcripts.create')" 
            class="inline-flex items-center justify-center gap-2 px-6 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium transition-all duration-200 shadow-lg shadow-sky-500/25 hover:shadow-xl hover:shadow-sky-500/30"
          >
            <component :is="Plus" class="h-5 w-5" />
            New Transcript
          </Link>
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="searchQuery || statusFilter" class="flex items-center gap-3 text-sm">
        <span class="text-gray-500 dark:text-gray-400">Active filters:</span>
        <div class="flex gap-2">
          <span v-if="searchQuery" class="px-3 py-1 bg-sky-100 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 rounded-lg">
            Search: "{{ searchQuery }}"
          </span>
          <span v-if="statusFilter" class="px-3 py-1 bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-lg">
            Status: {{ statusOptions.find(s => s.value === statusFilter)?.label }}
          </span>
        </div>
      </div>

      <!-- Main Content Card -->
      <div class="border-sidebar-border/70 dark:border-sidebar-border relative flex-1 rounded-2xl border bg-white dark:bg-gray-900 shadow-sm">
        <!-- Stats Cards -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-r from-sky-50 to-sky-100 dark:from-sky-900/20 dark:to-sky-800/20 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-200 dark:bg-sky-800 rounded-lg flex items-center justify-center">
                  <component :is="FileText" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
                  <p class="text-xl font-bold text-sky-600 dark:text-sky-400">{{ props.items.total }}</p>
                </div>
              </div>
            </div>
            
            <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-200 dark:bg-emerald-800 rounded-lg flex items-center justify-center">
                  <component :is="CheckCircle" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</p>
                  <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ props.items.data.filter(t => t.status === 'done').length }}
                  </p>
                </div>
              </div>
            </div>
            
            <div class="bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-200 dark:bg-amber-800 rounded-lg flex items-center justify-center">
                  <component :is="Loader" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Processing</p>
                  <p class="text-xl font-bold text-amber-600 dark:text-amber-400">
                    {{ props.items.data.filter(t => t.status === 'processing').length }}
                  </p>
                </div>
              </div>
            </div>
            
            <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-200 dark:bg-red-800 rounded-lg flex items-center justify-center">
                  <component :is="AlertCircle" class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Failed</p>
                  <p class="text-xl font-bold text-red-600 dark:text-red-400">
                    {{ props.items.data.filter(t => t.status === 'failed').length }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Transcripts Grid -->
        <div class="p-6">
          <div v-if="props.items.data.length === 0" class="text-center py-16">
            <div class="w-16 h-16 bg-sky-100 dark:bg-sky-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
              <component :is="FileText" class="h-8 w-8 text-sky-500 dark:text-sky-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
              {{ searchQuery || statusFilter ? 'No transcripts found' : 'No transcripts yet' }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              {{ searchQuery || statusFilter ? 'Try adjusting your search or filters' : 'Get started by creating your first transcript' }}
            </p>
            <div class="flex justify-center gap-3">
              <button
                v-if="searchQuery || statusFilter"
                @click="clearFilters"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 font-medium transition-all duration-200"
              >
                Clear Filters
              </button>
              <Link 
                :href="route('transcripts.create')" 
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium transition-all duration-200"
              >
                <component :is="Plus" class="h-5 w-5" />
                Create Transcript
              </Link>
            </div>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="transcript in props.items.data" 
              :key="transcript.id"
              class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-sky-200 dark:hover:border-sky-700 transition-all duration-200 hover:shadow-lg hover:shadow-sky-500/10"
            >
              <!-- Status indicator -->
              <div class="absolute top-4 right-4">
                <div :class="getStatusConfig(transcript.status).class + ' px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5'">
                  <div :class="getStatusConfig(transcript.status).dotClass + ' w-2 h-2 rounded-full'"></div>
                  {{ transcript.status }}
                </div>
              </div>

              <div class="p-6">
                <!-- Title and Path -->
                <div class="mb-4">
                  <h3 class="font-semibold text-gray-900 dark:text-white text-lg mb-2 pr-20 line-clamp-2">
                    {{ transcript.title || 'Untitled' }}
                  </h3>
                </div>

                <!-- Metrics -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                  <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-sky-100 dark:bg-sky-900/20 rounded-lg mx-auto mb-1">
                      <component :is="Users" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ transcript.num_speakers }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Speakers</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg mx-auto mb-1">
                      <component :is="MessageSquare" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ transcript.total_sentence }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Sentences</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg mx-auto mb-1">
                      <component :is="HardDrive" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                      {{ transcript.storage_driver || '-' }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Storage</div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                  <Link 
                    :href="route('transcripts.show', transcript.id)" 
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-50 hover:bg-sky-100 dark:bg-sky-900/20 dark:hover:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-lg transition-all duration-200 text-sm font-medium"
                  >
                    <component :is="Eye" class="h-4 w-4" />
                    View
                  </Link>
                  
                  <Link 
                    :href="route('transcripts.edit', transcript.id)" 
                    class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg transition-all duration-200 text-sm font-medium"
                  >
                    <component :is="Pencil" class="h-4 w-4" />
                  </Link>
                  
                  <button 
                    @click="destroy(transcript.id)" 
                    class="flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg transition-all duration-200 text-sm font-medium"
                  >
                    <component :is="Trash2" class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="props.items.links?.length" class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
            <div class="text-sm text-gray-500 dark:text-gray-400">
              Showing page {{ props.items.current_page }} of {{ props.items.last_page }} • Total {{ props.items.total }} transcripts
            </div>
            <div class="flex gap-2">
            <Link
                v-for="(link, idx) in props.items.links"
                :key="idx"
                :href="link.url ?? '#'"
                :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                link.active 
                    ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/25' 
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-sky-50 dark:hover:bg-sky-900/10 hover:border-sky-200 dark:hover:border-sky-700 hover:text-sky-600 dark:hover:text-sky-400'
                ]"
            >
                <span v-html="link.label"></span>
            </Link>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>