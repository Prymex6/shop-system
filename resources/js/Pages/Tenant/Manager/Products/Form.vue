<template>
  <ManagerLayout :title="product ? t('common.edit_product') : t('common.new_product')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.products.index')" class="text-gray-400 hover:text-gray-600 text-sm">{{
            t('manager.products.form.products')
          }}</Link>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ product ? t('common.editing') + product.name : t('common.new_product') }}
          </h1>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="form.is_published = !form.is_published"
            class="text-sm px-3 py-2 rounded-md border transition font-medium"
            :class="form.is_published ? 'border-green-300 bg-green-50 text-green-700' : 'border-gray-200 text-gray-600'"
          >
            {{ form.is_published ? '✓ Opublikowany' : t('manager.articles.form.draft') }}
          </button>
          <button
            @click="submit"
            class="bg-blue-600 text-white px-5 py-2 rounded-xl font-semibold hover:bg-blue-700 transition text-sm"
          >
            {{ t('manager.products.form.save_the_product') }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main column (2/3) -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Tabs -->
          <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="flex-1 px-3 py-2 text-sm font-medium rounded-lg transition"
              :class="activeTab === tab.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- Tab: Basic Info -->
          <div v-show="activeTab === 'basic'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.form.product_name')
              }}</label>
              <input
                v-model="form.name"
                @input="autoSlug"
                type="text"
                required
                class="w-full border border-gray-200 rounded-md px-3 py-2"
                :placeholder="t('manager.products.form.e_g_premium_printed_t_shirt')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.articles.form.slug_url')
              }}</label>
              <input
                v-model="form.slug"
                type="text"
                class="w-full border border-gray-200 rounded-md px-3 py-2 font-mono text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.form.short_description')
              }}</label>
              <textarea
                v-model="form.short_description"
                rows="2"
                maxlength="500"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm resize-none"
                :placeholder="t('manager.products.form.shown_on_product_cards')"
              />
            </div>
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">{{ t('common.product_description') }}</label>
                <button
                  type="button"
                  @click="generateAiDescription"
                  :disabled="aiLoading || !form.name"
                  class="inline-flex items-center gap-1.5 px-3 py-1 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold transition disabled:opacity-40"
                  :title="t('manager.products.form.generate_a_description_with_ai_needs')"
                >
                  <i v-if="aiLoading" class="fa-solid fa-spinner animate-spin"></i>
                  <i v-else class="fa-solid fa-wand-magic-sparkles"></i>
                  {{ aiLoading ? 'Generowanie...' : t('common.generate_with_ai') }}
                </button>
              </div>
              <p v-if="aiError" class="mb-1 text-xs text-red-600 flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i>{{ aiError }}
              </p>
              <textarea
                v-model="form.description"
                rows="8"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm resize-none"
                :placeholder="t('manager.products.form.the_full_product_description')"
              />
            </div>
          </div>

          <!-- Tab: Features (Cechy) -->
          <div v-show="activeTab === 'features'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-semibold text-gray-900">{{ t('manager.products.form.specifications') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                  {{ t('manager.products.form.properties_shown_in_the_table_on') }}
                </p>
              </div>
              <button
                type="button"
                @click="addFeature"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium border border-blue-200 rounded px-3 py-1.5 hover:bg-blue-50 transition"
              >
                <i class="fa-solid fa-plus mr-1 text-xs"></i> {{ t('manager.products.form.add_specification') }}
              </button>
            </div>

            <div
              v-if="form.features.length === 0"
              class="text-center py-10 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-xl"
            >
              {{ t('manager.products.form.no_specifications_yet_click_add_specification') }}
            </div>

            <div class="space-y-2">
              <div v-for="(feature, i) in form.features" :key="i" class="flex items-center gap-2 group">
                <div class="flex-1 grid grid-cols-2 gap-2">
                  <input
                    v-model="feature.name"
                    type="text"
                    :placeholder="t('manager.products.form.specification_name_e_g_material')"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <input
                    v-model="feature.value"
                    type="text"
                    :placeholder="t('manager.products.form.value_e_g_100_cotton')"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <button
                  type="button"
                  @click="removeFeature(i)"
                  class="text-gray-300 hover:text-red-500 transition p-1 opacity-0 group-hover:opacity-100"
                  :title="t('common.delete')"
                >
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>

            <!-- Quick-fill presets -->
            <div class="pt-2 border-t border-gray-100">
              <p class="text-xs text-gray-500 mb-2">{{ t('manager.products.form.quick_add') }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="preset in featurePresets"
                  :key="preset"
                  type="button"
                  @click="addFeatureNamed(preset)"
                  class="text-xs px-2.5 py-1 bg-gray-100 hover:bg-blue-100 hover:text-blue-700 text-gray-600 rounded-full transition"
                >
                  + {{ preset }}
                </button>
              </div>
            </div>
          </div>

          <!-- Tab: Variants -->
          <div v-show="activeTab === 'variants'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <p v-if="!product" class="text-sm text-gray-500 text-center py-4">
              {{ t('manager.products.form.save_the_product_to_manage_its') }}
            </p>
            <div v-else>
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ t('common.product_variants') }}</h3>
                <Link
                  :href="route('tenant.manager.products.variants', product.id)"
                  class="text-sm text-blue-600 hover:text-blue-900 font-medium"
                >
                  {{ t('manager.products.form.manage_variants') }}
                </Link>
              </div>
              <div v-if="product.variants?.length" class="space-y-2">
                <div
                  v-for="v in product.variants"
                  :key="v.id"
                  class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2 text-sm"
                >
                  <span class="font-medium text-gray-900">{{ v.label ?? t('common.no_attributes') }}</span>
                  <span class="text-gray-600">{{ v.price ? formatPrice(v.price) : t('common.base_price') }}</span>
                  <span class="font-medium" :class="v.stock_quantity > 0 ? 'text-green-700' : 'text-red-600'">
                    {{ v.stock_quantity }} szt.
                  </span>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500 text-center py-4">{{ t('manager.products.form.no_variants') }}</p>
            </div>
          </div>

          <!-- Tab: Images -->
          <div v-show="activeTab === 'images'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <h3 class="font-semibold text-gray-900">{{ t('manager.products.form.photo_gallery') }}</h3>
            <div v-if="product?.images?.length" class="grid grid-cols-4 gap-3">
              <div v-for="img in product.images" :key="img.id" class="relative group">
                <img
                  :src="img.path?.startsWith('/') || img.path?.startsWith('http') ? img.path : '/storage/' + img.path"
                  :alt="img.alt"
                  class="w-full aspect-square object-cover rounded-xl"
                />
                <button
                  @click="deleteImage(img)"
                  class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hidden group-hover:flex items-center justify-center"
                >
                  ×
                </button>
              </div>
            </div>
            <div>
              <label
                class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-md p-6 cursor-pointer hover:border-blue-300 transition"
              >
                <span class="text-3xl mb-2">📷</span>
                <span class="text-sm font-medium text-gray-700">{{
                  t('manager.products.form.click_to_add_photos')
                }}</span>
                <span class="text-xs text-gray-400 mt-1">{{ t('manager.products.form.jpg_png_webp_5_mb_max') }}</span>
                <input type="file" accept="image/*" multiple class="hidden" @change="uploadImages" />
              </label>
            </div>
          </div>

          <!-- Tab: Digital Files -->
          <div v-show="activeTab === 'files'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-gray-900">{{ t('manager.products.form.digital_files') }}</h3>
              <span class="text-xs text-gray-400">{{ t('manager.products.form.digital_products_only') }}</span>
            </div>
            <div v-if="product?.digital_files?.length" class="space-y-2">
              <div
                v-for="file in product.digital_files"
                :key="file.id"
                class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2"
              >
                <div class="flex items-center gap-2">
                  <span class="text-xl">📄</span>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                    <p class="text-xs text-gray-400">{{ file.formatted_size }}</p>
                  </div>
                </div>
                <button @click="deleteFile(file)" class="text-red-500 hover:text-red-700 text-sm font-medium">
                  {{ t('common.delete') }}
                </button>
              </div>
            </div>
            <label
              class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-md p-6 cursor-pointer hover:border-purple-300 transition"
            >
              <span class="text-3xl mb-2">📁</span>
              <span class="text-sm font-medium text-gray-700">{{
                t('manager.products.form.click_to_upload_a_file')
              }}</span>
              <span class="text-xs text-gray-400 mt-1">{{ t('manager.products.form.any_format_100_mb_max') }}</span>
              <input type="file" class="hidden" @change="uploadFile" />
            </label>
          </div>

          <!-- Tab: SEO -->
          <div v-show="activeTab === 'seo'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <h3 class="font-semibold text-gray-900">{{ t('manager.products.form.seo_settings') }}</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.meta_title') }}</label>
              <input
                v-model="form.meta_title"
                type="text"
                maxlength="200"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.meta_description') }}</label>
              <textarea
                v-model="form.meta_description"
                rows="3"
                maxlength="500"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm resize-none"
              />
            </div>
          </div>
        </div>

        <!-- Right column (1/3) -->
        <div class="space-y-5">
          <!-- Type & Category -->
          <div class="bg-white shadow rounded-lg p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.form.product_type')
              }}</label>
              <div class="flex gap-2">
                <label class="flex-1 cursor-pointer">
                  <input type="radio" v-model="form.type" value="physical" class="sr-only" />
                  <div
                    class="border-2 rounded-xl p-3 text-center text-sm font-medium transition"
                    :class="
                      form.type === 'physical'
                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                        : 'border-gray-200 text-gray-600 hover:border-blue-300'
                    "
                  >
                    {{ t('manager.products.form.physical') }}
                  </div>
                </label>
                <label class="flex-1 cursor-pointer">
                  <input type="radio" v-model="form.type" value="digital" class="sr-only" />
                  <div
                    class="border-2 rounded-xl p-3 text-center text-sm font-medium transition"
                    :class="
                      form.type === 'digital'
                        ? 'border-purple-500 bg-purple-50 text-purple-700'
                        : 'border-gray-200 text-gray-600 hover:border-purple-300'
                    "
                  >
                    {{ t('manager.products.form.digital') }}
                  </div>
                </label>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.category') }}</label>
              <select v-model="form.category_id" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                <option :value="null">{{ t('manager.products.form.no_category') }}</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
              <input
                v-model="form.sku"
                type="text"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm font-mono"
                placeholder="AUTO-001"
              />
            </div>
          </div>

          <!-- Pricing -->
          <div class="bg-white shadow rounded-lg p-5 space-y-4">
            <h3 class="text-sm font-bold text-gray-900">{{ t('common.price') }}</h3>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.selling_price_pln')
              }}</label>
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full border border-gray-200 rounded-md px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.compare_at_price_before_discount')
              }}</label>
              <input
                v-model="form.compare_price"
                type="number"
                step="0.01"
                min="0"
                class="w-full border border-gray-200 rounded-md px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.vat_rate')
              }}</label>
              <select v-model="form.tax_rate_id" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                <option :value="null">{{ t('common.none') }}</option>
                <option v-for="rate in taxRates" :key="rate.id" :value="rate.id">
                  {{ rate.name }} ({{ rate.rate }}%)
                </option>
              </select>
            </div>
          </div>

          <!-- Stock (physical only) -->
          <div v-if="form.type === 'physical'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <h3 class="text-sm font-bold text-gray-900">{{ t('common.warehouse') }}</h3>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.track_stock" class="accent-indigo-600" />
              {{ t('manager.products.form.track_stock') }}
            </label>
            <div v-if="form.track_stock">
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.stock_level')
              }}</label>
              <input
                v-model="form.stock_quantity"
                type="number"
                min="0"
                class="w-full border border-gray-200 rounded-md px-3 py-2"
              />
            </div>
            <div v-if="form.track_stock">
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.alert_threshold_units')
              }}</label>
              <input
                v-model="form.low_stock_threshold"
                type="number"
                min="0"
                class="w-full border border-gray-200 rounded-md px-3 py-2"
              />
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.allow_backorder" class="accent-indigo-600" />
              {{ t('manager.products.form.allow_orders_when_out_of_stock') }}
            </label>
          </div>

          <!-- Digital options -->
          <div v-if="form.type === 'digital'" class="bg-white shadow rounded-lg p-5 space-y-4">
            <h3 class="text-sm font-bold text-gray-900">{{ t('manager.products.form.delivery_options') }}</h3>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.download_limit_empty_unlimited')
              }}</label>
              <input
                v-model="form.download_limit"
                type="number"
                min="1"
                class="w-full border border-gray-200 rounded-md px-3 py-2"
                placeholder="∞"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{
                t('manager.products.form.link_expires_after_hours')
              }}</label>
              <input
                v-model="form.download_expires_hours"
                type="number"
                min="1"
                class="w-full border border-gray-200 rounded-md px-3 py-2"
                :placeholder="t('manager.products.form.never')"
              />
            </div>
          </div>

          <!-- Tags -->
          <div class="bg-white shadow rounded-lg p-5 space-y-3">
            <h3 class="text-sm font-bold text-gray-900">{{ t('manager.products.form.product_labels') }}</h3>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="tag in availableTags"
                :key="tag.value"
                class="flex items-center p-2.5 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
              >
                <input
                  v-model="form.tags"
                  type="checkbox"
                  :value="tag.value"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-900">{{ tag.label }}</span>
              </label>
            </div>
          </div>

          <!-- Options -->
          <div class="bg-white shadow rounded-lg p-5 space-y-3">
            <h3 class="text-sm font-bold text-gray-900">{{ t('manager.products.form.options') }}</h3>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_featured" class="accent-indigo-600" />
              {{ t('manager.products.form.featured_product') }}
            </label>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const availableTags = [
  { value: 'new', label: t('common.new_2') },
  { value: 'bestseller', label: t('manager.products.form.bestseller') },
  { value: 'sale', label: t('common.sale_2') },
  { value: 'hot', label: t('manager.products.form.popular') },
  { value: 'limited', label: t('manager.products.form.limited') },
  { value: 'free_ship', label: t('common.free_delivery_2') },
  { value: 'handmade', label: t('manager.products.form.handmade') },
  { value: 'premium', label: t('manager.products.form.premium') },
]

