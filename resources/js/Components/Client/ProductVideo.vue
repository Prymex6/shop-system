<template>
  <div v-if="videoUrl" class="w-full rounded-xl overflow-hidden bg-black aspect-video">
    <!-- YouTube embed -->
    <iframe
      v-if="youtubeId"
      :src="`https://www.youtube.com/embed/${youtubeId}?rel=0&modestbranding=1`"
      class="w-full h-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      allowfullscreen
    ></iframe>

    <!-- Vimeo embed -->
    <iframe
      v-else-if="vimeoId"
      :src="`https://player.vimeo.com/video/${vimeoId}?color=4f46e5&title=0&byline=0&portrait=0`"
      class="w-full h-full"
      frameborder="0"
      allow="autoplay; fullscreen; picture-in-picture"
      allowfullscreen
    ></iframe>

    <!-- Direct video file -->
    <video v-else-if="isDirectVideo" :src="videoUrl" controls class="w-full h-full" preload="metadata">
      {{ t('client.productvideo.your_browser_cannot_play_this_video') }}
    </video>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  videoUrl: { type: String, default: null },
})

const youtubeId = computed(() => {
  if (!props.videoUrl) return null
  const patterns = [
    /youtube\.com\/watch\?v=([^&]+)/,
    /youtu\.be\/([^?&]+)/,
    /youtube\.com\/embed\/([^?&]+)/,
    /youtube\.com\/shorts\/([^?&]+)/,
  ]
  for (const pattern of patterns) {
    const match = props.videoUrl.match(pattern)
    if (match) return match[1]
  }
  return null
})

const vimeoId = computed(() => {
  if (!props.videoUrl) return null
  const match = props.videoUrl.match(/vimeo\.com\/(?:video\/)?(\d+)/)
  return match ? match[1] : null
})

const isDirectVideo = computed(() => {
  if (!props.videoUrl) return false
  return /\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(props.videoUrl)
})
</script>
