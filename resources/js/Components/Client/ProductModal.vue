<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show && product" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close"></div>

          <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Close button -->
            <button
              @click="close"
              class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-600 bg-white rounded-full w-10 h-10 flex items-center justify-center text-xl"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Product Image -->
            <div class="relative aspect-video bg-gray-100">
              <img
                v-if="product.image || product.images?.[0]"
                :src="
                  product.image ??
                  (product.images[0].path?.startsWith('/') || product.images[0].path?.startsWith('http')
                    ? product.images[0].path
                    : '/storage/' + product.images[0].path)
                "
                :alt="product.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-8xl">
                <span v-if="product.category?.icon">{{ product.category.icon }}</span>
                <i v-else class="fa-solid fa-box-open text-gray-400"></i>
              </div>
              <!-- Badges -->
              <div class="absolute top-3 left-3 flex gap-2">
                <span
                  v-if="product.compare_price"
                  class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"
                >
                  OKAZJA
                </span>
                <span
                  v-if="product.type === 'digital'"
                  class="bg-purple-600 text-white text-xs font-bold px-2 py-1 rounded-full"
                >
                  <i class="fa-solid fa-download mr-1"></i>{{ t('client.cartsidebar.digital') }}
                </span>
              </div>
            </div>

            <div class="p-6">
              <!-- Product Name & Description -->
              <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ product.name }}</h2>
              <p v-if="product.short_description" class="text-gray-600 mb-2 text-sm">{{ product.short_description }}</p>
              <p v-if="product.description && !product.short_description" class="text-gray-600 mb-4">
                {{ product.description }}
              </p>

              <!-- Variants Selection -->
              <div v-if="product.variants && product.variants.length" class="mb-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">{{ t('common.choose_a_variant') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <button
                    v-for="variant in product.variants"
                    :key="variant.id"
                    @click="selectedVariant = variant"
                    :disabled="variant.stock_quantity === 0 && !product.allow_backorder"
                    class="border-2 rounded-lg p-3 text-center transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    :class="{
                      'theme-primary-border theme-primary-bg-light': selectedVariant?.id === variant.id,
                      'border-gray-200 hover:theme-primary-border': selectedVariant?.id !== variant.id,
                    }"
                  >
                    <div class="font-semibold text-gray-900 text-sm">{{ variant.label ?? variant.name }}</div>
                    <div class="theme-primary font-bold mt-1">
                      {{ formatPrice(variant.price ?? product.price) }}
                    </div>
                    <div
                      v-if="variant.stock_quantity === 0 && !product.allow_backorder"
                      class="text-xs text-red-500 mt-1"
                    >
                      {{ t('common.none') }}
                    </div>
                  </button>
                </div>
              </div>

              <!-- Notes -->
              <div class="mb-6">
                <label class="block text-base font-semibold text-gray-900 mb-2">
                  {{ t('client.productmodal.notes_optional') }}
                </label>
                <textarea
                  v-model="notes"
                  rows="2"
                  :placeholder="t('client.productmodal.e_g_gift_wrap_special_requests')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:theme-primary-ring focus:border-transparent text-sm"
                ></textarea>
              </div>

              <!-- Quantity -->
              <div class="mb-6">
                <label class="block text-base font-semibold text-gray-900 mb-2">{{ t('common.quantity_2') }}</label>
                <div class="flex items-center space-x-4">
                  <button
                    @click="quantity = Math.max(1, quantity - 1)"
                    class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold"
                  >
                    <i class="fa-solid fa-minus text-gray-700"></i>
                  </button>
                  <span class="text-2xl font-bold w-12 text-center">{{ quantity }}</span>
                  <button
                    @click="quantity++"
                    class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold"
                  >
                    +
                  </button>
                </div>
              </div>

              <!-- Total & Add to Cart -->
              <div class="border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-lg text-gray-600">{{ t('common.total_2') }}</span>
                  <div class="text-right">
                    <div v-if="product.compare_price" class="text-sm text-gray-400 line-through">
                      {{ formatPrice(parseFloat(product.compare_price) * quantity) }}
                    </div>
                    <span class="text-3xl font-bold theme-primary">
                      {{ formatPrice(totalPrice) }}
                    </span>
                  </div>
                </div>

                <button
                  @click="addToCart"
                  :disabled="!canAddToCart"
                  class="w-full theme-primary-bg hover:opacity-90 text-white py-4 rounded-lg text-lg font-semibold transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  <i class="fa-solid fa-cart-plus mr-2"></i>{{ t('common.add_to_cart') }}
                </button>
                <p v-if="product.variants?.length && !selectedVariant" class="text-center text-xs text-red-500 mt-2">
                  {{ t('client.productmodal.choose_a_variant') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useCartStore } from '@/Stores/cartStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  show: Boolean,
  product: {
    type: [Object, null],
    default: null,
  },
})

const emit = defineEmits(['close', 'add'])

const cartStore = useCartStore()

const selectedVariant = ref(null)
const notes = ref('')
const quantity = ref(1)

const effectivePrice = computed(() => {
  if (selectedVariant.value) return parseFloat(selectedVariant.value.price ?? props.product?.price ?? 0)
  return parseFloat(props.product?.price ?? 0)
})

const totalPrice = computed(() => effectivePrice.value * quantity.value)

const canAddToCart = computed(() => {
  if (!props.product) return false
  if (props.product.variants?.length && !selectedVariant.value) return false
  return true
})

const { formatPrice } = useCurrency()

const addToCart = () => {
  if (!canAddToCart.value) return

  cartStore.add({
    product: props.product,
    variant: selectedVariant.value,
    quantity: quantity.value,
  })

  cartStore.openCart()
  close()
}

const close = () => {
  emit('close')
}

watch(
  () => props.show,
  (newVal) => {
    if (newVal && props.product) {
      // Auto-select first available variant
      const firstAvailable =
        props.product.variants?.find((v) => v.stock_quantity > 0 || props.product.allow_backorder) ??
        props.product.variants?.[0] ??
        null
      selectedVariant.value = firstAvailable
      notes.value = ''
      quantity.value = 1
    }
  },
)
</script>
