<template>
  <ClientLayout :title="article.meta_title || article.title">
    <Head>
      <title>{{ article.meta_title || article.title }}</title>
      <meta name="description" :content="article.meta_description || article.excerpt || ''" />
      <meta property="og:title" :content="article.title" />
      <meta property="og:description" :content="article.excerpt || ''" />
      <meta v-if="article.cover_image" property="og:image" :content="article.cover_image" />
      <meta property="og:type" content="article" />
    </Head>

    <div class="max-w-3xl mx-auto px-4 py-12">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
        <Link :href="route('tenant.shop')" class="hover:text-indigo-600">{{ t('common.shop') }}</Link>
        <span>/</span>
        <Link :href="route('tenant.blog.index')" class="hover:text-indigo-600">Blog</Link>
        <span>/</span>
        <span class="text-gray-900 font-medium">{{ article.title }}</span>
      </nav>

      <!-- Cover image -->
      <div v-if="article.cover_image" class="aspect-video rounded-2xl overflow-hidden mb-8">
        <img :src="article.cover_image" :alt="article.title" class="w-full h-full object-cover" />
      </div>

      <!-- Article header -->
      <header class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ article.title }}</h1>
        <div class="flex items-center gap-4 text-sm text-gray-500">
          <time v-if="article.published_at" :datetime="article.published_at">
            {{ formatDate(article.published_at) }}
          </time>
          <span v-if="article.author" class="flex items-center gap-1">
            przez <strong class="text-gray-700">{{ article.author.name }}</strong>
          </span>
        </div>
        <p v-if="article.excerpt" class="mt-4 text-lg text-gray-600 leading-relaxed">
          {{ article.excerpt }}
        </p>
      </header>

      <!-- Article content -->
      <div class="prose prose-gray max-w-none text-gray-800 leading-relaxed" v-html="article.content"></div>

      <!-- Back link -->
      <div class="mt-12 pt-8 border-t">
        <Link
          :href="route('tenant.blog.index')"
          class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium transition"
        >
          {{ t('client.blog.show.larr_back_to_the_blog') }}
        </Link>
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
  article: { type: Object, required: true },
})

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>
