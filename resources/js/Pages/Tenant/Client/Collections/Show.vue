<template>
  <ClientLayout :title="collection.name" :description="collection.description">
    <!-- Collection header -->
    <div class="relative bg-gradient-to-br from-indigo-600 to-purple-700 text-white overflow-hidden">
      <div v-if="collection.image" class="absolute inset-0">
        <img
          :src="'/storage/' + collection.image"
          :alt="collection.name"
          class="w-full h-full object-cover opacity-25"
        />
      </div>
      <div class="relative max-w-7xl mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl font-bold mb-3">{{ collection.name }}</h1>
        <p v-if="collection.description" class="text-xl text-indigo-100 max-w-2xl mx-auto">
          {{ collection.description }}
        </p>
        <p class="mt-4 text-indigo-200 text-sm">
          {{ t('common.a_products_in_the_collection', { a: products.total ?? products.data?.length ?? 0 }) }}
        </p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">
      <!-- Empty state -->
      <div v-if="!products.data?.length" class="text-center py-16 text-gray-400">
        <i class="fa-solid fa-box-open text-5xl mb-4 block opacity-30"></i>
        <p class="text-lg">{{ t('client.collections.show.this_collection_is_empty_for_now') }}</p>
        <Link :href="route('tenant.shop')" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-medium">
          {{ t('client.collections.show.go_to_the_shop') }}
        </Link>
      </div>

      <!-- Product grid -->
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
      </div>

      <!-- Pagination -->
      <div v-if="products.last_page > 1" class="mt-10 flex justify-center gap-2">
        <Link
          v-for="link in products.links"
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
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import ProductCard from '@/Components/Client/ProductCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  collection: { type: Object, required: true },
  products: { type: Object, required: true },
})
</script>
