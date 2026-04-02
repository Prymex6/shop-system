<template>
  <aside class="space-y-6">
    <!-- Price range -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ t('common.price') }}</h3>
      <div class="flex items-center gap-2">
        <input
          v-model.number="localMin"
          type="number"
          min="0"
          :placeholder="t('common.from')"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:theme-primary-ring"
          @change="emitChange"
        />
        <span class="text-gray-400 flex-shrink-0">–</span>
        <input
          v-model.number="localMax"
          type="number"
          min="0"
          :placeholder="t('common.to_2')"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:theme-primary-ring"
          @change="emitChange"
        />
      </div>
    </div>

    <!-- In stock -->
    <div>
      <label class="flex items-center gap-2.5 cursor-pointer group">
        <input
          type="checkbox"
          :checked="modelValue.inStock"
          @change="toggle('inStock')"
          class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ t('common.in_stock_only') }}</span>
      </label>
    </div>

    <!-- Product type -->
    <div v-if="showType">
      <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ t('client.filtersidebar.product_type') }}</h3>
      <div class="space-y-2">
        <label v-for="opt in typeOptions" :key="opt.value" class="flex items-center gap-2.5 cursor-pointer group">
          <input
            type="radio"
            :value="opt.value"
            :checked="modelValue.type === opt.value"
            @change="setType(opt.value)"
            class="w-4 h-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ opt.label }}</span>
        </label>
      </div>
    </div>

    <!-- Attribute filters -->
    <div v-for="attr in attributes" :key="attr.name">
      <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ attr.name }}</h3>
      <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
        <label v-for="val in attr.values" :key="val" class="flex items-center gap-2.5 cursor-pointer group">
          <input
            type="checkbox"
            :checked="isAttrChecked(attr.name, val)"
            @change="toggleAttr(attr.name, val)"
            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ val }}</span>
        </label>
      </div>
    </div>

    <!-- Sort -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ t('client.filtersidebar.sorting') }}</h3>
      <select
        :value="modelValue.sort"
        @change="(e) => emit('update:modelValue', { ...modelValue, sort: e.target.value })"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:theme-primary-ring"
      >
        <option value="newest">{{ t('client.filtersidebar.newest') }}</option>
        <option value="price_asc">{{ t('common.price_low_to_high') }}</option>
        <option value="price_desc">{{ t('common.price_high_to_low') }}</option>
        <option value="popular">{{ t('client.filtersidebar.popularity') }}</option>
        <option value="rating">{{ t('client.filtersidebar.rating') }}</option>
        <option value="name_asc">{{ t('client.filtersidebar.name_a_z') }}</option>
      </select>
    </div>

    <!-- Reset -->
    <button
      v-if="hasActiveFilters"
      @click="reset"
      class="w-full py-2 text-sm theme-primary border border-gray-200 rounded-lg hover:theme-primary-bg-light font-medium"
    >
      {{ t('client.filtersidebar.clear_filters') }}
    </button>
  </aside>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ minPrice: null, maxPrice: null, inStock: false, type: null, sort: 'newest', attributes: {} }),
  },
  attributes: { type: Array, default: () => [] }, // [{ name: t('manager.products.form.spec_colour'), values: ['Czerwony', 'Niebieski'] }]
  showType: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'change'])

const localMin = ref(props.modelValue.minPrice ?? '')
const localMax = ref(props.modelValue.maxPrice ?? '')

const typeOptions = [
  { value: null, label: t('common.all') },
  { value: 'physical', label: t('client.filtersidebar.physical') },
  { value: 'digital', label: t('client.filtersidebar.digital') },
]

const hasActiveFilters = computed(
  () =>
    localMin.value ||
    localMax.value ||
    props.modelValue.inStock ||
    props.modelValue.type ||
    Object.keys(props.modelValue.attributes ?? {}).length > 0,
)

function emitChange() {
  emit('update:modelValue', {
    ...props.modelValue,
    minPrice: localMin.value || null,
    maxPrice: localMax.value || null,
  })
  emit('change')
}

function toggle(key) {
  emit('update:modelValue', { ...props.modelValue, [key]: !props.modelValue[key] })
  emit('change')
}

function setType(val) {
  emit('update:modelValue', { ...props.modelValue, type: val })
  emit('change')
}

function isAttrChecked(attrName, value) {
  return (props.modelValue.attributes?.[attrName] ?? []).includes(value)
}

function toggleAttr(attrName, value) {
  const current = { ...(props.modelValue.attributes ?? {}) }
  if (!current[attrName]) current[attrName] = []
  const idx = current[attrName].indexOf(value)
  if (idx === -1) {
    current[attrName] = [...current[attrName], value]
  } else {
    current[attrName] = current[attrName].filter((v) => v !== value)
    if (current[attrName].length === 0) delete current[attrName]
  }
  emit('update:modelValue', { ...props.modelValue, attributes: current })
  emit('change')
}

function reset() {
  localMin.value = ''
  localMax.value = ''
  emit('update:modelValue', {
    minPrice: null,
    maxPrice: null,
    inStock: false,
    type: null,
    sort: 'newest',
    attributes: {},
  })
  emit('change')
}

watch(
  () => props.modelValue.minPrice,
  (v) => {
    localMin.value = v ?? ''
  },
)
watch(
  () => props.modelValue.maxPrice,
  (v) => {
    localMax.value = v ?? ''
  },
)
</script>
