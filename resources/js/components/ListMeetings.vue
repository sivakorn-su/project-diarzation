<script setup lang="ts">
import { ref, computed, watch, watchEffect } from 'vue';
import { router } from '@inertiajs/vue3';

type Status = 'pending'|'processing'|'done'|'failed'|undefined;

const props = defineProps<{
  meetings: {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    user?: { name: string; email: string; };
    info?: {
      description: string;
      media_paths: string;
      audio_path: string;
      audio_length: number;
      transcript_json: {
        data: { start: number|string; end: number|string; speaker: string; filename: string; text: string; avg_probability: number|string; llm_corrected_text: string; }[];
        count_speaker: { speaker: string; count: number|string }[];
        summaries: string[]|string;
        video_path: string;
        num_speakers: number;
        speaker_array: string[];
        total_sentence: number;
      };
      status?: Status;
    };
  }[];
}>();

/* ---------- search/sort/pagination ---------- */
const search = ref('');
const debouncedSearch = ref('');
const sortKey = ref<'title'|'user'|'date'|'status'>('date');
const sortOrder = ref<'asc'|'desc'>('desc');
const currentPage = ref(1);
const pageSize = 5;

// debounce 250ms
let t: number | undefined;
watch(search, (v) => {
  if (t) clearTimeout(t);
  // @ts-ignore
  t = setTimeout(() => { debouncedSearch.value = v; }, 250);
});

// status helpers
const statusOrder: Record<Exclude<Status, undefined>, number> = {
  pending: 0,
  processing: 1,
  done: 2,
  failed: 3,
};
const statusBadgeClass = (s: Status) => {
  switch (s) {
    case 'pending': return 'bg-slate-100 text-slate-700';
    case 'processing': return 'bg-amber-100 text-amber-700';
    case 'done': return 'bg-emerald-100 text-emerald-700';
    case 'failed': return 'bg-rose-100 text-rose-700';
    default: return 'bg-gray-100 text-gray-600';
  }
};

const filteredAllMeetings = computed(() => {
  const q = debouncedSearch.value.trim().toLowerCase();
  let filtered = props.meetings.filter(m => {
    if (!q) return true;
    const user = m.user?.name?.toLowerCase() || '';
    const title = m.title?.toLowerCase() || '';
    return title.includes(q) || user.includes(q);
  });

  filtered = filtered.slice(); // copy before sort

  if (sortKey.value === 'title') {
    filtered.sort((a, b) =>
      sortOrder.value === 'asc'
        ? a.title.localeCompare(b.title)
        : b.title.localeCompare(a.title)
    );
  } else if (sortKey.value === 'user') {
    filtered.sort((a, b) =>
      sortOrder.value === 'asc'
        ? (a.user?.name || '').localeCompare(b.user?.name || '')
        : (b.user?.name || '').localeCompare(a.user?.name || '')
    );
  } else if (sortKey.value === 'date') {
    filtered.sort((a, b) => {
      const aDate = new Date(a.start_date || 0).getTime();
      const bDate = new Date(b.start_date || 0).getTime();
      return sortOrder.value === 'asc' ? aDate - bDate : bDate - aDate;
    });
  } else if (sortKey.value === 'status') {
    filtered.sort((a, b) => {
      const av = statusOrder[a.info?.status as keyof typeof statusOrder] ?? 99;
      const bv = statusOrder[b.info?.status as keyof typeof statusOrder] ?? 99;
      return sortOrder.value === 'asc' ? av - bv : bv - av;
    });
  }

  return filtered;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAllMeetings.value.length / pageSize)));
const paginatedMeetings = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredAllMeetings.value.slice(start, start + pageSize);
});

// reset page when search/sort changes
watch([debouncedSearch, sortKey, sortOrder], () => { currentPage.value = 1; });

function handleSort(key: 'title'|'user'|'date'|'status') {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = key === 'date' ? 'desc' : 'asc';
  }
}

function goToMeeting(meetingId: number) {
  if (!meetingId) return;
  router.visit(route('meetings.show', meetingId));
}

