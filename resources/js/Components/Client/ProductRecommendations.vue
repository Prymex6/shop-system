<template>
  <section v-if="products.length" class="py-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6">{{ title || t('client.shop.product.similar_products') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Link
        v-for="product in products"
        :key="product.id"
        :href="route('tenant.product.show', product.slug)"
        class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md hover:theme-primary-border transition-all"
      >
        <!-- Image -->
        <div class="aspect-square bg-gray-50 overflow-hidden">
          <img
            v-if="primaryImage(product)"
            :src="'/storage/' + primaryImage(product)"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-200">
            {{ product.type === 'digital' ? '📥' : '📦' }}
          </div>
        </div>

        <!-- Info -->
        <div class="p-4">
          <p class="text-sm font-semibold text-gray-900 group-hover:theme-primary transition line-clamp-2">
            {{ product.name }}
          </p>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="text-sm font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
            <span
              v-if="product.compare_price && product.compare_price > product.price"
              class="text-xs text-gray-400 line-through"
            >
              {{ formatPrice(product.compare_price) }}
            </span>
          </div>
        </div>
      </Link>
    </div>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  products: { type: Array, default: () => [] },
  title: { type: String, default: '' },
})

function primaryImage(product) {
  return product.images?.[0]?.path ?? product.image ?? null
}

const { formatPrice } = useCurrency()
</script>
