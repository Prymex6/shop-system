<template>
  <ManagerLayout :title="t('manager.knowledgebase.index.knowledge_base')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.knowledgebase.index.knowledge_base') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.knowledgebase.index.help_articles_for_staff') }}</p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('common.new_article') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.title') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.category') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.order_2') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.articles.form.published') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.date') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="article in articles.data" :key="article.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <p class="font-semibold text-gray-900">{{ article.title }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ article.slug }}</p>
              </td>
              <td class="px-4 py-3">
                <span
                  v-if="article.category"
                  class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-medium"
                >
                  {{ article.category }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-gray-500">{{ article.sort_order ?? 0 }}</td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="togglePublish(article)"
                  :class="
                    article.is_published
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                  "
                  class="px-2 py-0.5 rounded-full text-xs font-medium transition"
                >
                  {{ article.is_published ? 'Tak' : t('manager.collections.index.no') }}
                </button>
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(article.created_at) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(article)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(article)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!articles.data?.length">
              <td colspan="6" class="text-center py-12 text-gray-400">{{ t('common.no_articles') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_article') : t('common.new_article_2') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.title_2') }}</label>
            <input
              v-model="form.title"
              @input="autoSlug"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.category') }}</label>
            <input
              v-model="form.category"
              type="text"
              :placeholder="t('manager.knowledgebase.index.e_g_procedures_faq_complaints')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.content') }}</label>
            <textarea
              v-model="form.content"
              rows="10"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono resize-y min-h-[200px]"
            ></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.knowledgebase.index.display_order')
              }}</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
            <div class="flex items-end pb-1">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_published" type="checkbox" class="h-4 w-4 text-blue-600 rounded" />
                <span class="text-sm font-medium text-gray-700">{{ t('manager.articles.form.published') }}</span>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ editing ? t('common.save') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  articles: { type: Object, required: true },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({ title: '', slug: '', category: '', content: '', is_published: false, sort_order: 0 })

const autoSlug = () => {
  if (!editing.value) {
    form.slug = form.title
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '')
  }
}

const openModal = (art = null) => {
  editing.value = art
  if (art) {
    form.title = art.title ?? ''
    form.slug = art.slug ?? ''
    form.category = art.category ?? ''
    form.content = art.content ?? ''
    form.is_published = art.is_published ?? false
    form.sort_order = art.sort_order ?? 0
  } else {
    form.reset()
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.knowledge-base.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.knowledge-base.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const togglePublish = (art) => {
  router.put(
    route('tenant.manager.knowledge-base.update', art.id),
    { is_published: !art.is_published },
    { preserveScroll: true },
  )
}

const destroy = (art) => {
  if (!confirm(t('manager.articles.index.delete_the_article_a', { a: art.title }))) return
  router.delete(route('tenant.manager.knowledge-base.destroy', art.id))
}

const formatDate = (d) => new Date(d).toLocaleDateString(locale.value)
</script>
