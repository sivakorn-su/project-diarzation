<template>
  <Head title="OCR เทียบเอกสาร" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-white dark:bg-gray-950 min-h-screen py-8 px-4">
      <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="text-3xl font-extrabold text-sky-500 tracking-tight">OCR เทียบเอกสาร</h1>
        <span class="text-sm text-gray-400">|</span>
        <span class="text-sm text-gray-500 dark:text-gray-400">อัปโหลดต้นฉบับและฉบับแก้ไขเพื่อดูความแตกต่างอย่างละเอียด</span>
      </div>

      <!-- Mode selector -->
      <div class="grid gap-4 sm:grid-cols-2 mb-8">
        <Link
          v-for="card in modeCards"
          :key="card.title"
          :href="card.href"
          class="group block"
          preserve-scroll
        >
          <Card
            :class="[
              'transition-all duration-200 border-2 rounded-2xl h-full backdrop-blur-sm',
              card.active
                ? 'border-sky-400 shadow-xl shadow-sky-100/60 dark:shadow-sky-900/40'
                : 'border-transparent hover:border-sky-200 dark:hover:border-sky-800/70 bg-gray-50/60 dark:bg-gray-900/40'
            ]"
          >
            <CardHeader class="flex flex-row items-center gap-4">
              <div
                :class="[
                  'w-12 h-12 rounded-2xl flex items-center justify-center transition-colors',
                  card.active ? 'bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-white text-gray-500 dark:bg-gray-900'
                ]"
              >
                <component :is="card.icon" class="h-5 w-5" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <CardTitle class="text-lg">{{ card.title }}</CardTitle>
                  <span v-if="card.active" class="text-xs px-2 py-0.5 rounded-full bg-sky-100 text-sky-600 dark:bg-sky-900/60 dark:text-sky-300">ใช้งานอยู่</span>
                </div>
                <CardDescription>{{ card.description }}</CardDescription>
              </div>
            </CardHeader>
          </Card>
        </Link>
      </div>

      <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
        <div class="space-y-6">
          <div class="grid gap-4 md:grid-cols-2">
          <section class="h-full">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">ไฟล์ต้นฉบับ</h2>
            <div
              class="rounded-2xl border-2 border-dashed p-6 transition cursor-pointer"
              :class="[
                originalFile ? 'border-emerald-400 bg-emerald-50/50 dark:bg-emerald-900/20' : 'border-gray-300/70 dark:border-gray-700',
                dragState.original && 'border-sky-400 bg-sky-50/60 dark:bg-sky-900/40'
              ]"
              @click="openFilePicker('original')"
              @dragover.prevent="onDragOver('original')"
              @dragleave.prevent="onDragLeave('original')"
              @drop.prevent="onDrop('original', $event)"
            >
              <input type="file" ref="originalInput" class="hidden" :accept="accepts" @change="onFileChange('original', $event)" />
              <div v-if="!originalFile" class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                  <UploadCloud class="h-6 w-6 text-gray-500" />
                </div>
                <p class="font-semibold text-gray-800 dark:text-gray-100">ลากไฟล์มาวาง หรือคลิกเพื่อเลือก</p>
                <p class="text-sm text-gray-500">รองรับ PDF และรูปภาพสูงสุด {{ maxSizeMb }}MB</p>
              </div>
              <div v-else class="space-y-3">
                <p class="text-sm text-gray-500">ไฟล์ที่เลือก</p>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                  <span class="px-3 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">{{ originalFile.name }}</span>
                  <span class="text-xs text-gray-400">{{ formatBytes(originalFile.size) }}</span>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">ต้นฉบับ</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                  <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800" @click.stop="openFilePicker('original')">เปลี่ยนไฟล์</button>
                  <button type="button" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50" @click.stop="clearFile('original')">ลบไฟล์</button>
                </div>
              </div>
            </div>
            <p v-if="inputErrors.original" class="mt-2 text-sm text-red-500">{{ inputErrors.original }}</p>
          </section>

          <section class="h-full">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">ไฟล์ฉบับแก้ไข</h2>
            <div
              class="rounded-2xl border-2 border-dashed p-6 transition cursor-pointer"
              :class="[
                revisedFile ? 'border-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-gray-300/70 dark:border-gray-700',
                dragState.revised && 'border-sky-400 bg-sky-50/60 dark:bg-sky-900/40'
              ]"
              @click="openFilePicker('revised')"
              @dragover.prevent="onDragOver('revised')"
              @dragleave.prevent="onDragLeave('revised')"
              @drop.prevent="onDrop('revised', $event)"
            >
              <input type="file" ref="revisedInput" class="hidden" :accept="accepts" @change="onFileChange('revised', $event)" />
              <div v-if="!revisedFile" class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                  <UploadCloud class="h-6 w-6 text-gray-500" />
                </div>
                <p class="font-semibold text-gray-800 dark:text-gray-100">เลือกรายการฉบับแก้ไข</p>
                <p class="text-sm text-gray-500">รองรับ PDF และรูปภาพสูงสุด {{ maxSizeMb }}MB</p>
              </div>
              <div v-else class="space-y-3">
                <p class="text-sm text-gray-500">ไฟล์ที่เลือก</p>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                  <span class="px-3 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">{{ revisedFile.name }}</span>
                  <span class="text-xs text-gray-400">{{ formatBytes(revisedFile.size) }}</span>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">ฉบับแก้ไข</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                  <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800" @click.stop="openFilePicker('revised')">เปลี่ยนไฟล์</button>
                  <button type="button" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50" @click.stop="clearFile('revised')">ลบไฟล์</button>
                </div>
              </div>
            </div>
            <p v-if="inputErrors.revised" class="mt-2 text-sm text-red-500">{{ inputErrors.revised }}</p>
          </section>
          </div>

          <div class="flex flex-wrap gap-2 text-xs text-gray-500">
            <span>ไฟล์ที่รองรับ:</span>
            <span
              v-for="format in supportedFormats"
              :key="format"
              class="px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300"
            >{{ format }}</span>
          </div>
        </div>

        <aside>
          <Card class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-slate-50/60 dark:bg-gray-900/40">
            <CardHeader>
              <CardTitle class="text-lg">วิธีใช้งาน</CardTitle>
              <CardDescription>ทำตาม 3 ขั้นตอนง่าย ๆ เพื่อให้ระบบช่วยจับการเปลี่ยนแปลงสำคัญ</CardDescription>
            </CardHeader>
            <CardContent>
              <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <li v-for="(step, index) in instructions" :key="step" class="flex items-start gap-3">
                  <div class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-200 text-xs font-semibold">{{ index + 1 }}</div>
                  <span>{{ step }}</span>
                </li>
              </ol>
              <div class="mt-6 rounded-2xl border border-sky-200 dark:border-sky-800 bg-white/70 dark:bg-gray-950/40 p-4 text-xs text-sky-700 dark:text-sky-300">
                ระบบไม่เก็บไฟล์ของคุณไว้ ใช้เพื่อการเปรียบเทียบเท่านั้น
              </div>
            </CardContent>
          </Card>
        </aside>
      </div>

      <div class="mt-6 flex flex-wrap items-center gap-4">
        <button
          type="button"
          @click="submitComparison"
          :disabled="!canSubmit"
          :class="[
            'inline-flex items-center gap-2 rounded-xl px-5 py-3 text-white transition',
            canSubmit ? 'bg-sky-600 hover:bg-sky-700' : 'bg-sky-300 cursor-not-allowed'
          ]"
        >
          <component :is="isSubmitting ? Loader : FileDiff" class="h-5 w-5" />
          <span>{{ isSubmitting ? 'กำลังเทียบเอกสาร…' : 'เปรียบเทียบเอกสาร' }}</span>
        </button>
        <p v-if="statusMessage" class="text-sm text-emerald-600 dark:text-emerald-300">{{ statusMessage }}</p>
      </div>
      <p v-if="formError" class="mt-2 text-sm text-red-500">{{ formError }}</p>

      <div class="mt-10">
        <div class="flex flex-wrap items-center gap-3">
          <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">ผลการเทียบเอกสาร</h2>
          <span
            v-if="analyzedAt"
            class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300"
          >อัปเดตล่าสุด {{ analyzedAt }}</span>
        </div>

        <div v-if="comparisonResults.length" class="mt-6 space-y-6">
          <div class="grid gap-4 md:grid-cols-4">
            <div
              v-for="summary in severitySummary"
              :key="summary.level"
              class="rounded-2xl border p-4"
              :class="[
                summary.style.summaryBorder,
                summary.style.summaryBg
              ]"
            >
              <p class="text-xs uppercase tracking-wide">{{ summary.label }}</p>
              <p class="text-3xl font-semibold">{{ summary.count }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-300">รายการ</p>
            </div>
          </div>

          <div class="space-y-4">
            <div
              v-for="(change, index) in comparisonResults"
              :key="change.field_name + index"
              class="rounded-2xl border p-5 bg-white dark:bg-gray-900/60"
              :class="changeStyles(change.severity).border"
            >
              <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1">
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ change.field_name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-300">{{ change.description || 'ไม่มีคำอธิบายเพิ่มเติม' }}</p>
                  <p v-if="change.difference" class="mt-1 text-sm text-amber-600 dark:text-amber-400">ความแตกต่าง: {{ change.difference }}</p>
                </div>
                <div class="flex flex-col items-end gap-2">
                  <span class="text-xs font-semibold px-3 py-1 rounded-full" :class="changeStyles(change.severity).badge">
                    {{ severityLabels[change.severity] || change.severity }}
                  </span>
                  <span v-if="change.is_semantic_equivalent" class="text-xs px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300">
                    ความหมายเหมือนกัน
                  </span>
                </div>
              </div>

              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl p-3 bg-rose-50/70 dark:bg-rose-950/30">
                  <div class="flex items-center justify-between mb-1">
                    <p class="text-xs uppercase text-rose-500 dark:text-rose-200">ต้นฉบับ (Doc 1)</p>
                    <span v-if="change.doc1_page" class="text-xs px-2 py-0.5 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300">หน้า {{ change.doc1_page }}</span>
                  </div>
                  <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ change.old_value ?? '-' }}</p>
                </div>
                <div class="rounded-2xl p-3 bg-emerald-50/70 dark:bg-emerald-950/30">
                  <div class="flex items-center justify-between mb-1">
                    <p class="text-xs uppercase text-emerald-600 dark:text-emerald-300">ฉบับแก้ไข (Doc 2)</p>
                    <span v-if="change.doc2_page" class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">หน้า {{ change.doc2_page }}</span>
                  </div>
                  <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ change.new_value ?? '-' }}</p>
                </div>
              </div>

              <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="px-2 py-1 rounded-full uppercase tracking-wide" :class="changeTypeStyles(change.change_type)">{{ changeTypeLabels[change.change_type] || change.change_type }}</span>
                <span v-if="change.field_type" class="px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ change.field_type }}</span>
              </div>
            </div>
          </div>

          <!-- Raw Documents Section -->
          <div v-if="document1Raw || document2Raw" class="mt-8">
            <div class="flex items-center gap-3 mb-4">
              <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">ข้อความจากเอกสาร</h3>
              <button
                type="button"
                @click="showRawDocuments = !showRawDocuments"
                class="text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
              >
                {{ showRawDocuments ? 'ซ่อน' : 'แสดง' }}
              </button>
            </div>
            
            <div v-show="showRawDocuments" class="grid gap-4 lg:grid-cols-2">
              <!-- Document 1 Raw -->
              <div v-if="document1Raw" class="rounded-2xl border border-rose-200 dark:border-rose-800 bg-rose-50/50 dark:bg-rose-950/20 overflow-hidden">
                <div class="px-4 py-3 bg-rose-100/70 dark:bg-rose-900/40 border-b border-rose-200 dark:border-rose-800">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                    <h4 class="font-semibold text-rose-700 dark:text-rose-300">เอกสารต้นฉบับ (Doc 1)</h4>
                  </div>
                </div>
                <div class="p-4 max-h-[600px] overflow-y-auto doc-content" v-html="formatRawContent(document1Raw)"></div>
              </div>
              
              <!-- Document 2 Raw -->
              <div v-if="document2Raw" class="rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 overflow-hidden">
                <div class="px-4 py-3 bg-emerald-100/70 dark:bg-emerald-900/40 border-b border-emerald-200 dark:border-emerald-800">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <h4 class="font-semibold text-emerald-700 dark:text-emerald-300">เอกสารฉบับแก้ไข (Doc 2)</h4>
                  </div>
                </div>
                <div class="p-4 max-h-[600px] overflow-y-auto doc-content" v-html="formatRawContent(document2Raw)"></div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/30 p-6 text-center text-gray-500">
          อัปโหลดไฟล์ต้นฉบับและฉบับแก้ไขเพื่อดูผลลัพธ์การเปรียบเทียบที่นี่
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import { AlertCircle, FileDiff, Loader, ScanText, UploadCloud } from 'lucide-vue-next'
import type { LucideIcon } from 'lucide-vue-next'

