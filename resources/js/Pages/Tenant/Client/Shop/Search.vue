<template>
  <ClientLayout :title="'Wyszukiwanie: ' + query">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        {{ t('manager.manualordermodal.results_for') }} <span class="text-indigo-600">{{ query }}</span>
      </h1>
      <p class="text-sm text-gray-500 mb-8">{{ t('common.a_results_count', { a: products.total }) }}</p>

      <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
      </div>

      <div v-else class="text-center py-20 text-gray-500">
        <p class="text-5xl mb-4">🔍</p>
        <p class="text-xl font-medium mb-2">{{ t('client.shop.search.no_results') }}</p>
        <p class="text-sm">{{ t('client.shop.search.try_another_phrase_or_browse_our') }}</p>
        <Link :href="route('tenant.shop')" class="mt-4 inline-block text-indigo-600 font-medium hover:underline">{{
          t('common.back_to_the_shop')
        }}</Link>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import ProductCard from '@/Components/Client/ProductCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  products: { type: Object, required: true },
  query: { type: String, default: '' },
})
</script>
