<template>
    <Head title="OCR" />
    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="bg-white dark:bg-gray-950 min-h-screen py-8 px-4">
        <!-- Header -->
        <div class="mb-6 flex items-center gap-3">
          <h1 class="text-3xl font-extrabold text-sky-500 tracking-tight">OCR</h1>
            <span class="text-sm text-gray-400">|</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">ดึงข้อความจากรูปภาพหรือ PDF ด้วย OCR </span>
          <!-- แสดง chip ชื่อไฟล์ที่เลือก -->
          <span v-if="file" class="ml-2 text-xs px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 truncate max-w-[50vw]">
            {{ file.name }} • {{ formatBytes(file.size) }}
          </span>
        </div>

        <!-- Controls (ปุ่มอยู่นอกกล่องอัปโหลดชัด ๆ) -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <button
            type="button"
            @click="handleSubmit"
            :disabled="isSubmitting || !file || !apiKey"
            :class="[
              'inline-flex items-center gap-2 rounded-md px-4 py-2 text-white transition',
              isSubmitting || !file || !apiKey ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
            ]"
            title="ดึงข้อความจากรูป/PDF"
          >
            <component :is="isSubmitting ? Loader : FileUpIcon" class="h-5 w-5" />
            <span>{{ isSubmitting ? 'กำลังประมวลผล…' : 'ดึงข้อความ (OCR)' }}</span>
          </button>

          <button
            type="button"
            @click="openPicker"
            class="inline-flex items-center gap-2 rounded-md px-3 py-2 border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
            title="เลือกไฟล์ใหม่"
          >
            <UploadCloud class="h-4 w-4" />
            เลือกไฟล์
          </button>

          <button
            v-if="file"
            type="button"
            @click="clearFile"
            class="inline-flex items-center gap-2 rounded-md px-3 py-2 border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30"
            title="ลบไฟล์"
          >
            <X class="h-4 w-4" />
            ลบไฟล์
          </button>

          <div v-if="!apiKey" class="ml-auto flex items-center gap-2">
            <input
              v-model="apiKey"
              :type="showKey ? 'text' : 'password'"
              placeholder="API Key (VITE_TYPHOON_API_KEY)"
              class="w-[280px] rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
            />
            <button
              type="button"
              @click="showKey = !showKey"
              class="px-3 py-2 text-sm rounded-lg border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              {{ showKey ? 'Hide' : 'Show' }}
            </button>
          </div>
        </div>

        <!-- Dropzone -->
        <div
          @dragover="onDragOver"
          @dragleave="onDragLeave"
          @drop="onDrop"
          @click="openPicker"
          :class="[
            'relative border-2 border-dashed rounded-2xl p-8 cursor-pointer transition-all duration-200',
            isDragOver
              ? 'border-sky-400 bg-sky-50 dark:bg-sky-900/20'
              : file
                ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20'
                : 'border-gray-300 dark:border-gray-600 hover:border-sky-300 hover:bg-sky-50/50 dark:hover:bg-sky-900/10',
            errorMsg && 'border-red-300 bg-red-50 dark:bg-red-900/20'
          ]"
        >
          <input type="file" ref="fileInput" class="hidden" :accept="accepts" @change="onFileChange" />
          <div v-if="!file" class="text-center">
            <div class="w-16 h-16 bg-sky-100 dark:bg-sky-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <UploadCloud class="h-8 w-8 text-sky-500 dark:text-sky-400" />
            </div>
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ลากไฟล์มาวาง หรือคลิกเพื่อเลือก</h4>
            <p class="text-gray-500 dark:text-gray-400 mb-2">รองรับภาพ (JPG/PNG/WEBP) และ PDF</p>
            <div class="flex flex-wrap gap-2 justify-center text-xs">
              <span v-for="f in supportedFormats" :key="f" class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg">{{ f }}</span>
            </div>
          </div>

          <div v-else class="text-center">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
              <component :is="fileIcon" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">คลิกเพื่อเลือกไฟล์ใหม่ หรือใช้ปุ่มด้านบน</p>
          </div>

          <div v-if="errorMsg" class="flex items-center justify-center gap-2 text-red-600 dark:text-red-400 text-sm mt-3">
            <AlertCircle class="h-4 w-4" /> {{ errorMsg }}
          </div>
        </div>

        <!-- Uploaded Preview (โชว์ไฟล์ที่อัปโหลด) -->
        <div v-if="fileUrl" class="mt-6 grid md:grid-cols-1 gap-6">
          <div class="border rounded-xl overflow-hidden">
            <div class="px-4 py-2 border-b text-sm font-semibold text-gray-700 dark:text-gray-200 dark:border-gray-800 justify-between flex items-center">
                <div>
                    <span>Preview</span>
                    <div class="text-xs text-gray-400">แสดงตัวอย่างไฟล์ที่อัปโหลด (ถ้าเป็น PDF อาจใช้เวลานานหน่อย)</div>
                </div>
                <a :href="fileUrl" target="_blank" class="inline-flex items-center gap-2 my-2 px-3 py-2 rounded-md border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <ExternalLinkIcon class="h-4 w-4" />
              </a>
            </div>

            <div class="p-2">
              <img v-if="isImage" :src="fileUrl" alt="preview" class="max-h-[520px] mx-auto rounded-md object-contain" />
              <iframe
                v-else
                :src="fileUrl"
                class="w-full h-[520px] rounded-md"
                title="PDF preview"
              ></iframe>
            </div>
          </div>
        </div>

        <!-- Result -->
        <div v-if="resultText || rawJson" class="mt-8 space-y-3">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">ผลลัพธ์</span>
            <span v-if="resultMeta" class="text-xs text-gray-400">({{ resultMeta }})</span>
            <div class="ml-auto flex gap-2">
              <button @click="copyText" class="px-3 py-1.5 text-sm rounded border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">Copy</button>
              <button @click="downloadText" class="px-3 py-1.5 text-sm rounded border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">Download .txt</button>
              <button v-if="rawJson" @click="showJson = !showJson" class="px-3 py-1.5 text-sm rounded border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                {{ showJson ? 'Hide JSON' : 'Show JSON' }}
              </button>
            </div>
          </div>

          <textarea
            class="w-full min-h-[280px] rounded-lg border px-3 py-2 text-lg bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700"
            v-model="resultText"
            readonly
          />
          <pre v-if="showJson" class="w-full overflow-auto rounded-lg border px-3 py-2 text-xs bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800">{{ rawJson }}</pre>
        </div>
        <!-- === Together AI Actions === -->
