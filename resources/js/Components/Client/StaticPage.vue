<template>
  <div class="bg-gray-50 min-h-screen">
    <!-- Page header -->
    <div class="bg-white border-b border-gray-100">
      <div class="max-w-3xl mx-auto px-4 py-10">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center shrink-0">
            <i :class="[icon, 'text-indigo-600 text-lg']"></i>
          </div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ title }}</h1>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-3xl mx-auto px-4 py-10">
      <div
        v-if="content"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 md:p-10 prose prose-gray max-w-none prose-headings:font-bold prose-h2:text-xl prose-h3:text-base prose-a:text-indigo-600"
        v-html="content"
      />
      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
        <i class="fa-solid fa-file-lines text-4xl mb-4 block opacity-30"></i>
        <p class="text-sm">{{ t('client.staticpage.the_shop_s_owner_has_not') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  title: { type: String, required: true },
  icon: { type: String, default: 'fa-solid fa-file-lines' },
  content: { type: String, default: '' },
})
</script>
