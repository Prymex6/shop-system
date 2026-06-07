<template>
  <ClientLayout title="Blog">
    <Head>
      <title>Blog – {{ $page.props.tenant?.name }}</title>
      <meta name="description" :content="t('client.blog.index.articles_and_news_from_the_shop')" />
    </Head>

    <div class="max-w-7xl mx-auto px-4 py-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-2">Blog</h1>
      <p class="text-gray-500 mb-10">{{ t('client.blog.index.articles_and_news') }}</p>

      <div v-if="articles.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article
          v-for="article in articles.data"
          :key="article.id"
          class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
        >
          <!-- Cover Image -->
          <Link :href="route('tenant.blog.show', article.slug)" class="block aspect-video bg-gray-100 overflow-hidden">
            <img
              v-if="article.cover_image"
              :src="article.cover_image"
              :alt="article.title"
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-200">📝</div>
          </Link>

          <div class="p-5 flex flex-col gap-3">
            <!-- Meta -->
            <div class="flex items-center gap-3 text-xs text-gray-400">
              <span v-if="article.published_at">{{ formatDate(article.published_at) }}</span>
              <span v-if="article.author">•</span>
              <span v-if="article.author">{{ article.author.name }}</span>
            </div>

            <!-- Title -->
            <Link
              :href="route('tenant.blog.show', article.slug)"
              class="text-lg font-bold text-gray-900 hover:text-indigo-600 transition line-clamp-2"
            >
              {{ article.title }}
            </Link>

            <!-- Excerpt -->
            <p v-if="article.excerpt" class="text-sm text-gray-600 line-clamp-3">
              {{ article.excerpt }}
            </p>

            <!-- Read more -->
            <Link
              :href="route('tenant.blog.show', article.slug)"
              class="mt-auto inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition"
            >
              {{ t('client.blog.index.read_more_rarr') }}
            </Link>
          </div>
        </article>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-20 text-gray-400">
        <div class="text-5xl mb-4">📝</div>
        <p class="text-lg">{{ t('common.no_articles') }}</p>
      </div>

      <!-- Pagination -->
      <div v-if="articles.last_page > 1" class="mt-10 flex justify-center gap-2">
        <Link
          v-for="link in articles.links"
          :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="px-3 py-2 rounded-lg text-sm border transition"
          :class="
            link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 hover:border-indigo-300'
          "
        />
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  articles: { type: Object, required: true },
})

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>
