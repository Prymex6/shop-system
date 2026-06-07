<template>
  <!-- Hero -->
  <div
    v-if="block.type === 'hero'"
    class="rounded-lg overflow-hidden flex items-center justify-between p-6 min-h-[80px]"
    :style="{ background: block.data.bg_color || '#1e1b4b', color: block.data.text_color || '#fff' }"
  >
    <div>
      <p class="font-bold text-sm">{{ block.data.heading }}</p>
      <p class="text-xs opacity-70 mt-0.5">{{ block.data.subheading }}</p>
    </div>
    <span v-if="block.data.button_text" class="text-xs bg-white/20 px-3 py-1 rounded-full">{{
      block.data.button_text
    }}</span>
  </div>

  <!-- Text -->
  <div v-else-if="block.type === 'text'" class="text-sm text-gray-700 line-clamp-3" :class="`text-${block.data.align}`">
    {{ block.data.content }}
  </div>

  <!-- Image -->
  <div v-else-if="block.type === 'image'" class="text-center">
    <img
      v-if="block.data.url"
      :src="block.data.url"
      :alt="block.data.alt"
      class="max-h-24 mx-auto rounded object-contain"
    />
    <div v-else class="bg-gray-100 rounded-lg h-16 flex items-center justify-center text-gray-400 text-xs">
      <i class="fa-solid fa-image mr-2"></i> {{ t('manager.pagebuilder.blockpreview.no_image') }}
    </div>
  </div>

  <!-- Columns -->
  <div v-else-if="block.type === 'columns'" class="grid grid-cols-2 gap-3">
    <div class="bg-gray-50 rounded p-2 text-xs text-gray-600">{{ block.data.left }}</div>
    <div class="bg-gray-50 rounded p-2 text-xs text-gray-600">{{ block.data.right }}</div>
  </div>

  <!-- Button -->
  <div v-else-if="block.type === 'button'" :class="`text-${block.data.align}`">
    <span
      class="inline-block text-xs px-4 py-2 rounded-lg font-semibold"
      :class="block.data.style === 'primary' ? 'bg-blue-600 text-white' : 'border border-gray-400 text-gray-700'"
    >
      {{ block.data.text }}
    </span>
  </div>

  <!-- Divider -->
  <div v-else-if="block.type === 'divider'" class="border-t border-gray-300 my-1"></div>

  <!-- HTML -->
  <div v-else-if="block.type === 'html'" class="bg-gray-900 rounded p-2">
    <code class="text-green-400 text-xs line-clamp-2">{{ block.data.code }}</code>
  </div>

  <!-- Spacer -->
  <div
    v-else-if="block.type === 'spacer'"
    class="flex items-center justify-center text-gray-400 text-xs"
    :style="{ height: block.data.height / 4 + 'px' }"
  >
    ↕ {{ block.data.height }}px
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({ block: { type: Object, required: true } })
</script>
