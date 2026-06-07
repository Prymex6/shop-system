<template>
  <ManagerLayout :title="t('layout.managerlayout.page_builder')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('layout.managerlayout.page_builder') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.pagebuilder.index.build_your_own_pages_by_dragging') }}
          </p>
        </div>
        <Link
          :href="route('tenant.manager.page-builder.create')"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold flex items-center gap-2"
        >
          <i class="fa-solid fa-plus"></i> {{ t('manager.pagebuilder.index.new_page') }}
        </Link>
      </div>

      <div v-if="pages.length" class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">{{ t('common.title') }}</th>
              <th class="text-left px-4 py-3">Slug</th>
              <th class="text-left px-4 py-3">{{ t('manager.pagebuilder.editor.blocks') }}</th>
              <th class="text-left px-4 py-3">{{ t('common.status') }}</th>
              <th class="text-left px-4 py-3">{{ t('common.date') }}</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in pages" :key="p.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ p.title }}</td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs">/strona/{{ p.slug }}</td>
              <td class="px-4 py-3 text-gray-500">{{ p.blocks?.length ?? 0 }}</td>
              <td class="px-4 py-3">
                <span
                  :class="p.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="text-xs font-semibold px-2 py-1 rounded-full"
                >
                  {{
                    p.status === 'published'
                      ? t('manager.pagebuilder.editor.published')
                      : t('manager.articles.form.draft')
                  }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(p.created_at) }}</td>
              <td class="px-4 py-3 flex items-center justify-end gap-2">
                <Link
                  :href="route('tenant.manager.page-builder.edit', p.id)"
                  class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                  >{{ t('common.edit') }}</Link
                >
                <button @click="deletePage(p)" class="text-red-400 hover:text-red-600 text-xs">
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
        <i class="fa-solid fa-layer-group text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">{{ t('manager.pagebuilder.index.no_pages') }}</h3>
        <p class="text-gray-400 text-sm mb-6">{{ t('manager.pagebuilder.index.build_your_first_page_by_dragging') }}</p>
        <Link
          :href="route('tenant.manager.page-builder.create')"
          class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold"
        >
          {{ t('manager.pagebuilder.index.create_page') }}
        </Link>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({ pages: { type: Array, default: () => [] } })

const formatDate = (d) => new Date(d).toLocaleDateString(locale.value)

const deletePage = (p) => {
  if (!confirm(t('manager.pagebuilder.index.delete_the_page_a', { a: p.title }))) return
  router.delete(route('tenant.manager.page-builder.destroy', p.id))
}
</script>