interface ComparisonResult {
  field_name: string
  field_type: string
  old_value: string | null
  new_value: string | null
  doc1_page: number | null
  doc2_page: number | null
  change_type: 'removed' | 'relocated' | 'modified' | 'added' | string
  severity: 'CRITICAL' | 'HIGH' | 'MEDIUM' | 'LOW' | string
  description: string | null
  difference: string | null
  is_semantic_equivalent: boolean
}

type FileSlot = 'original' | 'revised'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'OCR', href: '/ocr' },
  { title: 'เทียบเอกสาร', href: route('ocr.compare') },
]

interface ModeCard {
  title: string
  description: string
  href: string
  icon: LucideIcon
  active: boolean
}

const modeCards: ModeCard[] = [
  {
    title: 'OCR ปกติ',
    description: 'ดึงข้อความจากรูปภาพหรือ PDF',
    href: route('ocr'),
    icon: ScanText,
    active: false,
  },
  {
    title: 'OCR เทียบเอกสาร',
    description: 'เปรียบเทียบต้นฉบับกับฉบับแก้ไข',
    href: route('ocr.compare'),
    icon: FileDiff,
    active: true,
  },
]

const instructions = [
  'อัปโหลดไฟล์ต้นฉบับและไฟล์ฉบับแก้ไข',
  'กดปุ่ม “เปรียบเทียบเอกสาร” เพื่อส่งเข้าระบบ',
  'ตรวจสอบผลการเปลี่ยนแปลงและผลกระทบที่เกิดขึ้น',
]

