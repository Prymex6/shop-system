<template>
  <ManagerLayout :title="article ? t('common.edit_article') : t('common.new_article_2')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ article ? t('common.edit_article') : t('common.new_article_2') }}
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ article ? t('common.edit_an_existing_blog_post') : t('common.add_a_new_blog_post') }}
          </p>
        </div>
        <Link :href="route('tenant.manager.articles.index')" class="text-gray-500 hover:text-gray-700 text-sm">
          {{ t('manager.articles.form.larr_back_to_the_list') }}
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Main content -->
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.title_2') }}</label>
            <input
              v-model="form.title"
              type="text"
              required
              @input="autoSlug"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.article_title')"
            />
            <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.articles.form.slug_url')
            }}</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
              placeholder="slug-artykulu"
            />
            <p v-if="form.errors.slug" class="text-red-600 text-sm mt-1">{{ form.errors.slug }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.articles.form.excerpt') }}</label>
            <textarea
              v-model="form.excerpt"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.a_short_description_shown_in_the')"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.content') }}</label>
            <textarea
              v-model="form.content"
              rows="16"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.article_content_html_or_markdown')"
            ></textarea>
            <p v-if="form.errors.content" class="text-red-600 text-sm mt-1">{{ form.errors.content }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.articles.form.main_image_url')
            }}</label>
            <input
              v-model="form.cover_image"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.https_or_storage')"
            />
          </div>
        </div>

        <!-- Publishing -->
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.articles.form.publication') }}</h2>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.status') }}</label>
              <select
                v-model="form.status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="draft">{{ t('manager.articles.form.draft') }}</option>
                <option value="published">{{ t('manager.articles.form.published') }}</option>
                <option value="archived">{{ t('manager.articles.form.archived') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.publication_date') }}</label>
              <input
                v-model="form.published_at"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- SEO -->
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">SEO</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.meta_title') }}</label>
            <input
              v-model="form.meta_title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.defaults_to_the_article_s_title')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.meta_description') }}</label>
            <textarea
              v-model="form.meta_description"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.articles.form.description_for_search_engines_160_characters')"
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <a
            v-if="form.slug"
            :href="route('tenant.blog.show', form.slug)"
            target="_blank"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
          >
            {{ t('common.preview') }}
          </a>
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ form.processing ? 'Zapisywanie...' : t('common.save_the_article') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  article: { type: Object, default: null },
})

const form = useForm({
  title: props.article?.title ?? '',
  slug: props.article?.slug ?? '',
  excerpt: props.article?.excerpt ?? '',
  content: props.article?.content ?? '',
  cover_image: props.article?.cover_image ?? '',
  status: props.article?.status ?? 'draft',
  published_at: props.article?.published_at ? props.article.published_at.substring(0, 16) : '',
  meta_title: props.article?.meta_title ?? '',
  meta_description: props.article?.meta_description ?? '',
})

function autoSlug() {
  if (!props.article) {
    form.slug = form.title
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '')
  }
}

function submit() {
  if (props.article) {
    form.put(route('tenant.manager.articles.update', props.article.id))
  } else {
    form.post(route('tenant.manager.articles.store'), {
      onSuccess: () => form.reset(),
    })
  }
}
</script>