<div class="mt-6 border rounded-xl">
  <div class="px-4 py-2 border-b text-sm font-semibold text-gray-700 dark:text-gray-200 dark:border-gray-800">
    สุ่มรายชื่อผู้โชคดี
  </div>

  <div class="p-4 space-y-3">
    <div v-if="!togetherApiKey" class="flex flex-wrap gap-2">
      <input
        v-model="togetherApiKey"
        :type="togetherShowKey ? 'text' : 'password'"
        placeholder="TOGETHER_API_KEY (หรือใส่ใน VITE_TOGETHER_API_KEY)"
        class="min-w-[260px] rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
      />
      <button
        type="button"
        @click="togetherShowKey = !togetherShowKey"
        class="px-3 py-2 text-sm rounded-lg border bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        {{ togetherShowKey ? 'Hide' : 'Show' }}
      </button>

      <select
        v-model="togetherModel"
        class="rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
        title="Together Model"
      >
        <option value="openai/gpt-oss-20b">openai/gpt-oss-20b</option>
        <!-- ใส่รุ่นอื่นที่คุณมีสิทธิ์ใช้ได้ตามต้องการ -->
      </select>
    </div>

    <div v-if="!togetherApiKey">
      <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Prompt</label>
      <input
        v-model="togetherPrompt"
        class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
        placeholder="อธิบาย/สรุปรายชื่อ 3 คนที่สุ่มได้เป็นภาษาไทยแบบสั้น ๆ"
      />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <button
        type="button"
        @click="randomPickThree"
        :disabled="!resultText"
        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400"
      >
        สุ่ม 3 รายชื่อจากผลลัพธ์
      </button>

      <div class="flex flex-wrap gap-2">
        <div v-for="(n, i) in pickedNames" :key="i"
              class="px-2 py-1 text-md rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
          {{ n }}
      </div>
      </div>

      <!-- <button
        type="button"
        @click="sendToTogether"
        :disabled="!togetherApiKey || pickedNames.length !== 3 || togetherLoading"
        class="ml-auto inline-flex items-center gap-2 rounded-md px-3 py-2 text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400"
      >
        {{ togetherLoading ? 'กำลังส่ง...' : 'ส่งไป Together AI' }}
      </button> -->
    </div>

    <div v-if="togetherError" class="text-sm text-red-600 dark:text-red-400">
      {{ togetherError }}
    </div>

    <div v-if="togetherResponse" class="mt-2">
      <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">Together Response</label>
      <textarea
        class="w-full min-h-[160px] rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700"
        :value="togetherResponse"
        readonly
      />
    </div>
  </div>
