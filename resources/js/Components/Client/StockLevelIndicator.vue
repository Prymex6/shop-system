<template>
  <span v-if="trackStock" :class="indicatorClass" class="text-xs font-semibold">
    {{ indicatorText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  stockQuantity: { type: Number, default: 0 },
  trackStock: { type: Boolean, default: true },
})

const indicatorClass = computed(() => {
  if (props.stockQuantity <= 0) return 'text-red-600'
  if (props.stockQuantity <= 4) return 'text-orange-500'
  if (props.stockQuantity <= 10) return 'text-yellow-600'
  return ''
})

const indicatorText = computed(() => {
  if (props.stockQuantity <= 0) return t('common.out_of_stock')
  if (props.stockQuantity <= 4) return `Tylko ${props.stockQuantity} sztuk!`
  if (props.stockQuantity <= 10) return t('common.only_a_few_left')
  return ''
})

// No indicator for stockQuantity > 10 — return empty string (no rendering)
</script>
