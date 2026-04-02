<template>
  <ClientLayout :title="bundle.name" :description="bundle.description">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <Link :href="route('tenant.shop')" class="hover:text-indigo-600">{{ t('common.shop') }}</Link>
        <span>/</span>
        <span class="text-gray-900 font-medium">{{ bundle.name }}</span>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Image -->
        <div class="aspect-square rounded-2xl overflow-hidden bg-gray-100">
          <img
            v-if="bundle.image"
            :src="'/storage/' + bundle.image"
            :alt="bundle.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-6xl text-gray-300">🎁</div>
        </div>

        <!-- Info -->
        <div class="flex flex-col gap-6">
          <div>
            <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">{{
              t('client.shop.bundle.product_bundle')
            }}</span>
            <h1 class="text-3xl font-bold text-gray-900 mt-3">{{ bundle.name }}</h1>
            <p v-if="bundle.description" class="text-gray-600 mt-2">{{ bundle.description }}</p>
          </div>

          <div class="flex items-baseline gap-3">
            <span class="text-3xl font-bold text-gray-900">{{ formatPrice(bundle.price) }}</span>
            <span
              v-if="bundle.compare_price && bundle.compare_price > bundle.price"
              class="text-lg text-gray-400 line-through"
            >
              {{ formatPrice(bundle.compare_price) }}
            </span>
          </div>

          <!-- Bundle contents -->
          <div class="border border-gray-200 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 mb-3">{{ t('client.shop.bundle.in_the_bundle') }}</h2>
            <ul class="space-y-2">
              <li v-for="item in bundle.items" :key="item.id" class="flex items-center justify-between text-sm">
                <span class="text-gray-700">
                  {{ item.quantity }}× {{ item.product?.name ?? t('common.product_unavailable') }}
                  <span v-if="item.variant?.label" class="text-gray-400">({{ item.variant.label }})</span>
                </span>
              </li>
            </ul>
          </div>

          <p v-if="!allItemsAvailable" class="text-sm text-red-600 font-medium">
            {{ t('client.shop.bundle.this_bundle_is_unavailable_for_now') }}
          </p>

          <div class="flex items-center gap-4">
            <div class="flex items-center border border-gray-300 rounded-xl">
              <button
                @click="qty = Math.max(1, qty - 1)"
                class="w-11 h-11 flex items-center justify-center text-gray-500 hover:text-gray-900"
              >
                −
              </button>
              <span class="w-10 text-center font-semibold">{{ qty }}</span>
              <button
                @click="qty++"
                class="w-11 h-11 flex items-center justify-center text-gray-500 hover:text-gray-900"
              >
                +
              </button>
            </div>
            <button
              @click="addToCart"
              :disabled="!allItemsAvailable"
              class="flex-1 theme-primary-bg hover:opacity-90 text-white font-semibold py-3.5 rounded-xl transition disabled:opacity-40"
            >
              {{ t('client.shop.bundle.add_the_bundle_to_the_cart') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useCartStore } from '@/Stores/cartStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  bundle: { type: Object, required: true },
})

const cart = useCartStore()
const qty = ref(1)

// A bundle can't be added if any component is out of stock for tracked
// products — the checkout-time decomposition would otherwise fail anyway,
// better to say so up front.
const allItemsAvailable = computed(() =>
  (props.bundle.items ?? []).every((item) => {
    if (!item.product) return false
    const stock = item.variant?.stock_quantity ?? item.product.stock_quantity
    const tracked = item.variant ? true : item.product.track_stock
    return !tracked || stock === null || stock === undefined || stock >= item.quantity
  }),
)

const { formatPrice } = useCurrency()

const addToCart = () => {
  cart.addBundle({ bundle: props.bundle, quantity: qty.value })
}
</script>
