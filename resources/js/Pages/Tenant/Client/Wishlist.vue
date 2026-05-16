<template>
  <ClientLayout :title="t('common.wishlist')">
    <div class="max-w-6xl mx-auto px-4 py-10">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ t('common.wishlist') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ t('client.wishlist.products_you_want_to_buy_later') }}</p>
      </div>

      <!-- Empty state -->
      <div v-if="items.length === 0" class="text-center py-20 bg-white rounded-2xl border border-gray-200">
        <div class="text-5xl mb-4">🤍</div>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">{{ t('client.wishlist.your_wishlist_is_empty') }}</h2>
        <p class="text-gray-400 text-sm mb-6">{{ t('client.wishlist.click_the_heart_on_a_product') }}</p>
        <Link
          :href="route('tenant.shop')"
          class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-indigo-700"
        >
          {{ t('common.browse_the_shop') }}
        </Link>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
        <div
          v-for="item in items"
          :key="item.id"
          class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group"
        >
          <!-- Image -->
          <Link :href="route('tenant.product.show', item.product.slug)" class="block">
            <div class="aspect-square overflow-hidden bg-gray-100">
              <img
                :src="
                  item.product.images?.[0]?.path
                    ? item.product.images[0].path.startsWith('/') || item.product.images[0].path.startsWith('http')
                      ? item.product.images[0].path
                      : '/storage/' + item.product.images[0].path
                    : '/images/placeholder.png'
                "
                :alt="item.product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
          </Link>

          <!-- Info -->
          <div class="p-3">
            <Link
              :href="route('tenant.product.show', item.product.slug)"
              class="block text-sm font-semibold text-gray-900 hover:text-indigo-600 leading-tight line-clamp-2 mb-2"
            >
              {{ item.product.name }}
            </Link>

            <div class="flex items-center justify-between">
              <span class="text-indigo-600 font-bold text-sm">
                {{ formatPrice(item.product.price) }}
              </span>
              <button
                @click="remove(item)"
                class="text-gray-400 hover:text-red-500 transition-colors"
                :title="t('client.wishlist.remove_from_the_wishlist')"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
            </div>

            <button
              @click="addToCart(item)"
              :disabled="!item.product.is_in_stock"
              class="mt-2 w-full py-1.5 text-xs font-semibold rounded-lg transition"
              :class="
                item.product.is_in_stock
                  ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'
              "
            >
              {{ item.product.is_in_stock ? t('common.add_to_cart') : t('client.shop.product.out_of_stock') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useCartStore } from '@/Stores/cartStore'
import { useWishlistStore } from '@/Stores/wishlistStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({ items: Array })

const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

function remove(item) {
  wishlistStore.toggle(item.product.id)
  router.reload()
}

function addToCart(item) {
  if (!item.product.is_in_stock) return
  cartStore.add({ product: item.product, variant: null, quantity: 1 })
  cartStore.openCart()
}

const { formatPrice } = useCurrency()
</script>
