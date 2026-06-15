<template>
  <StaffLayout :title="t('manager.knowledgebase.index.knowledge_base')">
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('manager.knowledgebase.index.knowledge_base') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('staff.knowledgebase.index.articles_and_procedures_for_staff') }}</p>
      </div>

      <!-- Search -->
      <div class="relative">
        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('staff.knowledgebase.index.search_articles')"
          class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <!-- No results -->
      <div v-if="!filteredGroups.length" class="text-center py-12 text-gray-400">
        <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block opacity-30"></i>
        <p>{{ t('staff.knowledgebase.index.no_articles_found') }}</p>
      </div>

      <!-- Grouped articles -->
      <div v-for="(group, category) in filteredGroups" :key="category" class="space-y-3">
        <h2 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2">
          {{ category || t('common.general') }}
        </h2>
        <div class="space-y-2">
          <Link
            v-for="article in group"
            :key="article.id"
            :href="route('tenant.staff.knowledge-base.show', article.slug)"
            class="block bg-white rounded-xl border border-gray-100 px-5 py-4 hover:border-indigo-300 hover:shadow-sm transition"
          >
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-900 group-hover:text-indigo-600">{{ article.title }}</p>
              <i class="fa-solid fa-chevron-right text-gray-300 text-sm"></i>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">Zaktualizowano {{ formatDate(article.updated_at) }}</p>
          </Link>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  articles: { type: Array, default: () => [] },
})

const searchQuery = ref('')

const filteredGroups = computed(() => {
  const q = searchQuery.value.toLowerCase()
  const filtered = q
    ? props.articles.filter((a) => a.title.toLowerCase().includes(q) || (a.content ?? '').toLowerCase().includes(q))
    : props.articles

  // Group by category
  return filtered.reduce((acc, art) => {
    const cat = art.category || ''
    if (!acc[cat]) acc[cat] = []
    acc[cat].push(art)
    return acc
  }, {})
})

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(locale.value) : '—')
</script>
