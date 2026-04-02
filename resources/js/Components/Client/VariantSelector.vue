<template>
  <div class="space-y-4">
    <div v-for="attribute in attributes" :key="attribute.id">
      <label class="block text-sm font-semibold text-gray-700 mb-2">
        {{ attribute.name }}:
        <span class="font-normal text-gray-500">{{ selectedLabels[attribute.id] ?? t('common.choose') }}</span>
      </label>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="value in attribute.values"
          :key="value.id"
          @click="selectValue(attribute.id, value)"
          :aria-pressed="selectedValues[attribute.id] === value.id"
          class="px-3 py-1.5 text-sm border-2 rounded-lg transition"
          :class="
            selectedValues[attribute.id] === value.id
              ? 'theme-primary-border theme-primary-bg-light theme-primary font-semibold'
              : 'border-gray-200 hover:theme-primary-border'
          "
        >
          <!-- Color swatch -->
          <span v-if="value.color_hex" class="inline-flex items-center gap-1.5">
            <span
              class="w-4 h-4 rounded-full border border-gray-200 inline-block"
              :style="{ backgroundColor: value.color_hex }"
            />
            {{ value.value }}
          </span>
          <span v-else>{{ value.value }}</span>
        </button>
      </div>
    </div>

    <p v-if="selectedVariant && !selectedVariant.is_active" class="text-sm text-red-600 font-medium">
      {{ t('client.variantselector.that_combination_is_out_of_stock') }}
    </p>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  variants: { type: Array, default: () => [] },
  attributes: { type: Array, default: () => [] },
})

const emit = defineEmits(['change'])

const selectedValues = reactive({})
const selectedLabels = reactive({})

const selectedVariant = computed(() => {
  if (Object.keys(selectedValues).length !== props.attributes.length) return null
  return (
    props.variants.find((v) => {
      return props.attributes.every((attr) => v.attributes?.[attr.id] == selectedValues[attr.id])
    }) ?? null
  )
})

function selectValue(attributeId, value) {
  selectedValues[attributeId] = value.id
  selectedLabels[attributeId] = value.value
}

watch(selectedVariant, (variant) => {
  emit('change', variant)
})
</script>
