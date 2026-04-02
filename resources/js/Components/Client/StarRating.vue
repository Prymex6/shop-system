<template>
  <div class="flex items-center gap-0.5">
    <span v-for="i in 5" :key="i" class="text-yellow-400" :class="compact ? 'text-xs' : 'text-base'">
      {{ i <= Math.round(rating) ? '★' : '☆' }}
    </span>
    <span v-if="!compact && showCount && count > 0" class="text-sm text-gray-500 ml-1">({{ count }})</span>
  </div>
</template>

<script setup>
defineProps({
  rating: { type: Number, default: 0 },
  count: { type: Number, default: 0 },
  compact: { type: Boolean, default: false },
  showCount: { type: Boolean, default: false },
})
</script>