function formatDate(date?: string) {
  if (!date) return '-';
  const d = new Date(date);
  if (Number.isNaN(d.getTime())) return '-';
  return d.toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: '2-digit',
    hour: '2-digit', minute: '2-digit',
  });
}

function getInitials(name?: string) {
  if (!name) return '?';
  return name.split(' ').filter(Boolean).map(n => n[0]).join('').toUpperCase();
}

function goToPage(page: number) {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
}
</script>

<template>
  <div class="bg-white dark:bg-gray-900 p-4 flex flex-col gap-4">
    <!-- search -->
    <div class="flex items-center gap-2 mb-2">
      <input
        v-model="search"
        type="text"
        placeholder="Search meeting or user..."
        class="w-full max-w-md rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white shadow-sm transition"
      />
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full table-auto divide-y divide-gray-200 dark:divide-gray-700 rounded-xl overflow-hidden">
        <thead class="sticky top-0 z-10">
          <tr class="bg-blue-50 dark:bg-blue-950">
            <th @click="handleSort('title')" class="cursor-pointer px-6 py-3 text-left text-xs font-bold text-blue-700 dark:text-blue-300 tracking-wider select-none">
              Title <span v-if="sortKey === 'title'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
            </th>
            <th @click="handleSort('status')" class="cursor-pointer px-6 py-3 text-left text-xs font-bold text-blue-700 dark:text-blue-300 tracking-wider select-none">
              Status <span v-if="sortKey === 'status'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
            </th>
            <th @click="handleSort('date')" class="cursor-pointer px-6 py-3 text-left text-xs font-bold text-blue-700 dark:text-blue-300 tracking-wider select-none">
              Date <span v-if="sortKey === 'date'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
            </th>
            <th @click="handleSort('user')" class="cursor-pointer px-6 py-3 text-left text-xs font-bold text-blue-700 dark:text-blue-300 tracking-wider select-none">
              User <span v-if="sortKey === 'user'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
            </th>
          </tr>
        </thead>
        
        <tbody>
          <tr
            v-for="(meeting, idx) in paginatedMeetings"
            :key="meeting.id"
            @click="goToMeeting(meeting.id)"
            :class="[
              'transition cursor-pointer group',
              idx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800',
              'hover:bg-blue-50 dark:hover:bg-blue-900'
            ]"
          >
            <td class="px-6 py-3 text-sm text-blue-700 dark:text-blue-300 font-semibold align-middle">
              {{ meeting.title || '-' }}
            </td>

            <td class="px-6 py-3 text-sm align-middle">
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
                :class="statusBadgeClass(meeting.info?.status)"
              >
                {{ meeting.info?.status ?? '—' }}
              </span>
            </td>

            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-200 align-middle">
              <span class="block">{{ formatDate(meeting.start_date) }}</span>
              <span class="block text-xs text-gray-400">to {{ formatDate(meeting.end_date) }}</span>
            </td>

            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-200 align-middle">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-bold text-xs">
                  {{ getInitials(meeting.user?.name) }}
                </span>
                <span>{{ meeting.user?.name || 'Unknown' }}</span>
              </div>
            </td>
          </tr>

          <tr v-if="!paginatedMeetings.length">
            <td colspan="4" class="text-center text-gray-400 py-6">No meetings found.</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-end items-center gap-2 mt-4">
        <button
          class="px-2.5 py-1 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 disabled:opacity-50 text-xs"
          :disabled="currentPage === 1"
          @click="goToPage(currentPage - 1)"
        >
          Prev
        </button>

        <button
          v-for="page in totalPages"
          :key="page"
          class="px-2.5 py-1 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 font-semibold text-xs"
          :class="{ 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 border-blue-400 dark:border-blue-700': currentPage === page }"
          @click="goToPage(page)"
        >
          {{ page }}
        </button>

        <button
          class="px-2.5 py-1 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 disabled:opacity-50 text-xs"
          :disabled="currentPage === totalPages"
          @click="goToPage(currentPage + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
