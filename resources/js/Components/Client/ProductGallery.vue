<template>
  <div>
    <!-- Main image -->
    <div
      class="aspect-square rounded-2xl overflow-hidden bg-gray-100 mb-3 relative cursor-zoom-in"
      @click="openLightbox(activeIndex)"
    >
      <img :src="activeImageSrc" :alt="activeAlt" class="w-full h-full object-cover transition-opacity duration-200" />
      <!-- Badges -->
      <div class="absolute top-3 left-3 flex flex-col gap-1.5">
        <slot name="badges" />
      </div>
    </div>

    <!-- Thumbnails -->
    <div v-if="images.length > 1" class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
      <button
        v-for="(img, idx) in images"
        :key="img.id ?? idx"
        @click="activeIndex = idx"
        class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all"
        :class="
          activeIndex === idx ? 'theme-primary-border theme-primary-ring' : 'border-transparent hover:border-gray-300'
        "
      >
        <img :src="imgUrl(img.path)" :alt="img.alt ?? ''" class="w-full h-full object-cover" />
      </button>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <div
        v-if="lightboxOpen"
        class="fixed inset-0 z-[9999] bg-black/90 flex items-center justify-center p-4"
        @click.self="lightboxOpen = false"
      >
        <!-- Close -->
        <button @click="lightboxOpen = false" class="absolute top-4 right-4 text-white/80 hover:text-white">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Prev -->
        <button
          v-if="images.length > 1"
          @click="lightboxIndex = (lightboxIndex - 1 + images.length) % images.length"
          class="absolute left-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white"
        >
          <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Image -->
        <img
          :src="imgUrl(images[lightboxIndex].path)"
          :alt="images[lightboxIndex].alt ?? ''"
          class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg"
        />

        <!-- Next -->
        <button
          v-if="images.length > 1"
          @click="lightboxIndex = (lightboxIndex + 1) % images.length"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white"
        >
          <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Counter -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/60 text-sm">
          {{ lightboxIndex + 1 }} / {{ images.length }}
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  images: {
    type: Array,
    default: () => [],
  },
  fallback: {
    type: String,
    default: '/images/placeholder.png',
  },
})

const activeIndex = ref(0)
const lightboxOpen = ref(false)
const lightboxIndex = ref(0)

// Resolve image path: new /uploads/... paths are used as-is, legacy paths get /storage/ prefix
function imgUrl(path) {
  if (!path) return props.fallback
  if (path.startsWith('/') || path.startsWith('http')) return path
  return '/storage/' + path
}

const activeImageSrc = computed(() => {
  const img = props.images[activeIndex.value]
  return img ? imgUrl(img.path) : props.fallback
})

const activeAlt = computed(() => {
  return props.images[activeIndex.value]?.alt ?? ''
})

function openLightbox(idx) {
  if (props.images.length === 0) return
  lightboxIndex.value = idx
  lightboxOpen.value = true
}

// Keyboard navigation
function onKey(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') lightboxOpen.value = false
  if (e.key === 'ArrowLeft') lightboxIndex.value = (lightboxIndex.value - 1 + props.images.length) % props.images.length
  if (e.key === 'ArrowRight') lightboxIndex.value = (lightboxIndex.value + 1) % props.images.length
}

import { onMounted, onBeforeUnmount } from 'vue'
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>
