<template>
  <div class="bg-white w-full p-6 rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-700">
    <!-- Case: String -->
    <div v-if="typeof summaries === 'string'">
        <p class="whitespace-pre-wrap text-gray-800 dark:text-gray-200 leading-relaxed">{{ summaries }}</p>
    </div>

    <!-- Case: Array -->
    <div v-else-if="Array.isArray(summaries)" class="space-y-4">
      <div v-for="(item, index) in summaries" :key="index">
         <p v-if="typeof item === 'string'" class="text-gray-800 dark:text-gray-200">{{ item }}</p>
         <div v-else class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
            <pre class="text-xs overflow-auto">{{ item }}</pre>
         </div>
      </div>
    </div>

    <!-- Case: Object (Key-Value like overview, key_points) -->
    <div v-else-if="typeof summaries === 'object' && summaries !== null" class="space-y-8">
        <div v-for="(value, key) in summaries" :key="key">
            <!-- Skip empty values -->
            <template v-if="value && (Array.isArray(value) ? value.length > 0 : true)">
                <h3 class="text-lg font-bold text-sky-600 dark:text-sky-400 mb-3 capitalize flex items-center gap-2">
                    <span class="w-1 h-6 bg-sky-500 rounded-full inline-block"></span>
                    {{ String(key).replace(/_/g, ' ') }}
                </h3>
                
                <!-- Value: String -->
                <p v-if="typeof value === 'string'" class="text-gray-700 dark:text-gray-300 leading-relaxed pl-3">
                    {{ value }}
                </p>
                
                <!-- Value: Array -->
                <ul v-else-if="Array.isArray(value)" class="space-y-2 pl-3">
                    <li v-for="(v, k) in value" :key="k" class="flex items-start gap-2 text-gray-700 dark:text-gray-300">
                        <span class="mt-1.5 w-1.5 h-1.5 bg-gray-400 rounded-full flex-shrink-0"></span>
                        <span v-if="typeof v === 'string'">{{ v }}</span>
                        <!-- Complex Object in Array (e.g. Action Items) -->
                        <div v-else class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                            <div v-for="(subVal, subKey) in v" :key="subKey" class="text-sm">
                                <span class="font-semibold text-gray-600 dark:text-gray-400 capitalize">{{ String(subKey).replace(/_/g, ' ') }}:</span>
                                <span class="ml-1 text-gray-800 dark:text-gray-200">{{ subVal }}</span>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Value: Object (Nested) -->
                <div v-else class="pl-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                     <div v-for="(subVal, subKey) in value" :key="subKey" class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ String(subKey).replace(/_/g, ' ') }}</span>
                        <span class="text-gray-800 dark:text-gray-200 font-medium">{{ subVal }}</span>
                     </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Case: Empty/Null -->
    <div v-else class="text-center py-10 text-gray-400">
        <p>No summary available.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ summaries: any }>()
</script> 