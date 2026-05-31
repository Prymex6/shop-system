<template>
  <ManagerLayout :title="t('manager.marketing.bannergenerator.ai_banner_generator')">
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="route('tenant.manager.marketing.index')" class="text-gray-400 hover:text-gray-600 transition">
          <i class="fa-solid fa-arrow-left text-lg"></i>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            {{ t('manager.marketing.bannergenerator.ai_banner_and_video_generator') }}
          </h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ t('manager.marketing.bannergenerator.free_ad_creative_generation_without_an') }}
          </p>
        </div>
        <span
          class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-semibold border border-green-200"
        >
          <i class="fa-solid fa-circle text-xs text-green-500 animate-pulse"></i>
          {{ t('manager.marketing.bannergenerator.100_free') }}
        </span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT: Generator form -->
        <div class="lg:col-span-1 space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
              <i class="fa-solid fa-sliders text-blue-500"></i> {{ t('common.settings') }}
            </h2>

            <!-- Product select or custom -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.bannergenerator.product_optional')
              }}</label>
              <select
                v-model="selectedProduct"
                @change="fillFromProduct"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
              >
                <option :value="null">{{ t('manager.marketing.bannergenerator.enter_manually') }}</option>
                <option v-for="p in products" :key="p.id" :value="p">{{ p.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >{{ t('common.product_name') }} <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.product_name"
                type="text"
                :placeholder="t('manager.marketing.bannergenerator.e_g_wireless_bluetooth_headphones')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.bannergenerator.description_specification')
              }}</label>
              <textarea
                v-model="form.description"
                rows="2"
                :placeholder="t('manager.marketing.bannergenerator.e_g_active_noise_cancelling_30h')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.price_pln') }}</label>
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                placeholder="np. 149.99"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Size -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.bannergenerator.banner_format')
              }}</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="(s, key) in sizes"
                  :key="key"
                  type="button"
                  @click="form.size = key"
                  class="p-2 text-xs border rounded-lg transition text-left"
                  :class="
                    form.size === key
                      ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium'
                      : 'border-gray-200 text-gray-600 hover:border-gray-300'
                  "
                >
                  <i class="fa-regular fa-image mr-1"></i>{{ sizeShortLabel(key) }}
                  <span class="block text-[10px] opacity-60 mt-0.5">{{ s.width }}×{{ s.height }}</span>
                </button>
              </div>
            </div>

            <!-- Style -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{
                t('manager.marketing.bannergenerator.visual_style')
              }}</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="(label, key) in styles"
                  :key="key"
                  type="button"
                  @click="form.style = key"
                  class="py-2 px-3 text-xs border rounded-lg transition font-medium"
                  :class="
                    form.style === key
                      ? 'border-blue-500 bg-blue-50 text-blue-700'
                      : 'border-gray-200 text-gray-600 hover:border-gray-300'
                  "
                >
                  {{ label }}
                </button>
              </div>
            </div>

            <!-- Generate buttons -->
            <div class="space-y-2 pt-1">
              <button
                @click="generatePreview"
                :disabled="!form.product_name.trim() || loading"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <i v-if="loading" class="fa-solid fa-spinner animate-spin"></i>
                <i v-else class="fa-solid fa-wand-magic-sparkles"></i>
                {{ loading ? 'Generowanie...' : t('common.generate_an_ai_banner') }}
              </button>
              <p class="text-[11px] text-gray-400 text-center">
                <i class="fa-solid fa-info-circle mr-0.5"></i>
                {{ t('manager.marketing.bannergenerator.every_click_generates_a_different_banner') }}
              </p>
            </div>

            <!-- Error -->
            <div v-if="error" class="flex items-start gap-2 p-3 bg-red-50 rounded-lg text-xs text-red-700">
              <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0"></i>{{ error }}
            </div>
          </div>

          <!-- VIDEO section -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
              <i class="fa-solid fa-film text-purple-500"></i> {{ t('manager.marketing.bannergenerator.ad_video') }}
            </h2>

            <div v-if="!klingKeySet" class="text-sm space-y-3">
              <div
                class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs"
              >
                <i class="fa-solid fa-circle-info mt-0.5 shrink-0 text-amber-500"></i>
                <span
                  >{{ t('manager.marketing.bannergenerator.kling_ai_gives_you') }}
                  <strong>{{ t('manager.marketing.bannergenerator.66_free_videos_month') }}</strong>
                  {{ t('manager.marketing.bannergenerator.a_one_off_registration_is_needed') }}</span
                >
              </div>
              <ol class="text-xs text-gray-600 space-y-1.5 pl-2">
                <li class="flex gap-2">
                  <span class="font-bold text-blue-600 shrink-0">1.</span>
                  {{ t('manager.marketing.bannergenerator.go_to') }}
                  <a href="https://platform.kling.ai" target="_blank" class="text-blue-600 underline"
                    >platform.kling.ai</a
                  >
                </li>
                <li class="flex gap-2">
                  <span class="font-bold text-blue-600 shrink-0">2.</span>
                  {{ t('manager.marketing.bannergenerator.create_a_free_account_email') }}
                </li>
                <li class="flex gap-2">
                  <span class="font-bold text-blue-600 shrink-0">3.</span> API Keys → Create API Key
                </li>
                <li class="flex gap-2">
                  <span class="font-bold text-blue-600 shrink-0">4.</span>
                  {{ t('manager.marketing.bannergenerator.paste_the_key_into') }}
                  <Link :href="route('tenant.manager.settings')" class="text-blue-600 underline">{{
                    t('manager.marketing.bannergenerator.settings_integrations')
                  }}</Link>
                </li>
              </ol>
              <p class="text-[11px] text-gray-400">
                {{ t('manager.marketing.bannergenerator.alternatively_use_the_video_recorder_below') }}
              </p>
            </div>

            <div v-else class="space-y-3">
              <p class="text-xs text-green-700 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check"></i>
                {{ t('manager.marketing.bannergenerator.kling_ai_configured') }}
              </p>
              <button
                @click="generateVideo"
                :disabled="videoLoading || !form.product_name.trim()"
                class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <i v-if="videoLoading" class="fa-solid fa-spinner animate-spin"></i>
                <i v-else class="fa-solid fa-video"></i>
                {{ videoLoading ? 'Generowanie (~2-3 min)...' : t('common.generate_an_ai_video') }}
              </button>
            </div>

            <!-- Video local recorder -->
            <div class="border-t border-gray-100 pt-3">
              <p class="text-xs font-medium text-gray-700 mb-2">
                <i class="fa-solid fa-circle-dot text-red-500 mr-1"></i>
                {{ t('manager.marketing.bannergenerator.video_from_an_animation_no_api') }}
              </p>
              <p class="text-xs text-gray-500 mb-3">
                {{ t('manager.marketing.bannergenerator.record_an_animated_ad_from_a') }}
              </p>
              <input ref="videoImageInput" type="file" accept="image/*" class="hidden" @change="onVideoImageSelected" />
              <button
                @click="$refs.videoImageInput.click()"
                class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-xs text-gray-500 hover:border-blue-400 hover:text-blue-600 transition"
              >
                <i class="fa-solid fa-image mr-1.5"></i>
                {{ videoImageFile ? videoImageFile.name : t('common.choose_a_product_photo') }}
              </button>
              <button
                v-if="videoImageFile"
                @click="recordAnimation"
                :disabled="recording"
                class="mt-2 w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <i v-if="recording" class="fa-solid fa-circle animate-pulse text-red-200"></i>
                <i v-else class="fa-solid fa-circle-dot"></i>
                {{ recording ? 'Nagrywanie 8s...' : t('common.record_an_animation') }}
              </button>
              <a
                v-if="recordedVideoUrl"
                :href="recordedVideoUrl"
                download="reklama-produkt.webm"
                class="mt-2 flex items-center justify-center gap-2 w-full py-2 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700 transition"
              >
                <i class="fa-solid fa-download"></i> {{ t('manager.marketing.bannergenerator.download_video_webm') }}
              </a>
            </div>
          </div>
        </div>

        <!-- RIGHT: Preview + gallery -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Banner preview -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-semibold text-gray-900">{{ t('manager.marketing.bannergenerator.banner_preview') }}</h2>
              <div class="flex gap-2" v-if="previewUrl">
                <button
                  @click="regenerate"
                  :disabled="loading"
                  :title="t('manager.marketing.bannergenerator.generate_a_new_version')"
                  class="p-2 text-gray-500 hover:text-blue-600 border border-gray-200 rounded-lg text-sm transition disabled:opacity-50"
                >
                  <i class="fa-solid fa-arrows-rotate" :class="loading ? 'animate-spin' : ''"></i>
                </button>
                <button
                  @click="saveBanner"
                  :disabled="saving"
                  class="flex items-center gap-1.5 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition disabled:opacity-50"
                >
                  <i v-if="saving" class="fa-solid fa-spinner animate-spin"></i>
                  <i v-else class="fa-solid fa-floppy-disk"></i>
                  {{ saving ? 'Zapisywanie...' : t('common.save') }}
                </button>
                <a
                  :href="previewUrl"
                  download
                  target="_blank"
                  class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition"
                >
                  <i class="fa-solid fa-download"></i> {{ t('common.download') }}
                </a>
              </div>
            </div>

            <!-- Preview area -->
            <div class="relative bg-gray-100 rounded-xl overflow-hidden flex items-center justify-center min-h-[240px]">
              <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 z-10">
                <div
                  class="w-12 h-12 rounded-full border-4 border-blue-500 border-t-transparent animate-spin mb-3"
                ></div>
                <p class="text-sm text-gray-500">
                  {{ t('manager.marketing.bannergenerator.ai_is_generating_the_banner') }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  {{ t('manager.marketing.bannergenerator.pollinations_ai_flux_model_10_20s') }}
                </p>
              </div>
              <img
                v-if="previewUrl && !loading"
                :src="previewUrl"
                :key="previewUrl"
                class="w-full h-auto rounded-xl object-contain max-h-[500px]"
                @error="onImageError"
                :alt="t('manager.marketing.bannergenerator.ai_advertising_banner')"
              />
              <div v-if="!previewUrl && !loading" class="text-center text-gray-400 py-12 px-6">
                <i class="fa-regular fa-image text-5xl mb-4 block text-gray-200"></i>
                <p class="font-medium">{{ t('manager.marketing.bannergenerator.fill_in_the_product_details_and') }}</p>
                <p class="text-sm mt-1">
                  {{ t('manager.marketing.bannergenerator.the_banner_will_appear_here_every') }}
                </p>
              </div>
            </div>

            <div v-if="previewUrl && !loading" class="mt-3 flex items-center justify-between text-xs text-gray-400">
              <span>Rozmiar: {{ sizes[form.size]?.width }}×{{ sizes[form.size]?.height }}px</span>
              <span>Styl: {{ styles[form.style] }}</span>
              <span>{{ t('manager.marketing.bannergenerator.powered_by_pollinations_ai_and_flux') }}</span>
            </div>
          </div>

          <!-- Video preview -->
          <div v-if="videoUrl" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa-solid fa-film text-purple-500"></i> {{ t('manager.marketing.bannergenerator.ad_video_2') }}
            </h2>
            <video :src="videoUrl" controls class="w-full rounded-xl" />
            <a
              :href="videoUrl"
              download
              target="_blank"
              class="mt-3 flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-semibold hover:bg-purple-700 transition"
            >
              <i class="fa-solid fa-download"></i> {{ t('manager.marketing.bannergenerator.download_video') }}
            </a>
          </div>

          <!-- Saved banners gallery -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-semibold text-gray-900">{{ t('manager.marketing.bannergenerator.saved_banners') }}</h2>
              <button @click="loadSavedBanners" class="text-xs text-blue-600 hover:underline">
                <i class="fa-solid fa-rotate-right mr-1"></i>{{ t('common.refresh') }}
              </button>
            </div>
            <div v-if="savedBanners.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div v-for="b in savedBanners" :key="b.url" class="relative group">
                <img
                  :src="b.url"
                  :alt="b.name"
                  class="w-full aspect-video object-cover rounded-lg border border-gray-200"
                />
                <div
                  class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition rounded-lg flex items-center justify-center gap-2"
                >
                  <a
                    :href="b.url"
                    download
                    target="_blank"
                    class="p-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition text-xs"
                  >
                    <i class="fa-solid fa-download"></i>
                  </a>
                  <button
                    @click="deleteBanner(b.name)"
                    class="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-xs"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 truncate">{{ b.name }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-400 text-sm">
              <i class="fa-regular fa-images text-3xl mb-2 block text-gray-200"></i>
              {{ t('manager.marketing.bannergenerator.no_saved_banners') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden canvas for video recorder -->
      <canvas ref="canvasEl" class="hidden"></canvas>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  products: { type: Array, default: () => [] },
  sizes: { type: Object, default: () => ({}) },
  klingKeySet: { type: Boolean, default: false },
})

const styles = {
  modern: t('manager.marketing.bannergenerator.modern'),
  minimal: t('manager.marketing.bannergenerator.minimal'),
  bold: t('manager.marketing.bannergenerator.bold'),
  elegant: t('manager.marketing.bannergenerator.elegant'),
}

const sizeShortLabel = (key) =>
  ({
    facebook: 'Facebook',
    instagram: 'Instagram',
    story: 'Story',
    wide: t('manager.marketing.bannergenerator.website'),
  })[key] || key

const form = reactive({
  product_name: '',
  description: '',
  price: '',
  size: 'facebook',
  style: 'modern',
})

const selectedProduct = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const previewUrl = ref('')
const currentSeed = ref(0)
const savedBanners = ref([])

// Video
const videoLoading = ref(false)
const videoUrl = ref('')
const videoTaskId = ref('')
const recording = ref(false)
const recordedVideoUrl = ref('')
const videoImageFile = ref(null)
const canvasEl = ref(null)
const videoImageInput = ref(null)

const fillFromProduct = () => {
  if (!selectedProduct.value) return
  const p = selectedProduct.value
  form.product_name = p.name
  form.description = p.short_description || ''
  form.price = p.price || ''
}

const generatePreview = async () => {
  if (!form.product_name.trim()) return
  loading.value = true
  error.value = ''
  previewUrl.value = ''
  currentSeed.value = Math.floor(Math.random() * 99999) + 1

  try {
    const { data } = await axios.post(route('tenant.manager.ai-media.banner.preview'), {
      ...form,
      seed: currentSeed.value,
    })
    if (!data.success) {
      error.value = data.message
      return
    }
    previewUrl.value = data.url
  } catch (e) {
    error.value = e.response?.data?.message || t('common.the_banner_could_not_be_generated')
  } finally {
    loading.value = false
  }
}

const regenerate = () => {
  generatePreview()
}

const saveBanner = async () => {
  if (!form.product_name.trim()) return
  saving.value = true
  try {
    const { data } = await axios.post(route('tenant.manager.ai-media.banner.save'), form)
    if (data.success) {
      await loadSavedBanners()
    }
  } catch (e) {
    error.value = e.response?.data?.message || t('common.it_could_not_be_saved')
  } finally {
    saving.value = false
  }
}

const loadSavedBanners = async () => {
  try {
    const { data } = await axios.get(route('tenant.manager.ai-media.banner.list'))
    savedBanners.value = data
  } catch {
    // The list is a convenience; failing to read it leaves the generator
    // itself perfectly usable.
  }
}

const deleteBanner = async (filename) => {
  if (!confirm(t('common.delete_this_banner'))) return
  try {
    await axios.delete(route('tenant.manager.ai-media.banner.delete'), { data: { filename } })
    await loadSavedBanners()
  } catch {
    // The banner may already be gone. Either way the list is refreshed
    // the next time the page is opened.
  }
}

const onImageError = () => {
  error.value = t('common.the_image_could_not_be_loaded')
  previewUrl.value = ''
}

// ── Kling Video ─────────────────────────────────────────────────────────────

const generateVideo = async () => {
  videoLoading.value = true
  try {
    const { data } = await axios.post(route('tenant.manager.ai-media.video.generate'), {
      product_name: form.product_name,
      description: form.description,
      price: form.price,
    })
    if (data.success && data.task_id) {
      videoTaskId.value = data.task_id
      pollVideoStatus()
    }
  } catch (e) {
    error.value = e.response?.data?.message || t('common.the_video_could_not_be_generated')
    videoLoading.value = false
  }
}

let pollTimer = null
const pollVideoStatus = () => {
  pollTimer = setInterval(async () => {
    try {
      const { data } = await axios.get(route('tenant.manager.ai-media.video.status'), {
        params: { task_id: videoTaskId.value },
      })
      if (data.done && data.url) {
        clearInterval(pollTimer)
        videoUrl.value = data.url
        videoLoading.value = false
      }
    } catch {
      // Polling for the finished video; the next attempt is in eight
      // seconds and the spinner stays where it is.
    }
  }, 8000)
}

// ── Canvas Video Recorder ────────────────────────────────────────────────────

const onVideoImageSelected = (e) => {
  const file = e.target.files?.[0]
  if (file) videoImageFile.value = file
}

const recordAnimation = async () => {
  if (!videoImageFile.value || !canvasEl.value) return
  recording.value = true
  recordedVideoUrl.value = ''

  const canvas = canvasEl.value
  canvas.width = 1280
  canvas.height = 720
  const ctx = canvas.getContext('2d')

  const img = new Image()
  const imgUrl = URL.createObjectURL(videoImageFile.value)
  img.src = imgUrl
  await new Promise((r) => {
    img.onload = r
  })

  const stream = canvas.captureStream(30)
  const recorder = new MediaRecorder(stream, { mimeType: 'video/webm;codecs=vp9' })
  const chunks = []
  recorder.ondataavailable = (e) => {
    if (e.data.size > 0) chunks.push(e.data)
  }
  recorder.onstop = () => {
    const blob = new Blob(chunks, { type: 'video/webm' })
    recordedVideoUrl.value = URL.createObjectURL(blob)
    recording.value = false
    URL.revokeObjectURL(imgUrl)
  }

  recorder.start()
  const duration = 8000 // 8 seconds
  const start = performance.now()

  const productName = form.product_name || t('common.product')
  const price = form.price ? `${parseFloat(form.price).toFixed(2)} PLN` : ''

  const animate = (now) => {
    const t = (now - start) / duration // 0 → 1
    const elapsed = now - start

    ctx.clearRect(0, 0, canvas.width, canvas.height)

    // Background
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Ken Burns effect: slow zoom + pan
    const scale = 1 + t * 0.12
    const offsetX = (canvas.width - img.width * scale) / 2 + t * 20
    const offsetY = (canvas.height - img.height * scale) / 2

    ctx.save()
    ctx.translate(canvas.width / 2, canvas.height / 2)
    ctx.scale(scale, scale)
    ctx.translate(-canvas.width / 2, -canvas.height / 2)

    // Fit image
    const ratio = Math.min(canvas.width / img.width, canvas.height / img.height)
    const w = img.width * ratio
    const h = img.height * ratio
    ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h)
    ctx.restore()

    // Vignette overlay
    const vg = ctx.createRadialGradient(
      canvas.width / 2,
      canvas.height / 2,
      canvas.width * 0.3,
      canvas.width / 2,
      canvas.height / 2,
      canvas.width * 0.8,
    )
    vg.addColorStop(0, 'rgba(0,0,0,0)')
    vg.addColorStop(1, 'rgba(0,0,0,0.45)')
    ctx.fillStyle = vg
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Product name (fade in from 0.5s)
    if (elapsed > 500) {
      const nameAlpha = Math.min(1, (elapsed - 500) / 400)
      const nameY = canvas.height * 0.65 + (1 - nameAlpha) * 20
      ctx.save()
      ctx.globalAlpha = nameAlpha
      ctx.fillStyle = 'rgba(0,0,0,0.55)'
      ctx.fillRect(0, nameY - 48, canvas.width, 60)
      ctx.fillStyle = '#ffffff'
      ctx.font = 'bold 36px Inter, Arial, sans-serif'
      ctx.textAlign = 'center'
      ctx.shadowColor = 'rgba(0,0,0,0.6)'
      ctx.shadowBlur = 12
      ctx.fillText(productName, canvas.width / 2, nameY)
      ctx.restore()
    }

    // Price (fade in from 1.5s)
    if (price && elapsed > 1500) {
      const priceAlpha = Math.min(1, (elapsed - 1500) / 400)
      ctx.save()
      ctx.globalAlpha = priceAlpha
      ctx.font = 'bold 48px Inter, Arial, sans-serif'
      ctx.textAlign = 'center'
      ctx.fillStyle = '#FFD700'
      ctx.shadowColor = 'rgba(0,0,0,0.8)'
      ctx.shadowBlur = 16
      ctx.fillText(price, canvas.width / 2, canvas.height * 0.78)
      ctx.restore()
    }

    // CTA bottom strip (fade in from 5s)
    if (elapsed > 5000) {
      const ctaAlpha = Math.min(1, (elapsed - 5000) / 600)
      ctx.save()
      ctx.globalAlpha = ctaAlpha
      ctx.fillStyle = '#3b82f6'
      ctx.fillRect(0, canvas.height - 70, canvas.width, 70)
      ctx.fillStyle = '#ffffff'
      ctx.font = 'bold 28px Inter, Arial, sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(t('manager.marketing.bannergenerator.see_it_in_the_shop'), canvas.width / 2, canvas.height - 28)
      ctx.restore()
    }

    if (elapsed < duration) {
      requestAnimationFrame(animate)
    } else {
      recorder.stop()
    }
  }

  requestAnimationFrame(animate)
}

onMounted(() => {
  loadSavedBanners()
})
</script>
