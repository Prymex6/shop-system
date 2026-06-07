<template>
  <Head :title="page.title" />
  <ClientLayout>
    <div class="max-w-4xl mx-auto px-4 py-12 space-y-0">
      <template v-for="block in page.blocks" :key="block.id">
        <!-- Hero -->
        <div
          v-if="block.type === 'hero'"
          class="relative rounded-2xl overflow-hidden flex items-center px-10 py-16 mb-8"
          :style="{
            background: block.data.bg_color || '#1e1b4b',
            color: block.data.text_color || '#fff',
            backgroundImage: block.data.image_url ? `url(${block.data.image_url})` : undefined,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
          }"
        >
          <div class="relative z-10">
            <h1 class="text-3xl md:text-5xl font-bold mb-4">{{ block.data.heading }}</h1>
            <p class="text-lg opacity-80 mb-6">{{ block.data.subheading }}</p>
            <a
              v-if="block.data.button_text"
              :href="block.data.button_url"
              class="inline-block bg-white text-gray-900 font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition"
            >
              {{ block.data.button_text }}
            </a>
          </div>
          <div v-if="block.data.image_url" class="absolute inset-0 bg-black/40"></div>
        </div>

        <!-- Text -->
        <div v-else-if="block.type === 'text'" class="prose max-w-none mb-8" :class="`text-${block.data.align}`">
          <p style="white-space: pre-line">{{ block.data.content }}</p>
        </div>

        <!-- Image -->
        <div v-else-if="block.type === 'image'" class="mb-8 text-center">
          <a :href="block.data.link || undefined">
            <img :src="block.data.url" :alt="block.data.alt" class="mx-auto rounded-xl max-w-full" />
          </a>
        </div>

        <!-- Columns -->
        <div v-else-if="block.type === 'columns'" class="grid md:grid-cols-2 gap-8 mb-8">
          <div class="prose max-w-none">
            <p style="white-space: pre-line">{{ block.data.left }}</p>
          </div>
          <div class="prose max-w-none">
            <p style="white-space: pre-line">{{ block.data.right }}</p>
          </div>
        </div>

        <!-- Button -->
        <div v-else-if="block.type === 'button'" class="mb-8" :class="`text-${block.data.align}`">
          <a
            :href="block.data.url"
            :class="
              block.data.style === 'primary'
                ? 'theme-primary-bg text-white hover:opacity-90'
                : 'border border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white'
            "
            class="inline-block font-semibold px-8 py-3 rounded-xl transition"
          >
            {{ block.data.text }}
          </a>
        </div>

        <!-- Divider -->
        <div
          v-else-if="block.type === 'divider'"
          class="border-t border-gray-200"
          :style="{ margin: `${block.data.margin}px 0` }"
        ></div>

        <!-- HTML -->
        <div v-else-if="block.type === 'html'" class="mb-8" v-html="block.data.code"></div>

        <!-- Spacer -->
        <div v-else-if="block.type === 'spacer'" :style="{ height: block.data.height + 'px' }"></div>
      </template>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
defineProps({ page: { type: Object, required: true } })
</script>
