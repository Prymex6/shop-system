<template>
  <ManagerLayout :title="t('manager.articles.index.blog_articles_2')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.articles.index.blog_articles') }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ t('manager.articles.index.manage_the_shop_s_blog') }}</p>
        </div>
        <Link
          :href="route('tenant.manager.articles.create')"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('common.new_article') }}
        </Link>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.title') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.publication_date') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.modifications.form.author') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!articles.data?.length">
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">{{ t('common.no_articles') }}</td>
              </tr>
              <tr v-for="article in articles.data" :key="article.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="font-semibold text-gray-900">{{ article.title }}</div>
                  <div class="text-xs text-gray-400">{{ article.slug }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="statusClass(article.status)"
                  >
                    {{ statusLabel(article.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ article.published_at ? formatDate(article.published_at) : '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ article.author?.name ?? '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-3">
                    <Link
                      :href="route('tenant.manager.articles.edit', article.id)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      {{ t('common.edit') }}
                    </Link>
                    <div class="relative">
                      <button @click="toggleStatusMenu(article.id)" class="text-blue-600 hover:text-blue-900">
                        {{ t('manager.articles.index.status') }}
                      </button>
                      <div
                        v-if="openStatusMenu === article.id"
                        class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-10"
                      >
                        <button
                          v-for="s in statuses"
                          :key="s.value"
                          @click="changeStatus(article, s.value)"
                          class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50"
                          :class="{ 'font-semibold text-blue-600': article.status === s.value }"
                        >
                          {{ s.label }}
                        </button>
                      </div>
                    </div>
                    <button @click="deleteArticle(article)" class="text-red-600 hover:text-red-900">
                      {{ t('common.delete') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="articles.data?.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ t('common.showing') }} <span class="font-medium">{{ articles.from }}</span> {{ t('common.to') }}
              <span class="font-medium">{{ articles.to }}</span> z <span class="font-medium">{{ articles.total }}</span>
            </div>
            <div class="flex space-x-2">
              <template v-for="link in articles.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  ]"
                  v-html="link.label"
                />
                <span
                  v-else
                  class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md opacity-50 cursor-not-allowed bg-white text-gray-700"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  articles: { type: Object, required: true },
})

const openStatusMenu = ref(null)

const statuses = [
  { value: 'draft', label: t('manager.articles.form.draft') },
  { value: 'published', label: t('manager.articles.form.published') },
  { value: 'archived', label: t('manager.articles.form.archived') },
]

function toggleStatusMenu(id) {
  openStatusMenu.value = openStatusMenu.value === id ? null : id
}

function changeStatus(article, status) {
  openStatusMenu.value = null
  router.patch(
    route('tenant.manager.articles.toggle-status', article.id),
    { status },
    {
      preserveScroll: true,
    },
  )
}

function deleteArticle(article) {
  if (!confirm(t('manager.articles.index.delete_the_article_a', { a: article.title }))) return
  router.delete(route('tenant.manager.articles.destroy', article.id))
}

function statusClass(status) {
  return (
    {
      draft: 'bg-gray-100 text-gray-700',
      published: 'bg-green-100 text-green-800',
      archived: 'bg-orange-100 text-orange-800',
    }[status] ?? 'bg-gray-100 text-gray-700'
  )
}

function statusLabel(status) {
  return (
    {
      draft: t('manager.articles.form.draft'),
      published: t('manager.articles.form.published'),
      archived: t('manager.articles.form.archived'),
    }[status] ?? status
  )
}

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value, { year: 'numeric', month: '2-digit', day: '2-digit' })
}
</script>