const accepts = 'application/pdf,image/*'
const supportedFormats = ['PDF', 'JPG', 'PNG', 'WEBP']
const maxSizeMb = 50

const originalFile = ref<File | null>(null)
const revisedFile = ref<File | null>(null)
const originalInput = ref<HTMLInputElement | null>(null)
const revisedInput = ref<HTMLInputElement | null>(null)

const dragState = reactive<Record<FileSlot, boolean>>({ original: false, revised: false })
const inputErrors = reactive<Record<FileSlot, string | null>>({ original: null, revised: null })
const formError = ref<string | null>(null)
const isSubmitting = ref(false)
const statusMessage = ref<string | null>(null)
const analyzedAt = ref<string | null>(null)
const comparisonResults = ref<ComparisonResult[]>([])
const document1Raw = ref<string | null>(null)
const document2Raw = ref<string | null>(null)
const showRawDocuments = ref(false)

const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
const analyzeEndpoint =(import.meta.env.VITE_OCR_COMPARE_ENDPOINT as string | undefined) ||''

const canSubmit = computed(() => Boolean(originalFile.value && revisedFile.value) && !isSubmitting.value)

const severityOrder = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'NONE'] as const
const severityLabels: Record<string, string> = {
  CRITICAL: 'วิกฤต',
  HIGH: 'สูง',
  MEDIUM: 'กลาง',
  LOW: 'ต่ำ',
  NONE: 'ไม่มีการเปลี่ยนแปลง',
}