const props = defineProps({
  product: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
  taxRates: { type: Array, default: () => [] },
  attributes: { type: Array, default: () => [] },
})

const activeTab = ref('basic')
const tabs = [
  { id: 'basic', label: t('manager.products.form.basics') },
  { id: 'features', label: t('manager.products.form.specifications_2') },
  { id: 'variants', label: t('common.variants') },
  { id: 'images', label: t('common.photos') },
  { id: 'files', label: t('landlord.modifications.index.files') },
  { id: 'seo', label: 'SEO' },
]

const form = reactive({
  name: props.product?.name ?? '',
  slug: props.product?.slug ?? '',
  category_id: props.product?.category_id ?? null,
  type: props.product?.type ?? 'physical',
  short_description: props.product?.short_description ?? '',
  description: props.product?.description ?? '',
  sku: props.product?.sku ?? '',
  price: props.product?.price ?? '',
  compare_price: props.product?.compare_price ?? '',
  cost_price: props.product?.cost_price ?? '',
  tax_rate_id: props.product?.tax_rate_id ?? null,
  track_stock: props.product?.track_stock ?? true,
  stock_quantity: props.product?.stock_quantity ?? 0,
  low_stock_threshold: props.product?.low_stock_threshold ?? 5,
  allow_backorder: props.product?.allow_backorder ?? false,
  weight: props.product?.weight ?? '',
  download_limit: props.product?.download_limit ?? '',
  download_expires_hours: props.product?.download_expires_hours ?? '',
  meta_title: props.product?.meta_title ?? '',
  meta_description: props.product?.meta_description ?? '',
  is_published: props.product?.is_published ?? false,
  is_featured: props.product?.is_featured ?? false,
  sort_order: props.product?.sort_order ?? 0,
  tags: props.product?.tags ?? [],
  features: props.product?.features?.map((f) => ({ name: f.name, value: f.value })) ?? [],
})

