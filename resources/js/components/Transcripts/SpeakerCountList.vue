<script setup lang="ts">
import { computed } from 'vue'
import { User, Users, BarChart3 } from 'lucide-vue-next'

interface SpeakerCount {
  speaker: string | null
  count: number
}

const props = defineProps<{
  items: SpeakerCount[]
}>()

const totalSegments = computed(() => {
  return props.items.reduce((total, item) => total + item.count, 0)
})

const sortedItems = computed(() => {
  return [...props.items].sort((a, b) => b.count - a.count)
})

const getPercentage = (count: number): number => {
  if (totalSegments.value === 0) return 0
  return Math.round((count / totalSegments.value) * 100)
}

const getColorClass = (index: number): string => {
  const colors = [
    'from-sky-500 to-sky-600',
    'from-emerald-500 to-emerald-600',
    'from-purple-500 to-purple-600',
    'from-amber-500 to-amber-600',
    'from-rose-500 to-rose-600',
    'from-indigo-500 to-indigo-600',
    'from-teal-500 to-teal-600',
    'from-orange-500 to-orange-600'
  ]
  return colors[index % colors.length]
}

const getBgColorClass = (index: number): string => {
  const colors = [
    'bg-sky-50 dark:bg-sky-900/10 border-sky-200 dark:border-sky-800',
    'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800',
    'bg-purple-50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800',
    'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800',
    'bg-rose-50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800',
    'bg-indigo-50 dark:bg-indigo-900/10 border-indigo-200 dark:border-indigo-800',
    'bg-teal-50 dark:bg-teal-900/10 border-teal-200 dark:border-teal-800',
    'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800'
  ]
  return colors[index % colors.length]
}

const getTextColorClass = (index: number): string => {
  const colors = [
    'text-sky-700 dark:text-sky-300',
    'text-emerald-700 dark:text-emerald-300',
    'text-purple-700 dark:text-purple-300',
    'text-amber-700 dark:text-amber-300',
    'text-rose-700 dark:text-rose-300',
    'text-indigo-700 dark:text-indigo-300',
    'text-teal-700 dark:text-teal-300',
    'text-orange-700 dark:text-orange-300'
  ]
  return colors[index % colors.length]
}
</script>

<template>
  <div class="space-y-4">
    <!-- Summary Card -->
    <div class="bg-gradient-to-r from-sky-50 to-sky-100 dark:from-sky-900/20 dark:to-sky-800/20 rounded-xl p-4 border border-sky-200 dark:border-sky-800">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-sky-200 dark:bg-sky-800 rounded-lg flex items-center justify-center">
          <component :is="BarChart3" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
        </div>
        <div>
          <div class="text-sm font-medium text-sky-600 dark:text-sky-400">
            Total Speakers
          </div>
          <div class="text-2xl font-bold text-sky-700 dark:text-sky-300">
            {{ sortedItems.length }}
          </div>
        </div>
        <div class="ml-auto text-right">
          <div class="text-sm text-sky-600 dark:text-sky-400">
            Total Segments
          </div>
          <div class="text-lg font-semibold text-sky-700 dark:text-sky-300">
            {{ totalSegments }}
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="sortedItems.length === 0" class="text-center py-8">
      <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3">
        <component :is="Users" class="h-6 w-6 text-gray-400" />
      </div>
      <p class="text-gray-500 dark:text-gray-400 text-sm">No speaker data available</p>
    </div>

    <!-- Speaker List -->
    <div v-else class="space-y-3">
      <div 
        v-for="(item, index) in sortedItems" 
        :key="item.speaker || 'unknown'" 
        :class="['rounded-xl p-4 border transition-all duration-200 hover:shadow-md', getBgColorClass(index)]"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="relative">
              <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center shadow-sm">
                <component :is="User" :class="['h-5 w-5', getTextColorClass(index)]" />
              </div>
              <!-- Position Badge -->
              <div v-if="index < 3" class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-r rounded-full flex items-center justify-center text-xs font-bold text-white shadow-lg"
                   :class="index === 0 ? 'from-yellow-400 to-yellow-500' : index === 1 ? 'from-gray-400 to-gray-500' : 'from-amber-600 to-amber-700'">
                {{ index + 1 }}
              </div>
            </div>
            <div>
              <div :class="['font-semibold', getTextColorClass(index)]">
                {{ item.speaker || 'Unknown Speaker' }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ item.count }} segments • {{ getPercentage(item.count) }}%
              </div>
            </div>
          </div>
          
          <!-- Count Badge -->
          <div :class="['px-3 py-1 rounded-full text-sm font-semibold', getBgColorClass(index)]">
            <span :class="getTextColorClass(index)">
              {{ item.count }}
            </span>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-3">
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div 
              class="h-2 rounded-full bg-gradient-to-r transition-all duration-500 ease-out"
              :class="getColorClass(index)"
              :style="{ width: `${getPercentage(item.count)}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div v-if="sortedItems.length > 0" class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
      <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Quick Stats</h4>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-gray-500 dark:text-gray-400">Most Active</div>
          <div class="font-semibold text-gray-900 dark:text-white truncate">
            {{ sortedItems[0]?.speaker || 'Unknown' }}
          </div>
        </div>
        <div>
          <div class="text-gray-500 dark:text-gray-400">Avg per Speaker</div>
          <div class="font-semibold text-gray-900 dark:text-white">
            {{ Math.round(totalSegments / sortedItems.length) }} segments
          </div>
        </div>
      </div>
    </div>
  </div>
</template>