const severityStyles: Record<string, { badge: string; border: string; summaryBorder: string; summaryBg: string; chip: string }> = {
  CRITICAL: {
    badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    border: 'border-red-200 dark:border-red-800',
    summaryBorder: 'border-red-200 dark:border-red-800',
    summaryBg: 'bg-red-50/70 dark:bg-red-950/30',
    chip: 'bg-red-50 text-red-600 dark:bg-red-900/40 dark:text-red-200',
  },
  HIGH: {
    badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    border: 'border-amber-200 dark:border-amber-800',
    summaryBorder: 'border-amber-200 dark:border-amber-800',
    summaryBg: 'bg-amber-50/70 dark:bg-amber-950/30',
    chip: 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
  },
  MEDIUM: {
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200',
    border: 'border-blue-200 dark:border-blue-800',
    summaryBorder: 'border-blue-200 dark:border-blue-800',
    summaryBg: 'bg-blue-50/70 dark:bg-blue-950/30',
    chip: 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
  },
  LOW: {
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
    border: 'border-emerald-200 dark:border-emerald-800',
    summaryBorder: 'border-emerald-200 dark:border-emerald-800',
    summaryBg: 'bg-emerald-50/70 dark:bg-emerald-950/30',
    chip: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
  },
  NONE: {
    badge: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-200',
    border: 'border-gray-200 dark:border-gray-700',
    summaryBorder: 'border-gray-200 dark:border-gray-700',
    summaryBg: 'bg-gray-50/70 dark:bg-gray-900/40',
    chip: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-200',
  },
  default: {
    badge: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-200',
    border: 'border-gray-200 dark:border-gray-700',
    summaryBorder: 'border-gray-200 dark:border-gray-700',
    summaryBg: 'bg-gray-50/70 dark:bg-gray-900/40',
    chip: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-200',
  },
}

