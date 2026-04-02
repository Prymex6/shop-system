<template>
  <div class="space-y-4">
    <div v-for="(group, attribute) in groupedAttributes" :key="attribute">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-sm font-medium text-gray-700">{{ attribute }}:</span>
        <span class="text-sm text-gray-500">{{ selectedLabels[attribute] ?? '—' }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="value in group"
          :key="value"
          type="button"
          @click="selectAttribute(attribute, value)"
          :disabled="isValueUnavailable(attribute, value)"
          :title="isValueUnavailable(attribute, value) ? t('common.unavailable') : value"
          :class="[
            'relative transition-all focus:outline-none',
            isColor(value) ? 'w-8 h-8 rounded-full border-2' : 'px-3 py-1.5 rounded-lg border text-sm font-medium',
            isSelectedAttribute(attribute, value)
              ? 'theme-primary-border theme-primary-ring'
              : 'border-gray-300 hover:theme-primary-border',
            isValueUnavailable(attribute, value) ? 'opacity-40 cursor-not-allowed line-through' : 'cursor-pointer',
          ]"
          :style="isColor(value) ? { backgroundColor: value } : {}"
        >
          <span v-if="!isColor(value)">{{ value }}</span>
          <!-- Unavailable slash for color swatches -->
          <span
            v-if="isColor(value) && isValueUnavailable(attribute, value)"
            class="absolute inset-0 flex items-center justify-center text-white font-bold text-xs pointer-events-none"
            >/</span
          >
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  variants: { type: Array, required: true },
  modelValue: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue'])

// Collect all distinct attribute keys and their possible values
const groupedAttributes = computed(() => {
  const groups = {}
  for (const variant of props.variants) {
    const attrs = variant.attributes ?? {}
    for (const [key, val] of Object.entries(attrs)) {
      if (!groups[key]) groups[key] = []
      if (!groups[key].includes(val)) groups[key].push(val)
    }
  }
  return groups
})

// Track currently selected attribute values (may be partial selection)
const selected = reactive({})

// When modelValue changes externally, sync selected attributes
const syncFromModel = () => {
  if (!props.modelValue?.attributes) return
  for (const [k, v] of Object.entries(props.modelValue.attributes)) {
    selected[k] = v
  }
}
syncFromModel()

const selectedLabels = computed(() => ({ ...selected }))

const isColor = (value) => {
  // Detect hex colors or CSS named colors
  return (
    /^#([0-9a-fA-F]{3}){1,2}$/.test(value) ||
    [
      'red',
      'green',
      'blue',
      'white',
      'black',
      'yellow',
      'orange',
      'pink',
      'purple',
      'gray',
      'grey',
      'brown',
      'beige',
      'navy',
      'coral',
      'cyan',
      'magenta',
      'lime',
      'maroon',
      'olive',
      'teal',
      'silver',
      'gold',
    ].includes(value.toLowerCase())
  )
}

const isSelectedAttribute = (attribute, value) => selected[attribute] === value

const isVariantAvailable = (variant) => {
  return variant.is_active !== false && (variant.stock == null || variant.stock > 0)
}

const isValueUnavailable = (attribute, value) => {
  // Check if ANY variant with this attribute value (and current other selections) is available
  const testSelected = { ...selected, [attribute]: value }
  const matching = props.variants.filter((v) => {
    const attrs = v.attributes ?? {}
    return Object.entries(testSelected).every(([k, val]) => attrs[k] === val || !(k in attrs))
  })
  return matching.length === 0 || matching.every((v) => !isVariantAvailable(v))
}

const selectAttribute = (attribute, value) => {
  if (isValueUnavailable(attribute, value)) return
  selected[attribute] = value

  // Try to find a variant that matches all selected attributes
  const match = props.variants.find((v) => {
    const attrs = v.attributes ?? {}
    return Object.entries(selected).every(([k, val]) => attrs[k] === val)
  })

  if (match && isVariantAvailable(match)) {
    emit('update:modelValue', match)
  } else {
    // Partial match — still emit null so parent knows selection is incomplete
    emit('update:modelValue', null)
  }
}
</script>
