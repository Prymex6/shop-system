<template>
  <StaffLayout :title="article.title">
    <div class="max-w-3xl mx-auto px-4 py-8">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm mb-6">
        <Link :href="route('tenant.staff.knowledge-base.index')" class="text-indigo-600 hover:text-indigo-800">
          {{ t('manager.knowledgebase.index.knowledge_base') }}
        </Link>
        <span class="text-gray-400">/</span>
        <span v-if="article.category" class="text-gray-500">{{ article.category }}</span>
        <span v-if="article.category" class="text-gray-400">/</span>
        <span class="text-gray-700">{{ article.title }}</span>
      </div>

      <article class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="p-8 border-b border-gray-100">
          <span
            v-if="article.category"
            class="inline-block px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full mb-3"
          >
            {{ article.category }}
          </span>
          <h1 class="text-2xl font-bold text-gray-900">{{ article.title }}</h1>
          <p class="text-xs text-gray-400 mt-2">Zaktualizowano: {{ formatDate(article.updated_at) }}</p>
        </div>

        <!-- Content -->
        <div class="p-8 prose prose-gray max-w-none">
          <div class="whitespace-pre-wrap text-gray-700 leading-relaxed text-sm">{{ article.content }}</div>
        </div>
      </article>

      <!-- Back -->
      <div class="mt-6">
        <Link
          :href="route('tenant.staff.knowledge-base.index')"
          class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 text-sm font-medium"
        >
          <i class="fa-solid fa-arrow-left"></i>
          {{ t('staff.knowledgebase.show.back_to_the_knowledge_base') }}
        </Link>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  article: { type: Object, required: true },
})

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString(locale.value, { day: '2-digit', month: 'long', year: 'numeric' }) : '—'
</script>
