<template>
  <ClientLayout :title="t('layout.clientlayout.collections')">
    <div class="max-w-7xl mx-auto px-4 py-12">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ t('layout.clientlayout.collections') }}</h1>
      <p class="text-gray-500 mb-10">
        {{ t('client.collections.index.discover_our_hand_picked_product_collections') }}
      </p>

      <div v-if="!collections.length" class="text-center py-20 text-gray-400">
        <i class="fa-solid fa-layer-group text-5xl mb-4 block opacity-30"></i>
        <p class="text-lg">{{ t('client.collections.index.no_active_collections') }}</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <Link
          v-for="col in collections"
          :key="col.id"
          :href="route('tenant.collections.show', col.slug)"
          class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-indigo-200 transition-all duration-300"
        >
          <!-- Image -->
          <div class="aspect-video bg-gradient-to-br from-indigo-50 to-purple-50 overflow-hidden">
            <img
              v-if="col.image"
              :src="'/storage/' + col.image"
              :alt="col.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <i class="fa-solid fa-layer-group text-5xl text-indigo-200"></i>
            </div>
          </div>

          <!-- Content -->
          <div class="p-5">
            <h2 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
              {{ col.name }}
            </h2>
            <p v-if="col.description" class="text-sm text-gray-500 line-clamp-2 mb-3">
              {{ col.description }}
            </p>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-400">
                {{ col.products_count ?? 0 }} {{ pluralProducts(col.products_count ?? 0) }}
              </span>
              <span
                class="text-indigo-600 text-sm font-semibold group-hover:translate-x-1 transition-transform inline-block"
              >
                {{ t('client.collections.index.browse') }}
              </span>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  collections: { type: Array, default: () => [] },
})

const pluralProducts = (count) => {
  if (count === 1) return 'produkt'
  if (count >= 2 && count <= 4) return 'produkty'
  return t('common.products_3')
}
</script>
