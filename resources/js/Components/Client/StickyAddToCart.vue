<template>
  <Transition name="sticky-slide">
    <div
      v-if="visible"
      class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white border-t border-gray-200 shadow-2xl px-4 py-3 safe-area-bottom"
    >
      <div class="flex items-center gap-3 max-w-lg mx-auto">
        <!-- Product info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900 truncate">{{ shortName }}</p>
          <p class="text-sm font-bold theme-primary">{{ formattedPrice }}</p>
        </div>

        <!-- Add to cart button -->
        <button
          type="button"
          @click="handleAddToCart"
          :disabled="!selectedVariant && hasVariants"
          class="shrink-0 theme-primary-bg hover:opacity-90 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors"
        >
          {{ !selectedVariant && hasVariants ? t('client.productmodal.choose_a_variant') : t('common.add_to_cart') }}
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
  selectedVariant: { type: Object, default: null },
  quantity: { type: Number, default: 1 },
  // Pass a ref or CSS selector of the original add-to-cart button to watch its visibility
  observeSelector: { type: String, default: '[data-add-to-cart]' },
})

const emit = defineEmits(['add-to-cart'])

const visible = ref(false)
let observer = null
let scrollHandler = null

const hasVariants = computed(() => {
  return props.product?.variants?.length > 0
})

const shortName = computed(() => {
  const name = props.product?.name ?? ''
  return name.length > 35 ? name.substring(0, 32) + '…' : name
})

const effectivePrice = computed(() => {
  if (props.selectedVariant?.price != null) return parseFloat(props.selectedVariant.price)
  return parseFloat(props.product?.price ?? 0)
})

const { formatPrice } = useCurrency()
const formattedPrice = computed(() => formatPrice(effectivePrice.value))

const handleAddToCart = () => {
  emit('add-to-cart', {
    product: props.product,
    variant: props.selectedVariant,
    quantity: props.quantity,
  })
}

onMounted(() => {
  // Try IntersectionObserver first
  const target = document.querySelector(props.observeSelector)
  if (target && 'IntersectionObserver' in window) {
    observer = new IntersectionObserver(
      ([entry]) => {
        visible.value = !entry.isIntersecting
      },
      { threshold: 0.1 },
    )
    observer.observe(target)
    return
  }

  // Fallback: scroll-based detection
  scrollHandler = () => {
    visible.value = window.scrollY > 300
  }
  window.addEventListener('scroll', scrollHandler, { passive: true })
})

onUnmounted(() => {
  if (observer) observer.disconnect()
  if (scrollHandler) window.removeEventListener('scroll', scrollHandler)
})
</script>

<style scoped>
.sticky-slide-enter-active,
.sticky-slide-leave-active {
  transition:
    transform 0.25s ease,
    opacity 0.25s ease;
}
.sticky-slide-enter-from,
.sticky-slide-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.safe-area-bottom {
  padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}
</style>
