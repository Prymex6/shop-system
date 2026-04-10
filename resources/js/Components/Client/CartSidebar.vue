<template>
  <Teleport to="body">
    <Transition name="sidebar">
      <div v-if="cartStore.isCartOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50 transition-opacity"
          @click="cartStore.closeCart()"
          aria-hidden="true"
        ></div>

        <!-- Sidebar -->
        <div
          ref="panelEl"
          role="dialog"
          aria-modal="true"
          :aria-label="t('common.cart')"
          tabindex="-1"
          @keydown.esc="cartStore.closeCart()"
          class="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-xl flex flex-col focus:outline-none"
        >
          <!-- Header -->
          <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Koszyk ({{ cartStore.itemCount }})</h2>
            <button @click="cartStore.closeCart()" class="text-gray-400 hover:text-gray-600 text-xl">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Cart Items -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <div v-if="cartStore.items.length === 0" class="text-center py-12">
              <div class="text-6xl mb-4">🛒</div>
              <p class="text-gray-500 text-lg">{{ t('common.your_cart_is_empty') }}</p>
            </div>

            <template v-else>
              <!-- Free shipping progress -->
              <div v-if="freeShippingThreshold > 0" class="mb-4 bg-gray-50 rounded-xl p-3">
                <p v-if="freeShippingReached" class="text-sm font-medium text-green-700 flex items-center gap-1.5">
                  <i class="fa-solid fa-circle-check"></i> {{ t('client.cartsidebar.you_have_free_delivery') }}
                </p>
                <p v-else class="text-sm text-gray-700">
                  {{ t('client.cartsidebar.still') }} <strong>{{ formatPrice(freeShippingRemaining) }}</strong>
                  {{ t('client.cartsidebar.to_free_delivery') }}
                </p>
                <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full theme-primary-bg transition-all duration-300"
                    :class="{ 'bg-green-500': freeShippingReached }"
                    :style="{ width: freeShippingProgressPercent + '%' }"
                  ></div>
                </div>
              </div>

              <div class="space-y-4">
                <div v-for="item in cartStore.items" :key="item.key" class="bg-gray-50 rounded-xl p-4">
                  <!-- Image + Name -->
                  <div class="flex gap-3 mb-3">
                    <img
                      v-if="item.product.image"
                      :src="'/storage/' + item.product.image"
                      :alt="item.product.name"
                      class="w-14 h-14 rounded-lg object-cover flex-shrink-0"
                    />
                    <div
                      v-else
                      class="w-14 h-14 rounded-lg bg-gray-200 flex items-center justify-center flex-shrink-0 text-2xl"
                    >
                      📦
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-semibold text-gray-900 text-sm leading-tight">{{ item.product.name }}</p>
                      <p v-if="item.variant" class="text-xs text-gray-500 mt-0.5">{{ item.variantLabel }}</p>
                      <span
                        v-if="item.product.type === 'digital'"
                        class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-medium"
                        >{{ t('client.cartsidebar.digital') }}</span
                      >
                    </div>
                    <button @click="cartStore.remove(item.id)" class="text-red-400 hover:text-red-600 flex-shrink-0">
                      <i class="fa-solid fa-trash text-sm"></i>
                    </button>
                  </div>

                  <!-- Quantity & Price -->
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <button
                        @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                        class="w-7 h-7 rounded-full bg-white border border-gray-200 hover:bg-gray-100 flex items-center justify-center text-sm"
                      >
                        −
                      </button>
                      <span class="w-6 text-center font-semibold text-sm">{{ item.quantity }}</span>
                      <button
                        @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                        class="w-7 h-7 rounded-full bg-white border border-gray-200 hover:bg-gray-100 flex items-center justify-center text-sm"
                      >
                        +
                      </button>
                    </div>
                    <div class="text-right">
                      <span class="font-bold text-gray-900">{{ formatPrice(item.price * item.quantity) }}</span>
                      <p
                        v-if="item.comparePrice && item.comparePrice > item.price"
                        class="text-xs text-gray-400 line-through"
                      >
                        {{ formatPrice(item.comparePrice * item.quantity) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cross-sell: "add this too" -->
              <div v-if="upsellProducts.length" class="mt-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">{{ t('client.cartsidebar.add_as_well') }}</p>
                <div class="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
                  <div
                    v-for="p in upsellProducts"
                    :key="p.id"
                    class="flex-shrink-0 w-28 bg-gray-50 rounded-xl p-2 text-center"
                  >
                    <img
                      v-if="p.image"
                      :src="'/storage/' + p.image"
                      :alt="p.name"
                      class="w-full h-16 object-cover rounded-lg mb-1"
                    />
                    <div
                      v-else
                      class="w-full h-16 rounded-lg bg-gray-200 flex items-center justify-center text-xl mb-1"
                    >
                      📦
                    </div>
                    <p class="text-xs font-medium text-gray-900 line-clamp-2 leading-tight">{{ p.name }}</p>
                    <p class="text-xs font-bold text-gray-900 mt-0.5">{{ formatPrice(p.price) }}</p>
                    <button
                      @click="addUpsellProduct(p)"
                      class="mt-1 w-full text-xs font-semibold theme-primary-bg hover:opacity-90 text-white rounded-lg py-1"
                    >
                      {{ t('common.add_2') }}
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div v-if="cartStore.items.length > 0" class="border-t px-6 py-4 bg-gray-50 space-y-3">
            <!-- Discount badge -->
            <div v-if="cartStore.discountAmount > 0" class="flex justify-between text-sm">
              <span class="text-green-700 font-medium">{{ t('common.discount') }}</span>
              <span class="text-green-700 font-bold">−{{ formatPrice(cartStore.discountAmount) }}</span>
            </div>

            <!-- Subtotal -->
            <div class="flex justify-between items-center">
              <span class="text-lg text-gray-600">{{ t('common.total_2') }}</span>
              <span class="text-2xl font-bold text-gray-900">{{ formatPrice(cartStore.subtotal) }}</span>
            </div>

            <!-- Info digital -->
            <p v-if="cartStore.hasDigitalItems" class="text-xs text-purple-600 bg-purple-50 rounded-lg px-3 py-2">
              {{ t('client.cartsidebar.digital_products_are_emailed_once_the') }}
            </p>

            <!-- Checkout Button -->
            <a
              :href="route('tenant.checkout')"
              class="block w-full theme-primary-bg hover:opacity-90 text-white text-center py-3 rounded-xl font-semibold transition-colors"
            >
              {{ t('client.cartsidebar.go_to_checkout') }}
            </a>

            <!-- Clear Cart -->
            <button @click="clearCartConfirm" class="w-full text-gray-500 hover:text-gray-700 py-1 text-sm">
              {{ t('client.cartsidebar.empty_the_cart') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useCartStore } from '@/Stores/cartStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const cartStore = useCartStore()
const panelEl = ref(null)
const page = usePage()

watch(
  () => cartStore.isCartOpen,
  (isOpen) => {
    if (isOpen) nextTick(() => panelEl.value?.focus())
  },
)

const { formatPrice } = useCurrency()

const clearCartConfirm = () => {
  if (confirm(t('common.are_you_sure_you_want_to_2'))) cartStore.clearCart()
}

// ─── Free shipping progress ─────────────────────────────────────────────

const freeShippingThreshold = computed(() => Number(page.props.tenant?.free_shipping_threshold ?? 0))
const freeShippingReached = computed(
  () => freeShippingThreshold.value > 0 && cartStore.subtotal >= freeShippingThreshold.value,
)
const freeShippingRemaining = computed(() => Math.max(0, freeShippingThreshold.value - cartStore.subtotal))
const freeShippingProgressPercent = computed(() => {
  if (freeShippingThreshold.value <= 0) return 0
  return Math.min(100, (cartStore.subtotal / freeShippingThreshold.value) * 100)
})

// ─── Cross-sell suggestions ──────────────────────────────────────────────

const upsellProducts = ref([])
let upsellFetchTimeout = null

async function fetchUpsellSuggestions() {
  const productIds = cartStore.items.filter((item) => !item.bundleId).map((item) => item.product.id)

  if (productIds.length === 0) {
    upsellProducts.value = []
    return
  }

  try {
    const res = await axios.get(route('tenant.cart.upsell'), { params: { product_ids: productIds } })
    upsellProducts.value = res.data.products ?? []
  } catch {
    // Suggestions are a nice-to-have — never let a failure disrupt the cart.
    upsellProducts.value = []
  }
}

watch(
  () => cartStore.items.map((i) => i.key).join(','),
  () => {
    clearTimeout(upsellFetchTimeout)
    upsellFetchTimeout = setTimeout(fetchUpsellSuggestions, 400)
  },
  { immediate: true },
)

function addUpsellProduct(p) {
  cartStore.add({ product: p, quantity: 1 })
  upsellProducts.value = upsellProducts.value.filter((item) => item.id !== p.id)
}
</script>

<style scoped>
.sidebar-enter-active,
.sidebar-leave-active {
  transition: all 0.3s ease;
}
.sidebar-enter-from,
.sidebar-leave-to {
  transform: translateX(100%);
}
</style>
