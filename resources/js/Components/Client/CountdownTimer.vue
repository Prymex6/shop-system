<template>
  <div v-if="!expired" class="font-mono text-lg font-bold tracking-widest">
    <span>{{ pad(days) }}</span
    >:<span>{{ pad(hours) }}</span
    >:<span>{{ pad(minutes) }}</span
    >:<span>{{ pad(seconds) }}</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  endsAt: { type: String, required: true },
})

const now = ref(Date.now())
const expired = ref(false)
let interval = null

const remaining = computed(() => {
  const diff = new Date(props.endsAt).getTime() - now.value
  return Math.max(0, diff)
})

const days = computed(() => Math.floor(remaining.value / 86400000))
const hours = computed(() => Math.floor((remaining.value % 86400000) / 3600000))
const minutes = computed(() => Math.floor((remaining.value % 3600000) / 60000))
const seconds = computed(() => Math.floor((remaining.value % 60000) / 1000))

function pad(n) {
  return String(n).padStart(2, '0')
}

onMounted(() => {
  if (remaining.value <= 0) {
    expired.value = true
    return
  }
  interval = setInterval(() => {
    now.value = Date.now()
    if (remaining.value <= 0) {
      expired.value = true
      clearInterval(interval)
    }
  }, 1000)
})

onBeforeUnmount(() => {
  if (interval) clearInterval(interval)
})
</script>
