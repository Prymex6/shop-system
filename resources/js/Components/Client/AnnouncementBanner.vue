<template>
  <div
    v-if="visible && announcement?.enabled && announcement?.text"
    class="relative flex items-center justify-center px-10 py-2.5 text-sm font-medium text-white"
    :style="{ backgroundColor: announcement.color || '#4f46e5' }"
  >
    <span>{{ announcement.text }}</span>
    <button
      @click="dismiss"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-white/80 hover:text-white text-lg leading-none"
      :aria-label="t('common.close')"
    >
      &times;
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const page = usePage()
const announcement = computed(() => page.props.announcement)
const visible = ref(true)

const STORAGE_KEY = computed(() => `announcement_dismissed_${announcement.value?.text?.substring(0, 20) ?? 'banner'}`)

onMounted(() => {
  if (sessionStorage.getItem(STORAGE_KEY.value) === '1') {
    visible.value = false
  }
})

function dismiss() {
  visible.value = false
  sessionStorage.setItem(STORAGE_KEY.value, '1')
}
</script>
