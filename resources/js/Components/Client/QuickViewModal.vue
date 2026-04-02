<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="flex flex-col md:flex-row">
            <!-- Image -->
            <div class="md:w-1/2 bg-gray-50 flex-shrink-0">
              <div class="aspect-square rounded-l-2xl overflow-hidden">
                <img
                  v-if="primaryImage"
                  :src="'/storage/' + primaryImage"
                  :alt="product.name"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-6xl text-gray-200">
                  {{ product.type === 'digital' ? '📥' : '📦' }}
                </div>
              </div>
            </div>

            <!-- Info -->
            <div class="md:w-1/2 p-6 flex flex-col gap-4">
              <!-- Close button -->
              <div class="flex justify-between items-start">
                <h2 class="text-xl font-bold text-gray-900">{{ product.name }}</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none ml-2">
                  &times;
                </button>
              </div>

              <!-- Price -->
              <div class="flex items-baseline gap-3">
                <span class="text-2xl font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                <span
                  v-if="product.compare_price && product.compare_price > product.price"
                  class="text-base text-gray-400 line-through"
                >
                  {{ formatPrice(product.compare_price) }}
                </span>
              </div>

              <!-- Excerpt -->
              <p v-if="product.excerpt || product.description" class="text-sm text-gray-600 line-clamp-3">
                {{ product.excerpt || product.description }}
              </p>

              <!-- Variant select -->
              <div v-if="product.variants?.length">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('client.quickviewmodal.variant')
                }}</label>
                <select
                  v-model="selectedVariant"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:theme-primary-ring"
                >
                  <option :value="null">{{ t('client.quickviewmodal.choose_a_variant') }}</option>
                  <option v-for="v in product.variants" :key="v.id" :value="v">
                    {{ v.name }} ({{ formatPrice(v.price ?? product.price) }})
                  </option>
                </select>
              </div>

              <!-- Quantity -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.quantity') }}</label>
                <div class="flex items-center border border-gray-300 rounded-lg w-32">
                  <button
                    @click="qty = Math.max(1, qty - 1)"
                    class="px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-l-lg"
                  >
                    -
                  </button>
                  <input
                    v-model.number="qty"
                    type="number"
                    min="1"
                    class="w-full text-center py-2 border-0 focus:ring-0 text-sm"
                  />
                  <button @click="qty++" class="px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-r-lg">+</button>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-col gap-2 mt-auto">
                <button
                  @click="handleAddToCart"
                  :disabled="!canAddToCart"
                  class="w-full theme-primary-bg text-white py-3 rounded-xl font-semibold hover:opacity-90 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{
                    product.variants?.length && !selectedVariant
                      ? t('client.productmodal.choose_a_variant')
                      : t('common.add_to_cart')
                  }}
                </button>
                <Link
                  :href="route('tenant.product.show', product.slug)"
                  class="w-full text-center py-2 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                  {{ t('client.quickviewmodal.go_to_the_product') }}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'add-to-cart'])

const qty = ref(1)
const selectedVariant = ref(null)

const primaryImage = computed(() => {
  return props.product.images?.[0]?.path ?? props.product.image ?? null
})

const { formatPrice } = useCurrency()

const canAddToCart = computed(() => !props.product.variants?.length || !!selectedVariant.value)

function handleAddToCart() {
  if (!canAddToCart.value) return
  emit('add-to-cart', {
    product: props.product,
    variant: selectedVariant.value,
    quantity: qty.value,
  })
  emit('close')
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