// AI description
const aiLoading = ref(false)
const aiError = ref('')

async function generateAiDescription() {
  if (!form.name) return
  aiLoading.value = true
  aiError.value = ''
  try {
    const { data } = await axios.post(route('tenant.manager.products.generate-description'), {
      title: form.name,
      price: form.price,
      description: form.description,
    })
    if (!data.success) {
      aiError.value = data.message
      return
    }
    if (data.data.description) form.description = data.data.description
    if (data.data.short_description) form.short_description = data.data.short_description
    if (data.data.meta_description) form.meta_description = data.data.meta_description
  } catch (e) {
    aiError.value = e.response?.data?.message || t('common.the_ai_description_could_not_be')
  } finally {
    aiLoading.value = false
  }
}

const featurePresets = [
  t('manager.products.form.spec_material'),
  t('manager.products.form.spec_maker'),
  t('manager.products.form.spec_origin'),
  t('manager.products.form.spec_dimensions'),
  t('manager.products.form.spec_weight'),
  t('manager.products.form.spec_colour'),
  t('manager.products.form.spec_size'),
  t('manager.products.form.spec_power'),
  t('manager.products.form.spec_capacity'),
  t('manager.products.form.spec_model'),
  t('manager.products.form.spec_warranty'),
  t('manager.products.form.spec_certs'),
]