</div>

      </div>
    </AppLayout>
  </template>

  <script setup lang="ts">
  import AppLayout from '@/layouts/AppLayout.vue'
  import { type BreadcrumbItem } from '@/types'
  import { Head } from '@inertiajs/vue3'
  import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'
  import { UploadCloud, X, AlertCircle, FileUpIcon, Loader, ExternalLinkIcon} from 'lucide-vue-next'

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'OCR', href: '/ocr' },
  ]

  // --- State
  const apiKey = ref<string>(import.meta.env.VITE_TYPHOON_API_KEY || '')
  const showKey = ref<boolean>(false)

  const file = ref<File | null>(null)
  const fileInput = ref<HTMLInputElement | null>(null)
  const fileUrl = ref<string | null>(null)

  const isDragOver = ref(false)
  const isSubmitting = ref(false)
  const errorMsg = ref<string | null>(null)

  const resultText = ref<string>('')
  const rawJson = ref<string>('') // pretty JSON string
  const showJson = ref<boolean>(false)
  const resultMeta = ref<string>('')

  // --- UI
  const supportedFormats = ['JPG', 'PNG', 'WEBP', 'PDF']
  const accepts = 'image/*,application/pdf'

  const isPdf = computed(() => file.value?.type === 'application/pdf')
  const isImage = computed(() => !!file.value && file.value.type.startsWith('image/'))

  const fileIcon = computed(() => (isPdf.value ? FileUpIcon : UploadCloud))

  // --- object URL lifecycle
  watch(file, (f, prev) => {
    if (fileUrl.value) {
      URL.revokeObjectURL(fileUrl.value)
      fileUrl.value = null
    }
    if (f) fileUrl.value = URL.createObjectURL(f)
  })
  onBeforeUnmount(() => {
    if (fileUrl.value) URL.revokeObjectURL(fileUrl.value)
  })

  // Handlers
  function openPicker() {
    fileInput.value?.click()
  }
  function onDragOver(e: DragEvent) {
    e.preventDefault()
    isDragOver.value = true
  }
  function onDragLeave(e: DragEvent) {
    e.preventDefault()
    isDragOver.value = false
  }
  function onDrop(e: DragEvent) {
    e.preventDefault()
    isDragOver.value = false
    const f = e.dataTransfer?.files?.[0]
    if (f) assignFile(f)
  }
  function onFileChange(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0]
    if (f) assignFile(f)
  }
  function assignFile(f: File) {
    errorMsg.value = null
    const ok = f.type.startsWith('image/') || f.type === 'application/pdf'
    if (!ok) {
      errorMsg.value = 'รองรับเฉพาะรูปภาพหรือ PDF'
      return
    }
    if (f.size > 1024 * 1024 * 50) {
      errorMsg.value = 'ไฟล์ใหญ่เกิน 50MB'
      return
    }
    file.value = f
  }
  function clearFile() {
    file.value = null
    if (fileInput.value) fileInput.value.value = ''
  }

  // Utils
  function formatBytes(bytes: number) {
    if (!bytes) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  // OCR
  async function extractTextFromImage(imageFile: File, apiKey: string) {
    const formData = new FormData()
    formData.append('file', imageFile)
    formData.append('params', JSON.stringify({
      model: 'typhoon-ocr-preview',
      task_type: 'default',
      max_tokens: 16000,
      temperature: 0.1,
      top_p: 0.6,
      repetition_penalty: 1.2,
      // NOTE: ไม่ใส่ pages (ถ้าจะทั้งเอกสาร) หรือใส่เป็น [1,3,...] เท่านั้น
    }))

    const res = await fetch('https://api.opentyphoon.ai/v1/ocr', {
      method: 'POST',
      headers: { Authorization: `Bearer ${apiKey}` },
      body: formData,
    })

    let raw: any
    const isJson = res.headers.get('content-type')?.includes('application/json')
    raw = isJson ? await res.json() : await res.text()

    if (!res.ok) {
      const detail = typeof raw === 'string' ? raw : (raw?.detail || JSON.stringify(raw))
      throw new Error(detail || `HTTP ${res.status}`)
    }

    // parse results
    const out: string[] = []
    if (typeof raw === 'string') {
      out.push(raw)
    } else {
      rawJson.value = JSON.stringify(raw, null, 2)
      let pageIndex = 1
      for (const r of raw?.results ?? []) {
        if (r?.success && r?.message) {
          let content = r.message?.choices?.[0]?.message?.content ?? ''
          try {
            const parsed = JSON.parse(content)
            content = parsed?.natural_text || parsed?.text || content
          } catch { /* not JSON, keep as-is */ }
          out.push(`--- Page ${pageIndex} ---\n${content}`.trim())
        } else {
          const fname = r?.filename || 'unknown'
          out.push(`--- Page ${pageIndex} [ERROR: ${fname}] ---\n${r?.error || 'Unknown error'}`)
        }
        pageIndex++
      }
    }
    return out.join('\n\n')
  }

  async function handleSubmit() {
    if (!file.value) {
      errorMsg.value = 'ยังไม่ได้เลือกไฟล์'
      return
    }
    if (!apiKey.value) {
      errorMsg.value = 'โปรดใส่ API Key'
      return
    }
    errorMsg.value = null
    isSubmitting.value = true
    resultText.value = ''
    rawJson.value = ''
    resultMeta.value = ''
    showJson.value = false

    try {
      const t0 = Date.now()
      const text = await extractTextFromImage(file.value, apiKey.value)
      const dt = Date.now() - t0
      resultText.value = text || '[No text returned]'
      resultMeta.value = `${formatBytes(file.value.size)} • ${(dt / 1000).toFixed(1)}s`
    } catch (e: any) {
      errorMsg.value = e?.message || 'OCR ล้มเหลว'
    } finally {
      isSubmitting.value = false
    }
  }

  // Result utils
  async function copyText() {
    try { await navigator.clipboard.writeText(resultText.value || '') } catch {}
  }
  function downloadText() {
    const blob = new Blob([resultText.value || ''], { type: 'text/plain;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = (file.value?.name?.replace(/\.[^.]+$/, '') || 'ocr') + '.txt'
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  }

  onMounted(() => {})

  // === Together AI state ===
const togetherApiKey = ref<string>(import.meta.env.VITE_TOGETHER_API_KEY || '')
const togetherShowKey = ref<boolean>(false)
const togetherModel = ref<string>('openai/gpt-oss-20b')
const togetherPrompt = ref<string>('สรุป/อธิบายรายชื่อ 3 คนที่สุ่มได้แบบ bullet list ภาษาไทย สั้น กระชับ')

const pickedNames = ref<string[]>([])
const togetherLoading = ref<boolean>(false)
const togetherError = ref<string | null>(null)
const togetherResponse = ref<string>('')

// ดึงชื่อจาก resultText: ตัดตามบรรทัด, กรองว่าง, เดดู้พ์, เอาที่มีตัวอักษรจริง ๆ
function parseNamesFromText(text: string): string[] {
  return Array.from(
    new Set(
      text
        .split(/\r?\n/)
        .map(s => s.trim())
        .filter(s => s.length > 0)
        // กรองบรรทัดที่ดู “เป็นชื่อ” แบบหยาบ ๆ (มีตัวอักษร/ไทย/ช่องว่าง)
        .filter(s => /[A-Za-zก-๙]/.test(s))
        // ตัดกรณีเป็นหัวข้อ Page/--- ออก
        .filter(s => !/^---/.test(s))
    )
  ).slice(0, 500) // กันยาวเกิน
}

function randomPickThree() {
  pickedNames.value = []
  const names = parseNamesFromText(resultText.value || '')
  if (names.length < 3) {
    togetherError.value = `รายชื่อไม่พอ (${names.length}) ในผลลัพธ์`
    return
  }
  togetherError.value = null
  // สุ่มไม่ซ้ำ 3 รายชื่อ
  const seen = new Set<number>()
  while (pickedNames.value.length < 3) {
    const idx = Math.floor(Math.random() * names.length)
    if (!seen.has(idx)) {
      seen.add(idx)
      pickedNames.value.push(names[idx])
    }
  }
}

async function sendToTogether() {
  togetherError.value = null
  togetherResponse.value = ''
  if (!togetherApiKey.value) {
    togetherError.value = 'กรุณาใส่ TOGETHER_API_KEY'
    return
  }
  if (pickedNames.value.length !== 3) {
    togetherError.value = 'ต้องสุ่มให้ได้ครบ 3 คนก่อน'
    return
  }

  togetherLoading.value = true
  try {
    // === ส่งตรงไป Together REST (ระวังเรื่อง CORS/การเปิดเผยคีย์) ===
    const body = {
      model: togetherModel.value,
      messages: [
        { role: 'system', content: 'You are a helpful assistant. Reply in Thai.' },
        {
          role: 'user',
          content:
            `${togetherPrompt.value}\n\nรายชื่อ:\n- ${pickedNames.value[0]}\n- ${pickedNames.value[1]}\n- ${pickedNames.value[2]}`
        }
      ]
    }

    const res = await fetch('https://api.together.xyz/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${togetherApiKey.value}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body)
    })

    const data = await res.json().catch(() => null)
    if (!res.ok) {
      const msg = data?.error?.message || data?.detail || JSON.stringify(data)
      throw new Error(msg || `HTTP ${res.status}`)
    }

    // รูปแบบปกติ: choices[0].message.content
    togetherResponse.value = data?.choices?.[0]?.message?.content || JSON.stringify(data, null, 2)
  } catch (e: any) {
    togetherError.value = e?.message || 'เรียก Together ไม่สำเร็จ'
  } finally {
    togetherLoading.value = false
  }
}

  </script>