const changeTypeLabels: Record<string, string> = {
  removed: 'ถูกลบ',
  relocated: 'ย้ายตำแหน่ง',
  modified: 'แก้ไข',
  added: 'เพิ่มใหม่',
}

const changeTypeStylesMap: Record<string, string> = {
  removed: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  relocated: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
  modified: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  added: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
  default: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
}

function changeTypeStyles(changeType: string): string {
  return changeTypeStylesMap[changeType] || changeTypeStylesMap.default
}

const severitySummary = computed(() =>
  severityOrder.map((level) => {
    const count = comparisonResults.value.filter((item) => item.severity === level).length
    return {
      level,
      label: severityLabels[level] || level,
      count,
      style: severityStyles[level] || severityStyles.default,
    }
  }),
)

function changeStyles(severity: ComparisonResult['severity']) {
  return severityStyles[severity] || severityStyles.default
}

function openFilePicker(slot: FileSlot) {
  const target = slot === 'original' ? originalInput.value : revisedInput.value
  target?.click()
}

function onFileChange(slot: FileSlot, event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) assignFile(slot, file)
}

function onDragOver(slot: FileSlot) {
  dragState[slot] = true
}

function onDragLeave(slot: FileSlot) {
  dragState[slot] = false
}

function onDrop(slot: FileSlot, event: DragEvent) {
  dragState[slot] = false
  const file = event.dataTransfer?.files?.[0]
  if (file) assignFile(slot, file)
}

function assignFile(slot: FileSlot, file: File) {
  inputErrors[slot] = null
  const isSupported = file.type === 'application/pdf' || file.type.startsWith('image/')
  if (!isSupported) {
    inputErrors[slot] = 'รองรับเฉพาะไฟล์ PDF หรือรูปภาพ'
    return
  }
  if (file.size > maxSizeMb * 1024 * 1024) {
    inputErrors[slot] = `ไฟล์ต้องไม่เกิน ${maxSizeMb}MB`
    return
  }
  if (slot === 'original') {
    originalFile.value = file
  } else {
    revisedFile.value = file
  }
}

function clearFile(slot: FileSlot) {
  if (slot === 'original') {
    originalFile.value = null
    if (originalInput.value) originalInput.value.value = ''
  } else {
    revisedFile.value = null
    if (revisedInput.value) revisedInput.value.value = ''
  }
  inputErrors[slot] = null
}