function addFeatureNamed(name) {
  addFeature()
  form.features[form.features.length - 1].name = name
}

function addFeature() {
  form.features.push({ name: '', value: '' })
}

function removeFeature(i) {
  form.features.splice(i, 1)
}

function autoSlug() {
  if (!props.product) {
    form.slug = form.name
      .toLowerCase()
      .replace(/[^a-z0-9ąćęłńóśźż\s-]/gi, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

function submit() {
  if (props.product) {
    router.put(route('tenant.manager.products.update', props.product.id), form, { preserveScroll: true })
  } else {
    router.post(route('tenant.manager.products.store'), form)
  }
}

function uploadImages(event) {
  if (!props.product) return
  Array.from(event.target.files).forEach((file) => {
    const fd = new FormData()
    fd.append('image', file)
    router.post(route('tenant.manager.products.images.upload', props.product.id), fd, { preserveScroll: true })
  })
}

function deleteImage(image) {
  if (!confirm(t('common.delete_this_photo'))) return
  router.delete(route('tenant.manager.products.images.destroy', [props.product.id, image.id]), { preserveScroll: true })
}

function uploadFile(event) {
  if (!props.product) return
  const fd = new FormData()
  fd.append('file', event.target.files[0])
  router.post(route('tenant.manager.products.files.upload', props.product.id), fd, { preserveScroll: true })
}

function deleteFile(file) {
  if (!confirm(t('manager.products.form.delete_the_file_a', { a: file.name }))) return
  router.delete(route('tenant.manager.products.files.destroy', [props.product.id, file.id]), { preserveScroll: true })
}
</script>
