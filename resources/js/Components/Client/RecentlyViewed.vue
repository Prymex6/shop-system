<template>
  <section v-if="items.length" class="py-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ t('client.recentlyviewed.recently_viewed') }}</h2>

    <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200">
      <Link
        v-for="item in displayItems"
        :key="item.id"
        :href="route('tenant.product.show', item.slug)"
        class="flex-shrink-0 w-40 bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md hover:theme-primary-border transition-all"
      >
        <div class="aspect-square bg-gray-50 overflow-hidden">
          <img
            v-if="item.image"
            :src="'/storage/' + item.image"
            :alt="item.name"
            class="w-full h-full object-cover"
            loading="lazy"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-3xl text-gray-200">📦</div>
        </div>
        <div class="p-3">
          <p class="text-xs font-semibold text-gray-900 line-clamp-2 leading-snug">{{ item.name }}</p>
          <p class="text-xs font-bold theme-primary mt-1">{{ formatPrice(item.price) }}</p>
        </div>
      </Link>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useRecentlyViewedStore } from '@/Stores/recentlyViewedStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const store = useRecentlyViewedStore()

const items = computed(() => store.getAll())
const displayItems = computed(() => items.value.slice(0, 6))

const { formatPrice } = useCurrency()
</script>