function formatBytes(bytes: number) {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`
}

function formatDateTime(date: Date) {
  try {
    return new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
  } catch {
    return date.toLocaleString()
  }
}

async function submitComparison() {
  if (!originalFile.value || !revisedFile.value) {
    formError.value = 'กรุณาเลือกไฟล์ต้นฉบับและฉบับแก้ไขให้ครบ'
    return
  }
  formError.value = null
  statusMessage.value = null
  isSubmitting.value = true

  const formData = new FormData()
  formData.append('document1', originalFile.value)
  formData.append('document2', revisedFile.value)

  try {
    const response = await fetch(analyzeEndpoint, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: formData,
    })

    const payloadText = await response.text()
    let payload: any = null
    if (payloadText) {
      try {
        payload = JSON.parse(payloadText)
      } catch {
        payload = payloadText
      }
    }

    if (!response.ok) {
      const message = typeof payload === 'string' ? payload : payload?.message || payload?.error || 'ไม่สามารถประมวลผลได้'
      throw new Error(message)
    }

    const changesSource = Array.isArray(payload)
      ? payload
      : payload?.changes ?? payload?.data ?? []
    const changes: ComparisonResult[] = Array.isArray(changesSource) ? changesSource : []
    comparisonResults.value = changes.sort((a, b) => {
      const weight = (severity: string) => {
        const index = severityOrder.indexOf(severity as (typeof severityOrder)[number])
        return index === -1 ? severityOrder.length : index
      }
      return weight(a.severity) - weight(b.severity)
    })
    
    // Extract raw documents if available
    document1Raw.value = payload?.document1_raw ?? null
    document2Raw.value = payload?.document2_raw ?? null
    
    analyzedAt.value = formatDateTime(new Date())
    statusMessage.value = (payload && !Array.isArray(payload) ? payload?.message : null) || `พบ ${changes.length} รายการที่มีการเปลี่ยนแปลง`
  } catch (error) {
    formError.value = (error as Error).message
  } finally {
    isSubmitting.value = false
  }
}

/**
 * Format raw document content - convert HTML tables to styled tables
 * and preserve other text formatting
 */
function formatRawContent(content: string): string {
  if (!content) return ''
  
  // Process content - keep HTML tables but escape other text for safety
  // Split by page markers and process each section
  let formatted = content
  
  // Add page separator styling
  formatted = formatted.replace(
    /===\s*Page\s*(\d+)\s*===/g,
    '<div class="page-separator"><span class="page-badge">หน้า $1</span></div>'
  )
  
  // Convert newlines to <br> for non-table content, but be careful with tables
  // First, protect tables by replacing them with placeholders
  const tables: string[] = []
  formatted = formatted.replace(/<table[\s\S]*?<\/table>/gi, (match) => {
    tables.push(match)
    return `__TABLE_PLACEHOLDER_${tables.length - 1}__`
  })
  
  // Now convert newlines to <br>
  formatted = formatted.replace(/\n/g, '<br>')
  
  // Restore tables
  tables.forEach((table, index) => {
    formatted = formatted.replace(`__TABLE_PLACEHOLDER_${index}__`, table)
  })
  
  // Style bold markers
  formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-semibold text-gray-800 dark:text-gray-100">$1</strong>')
  
  return formatted
}
</script>

<style scoped>
/* Document content styles */
.doc-content {
  font-size: 0.875rem;
  line-height: 1.625;
  color: #374151;
}

:root.dark .doc-content {
  color: #d1d5db;
}

.doc-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
  font-size: 0.75rem;
}

.doc-content :deep(table tr:first-child td),
.doc-content :deep(table th) {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #1f2937;
}

:root.dark .doc-content :deep(table tr:first-child td),
:root.dark .doc-content :deep(table th) {
  background-color: #1f2937;
  color: #e5e7eb;
}

.doc-content :deep(table td),
.doc-content :deep(table th) {
  border: 1px solid #d1d5db;
  padding: 0.5rem 0.75rem;
  text-align: left;
}

:root.dark .doc-content :deep(table td),
:root.dark .doc-content :deep(table th) {
  border-color: #4b5563;
}

.doc-content :deep(table tr:nth-child(even)) {
  background-color: rgba(255, 255, 255, 0.5);
}

:root.dark .doc-content :deep(table tr:nth-child(even)) {
  background-color: rgba(17, 24, 39, 0.5);
}

.doc-content :deep(table tr:hover) {
  background-color: #f9fafb;
}

:root.dark .doc-content :deep(table tr:hover) {
  background-color: rgba(31, 41, 55, 0.5);
}

.doc-content :deep(.page-separator) {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 1.5rem 0;
}

.doc-content :deep(.page-separator)::before,
.doc-content :deep(.page-separator)::after {
  content: '';
  flex: 1;
  height: 1px;
  background-color: #d1d5db;
}

:root.dark .doc-content :deep(.page-separator)::before,
:root.dark .doc-content :deep(.page-separator)::after {
  background-color: #4b5563;
}

.doc-content :deep(.page-badge) {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  background-color: #e0f2fe;
  color: #0369a1;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

:root.dark .doc-content :deep(.page-badge) {
  background-color: rgba(12, 74, 110, 0.4);
  color: #7dd3fc;
}
</style>
