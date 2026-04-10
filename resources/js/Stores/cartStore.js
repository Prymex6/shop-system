import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const isCartOpen = ref(false)
  const appliedDiscount = ref(null) // { code, type, value }

  // ─── Getters ─────────────────────────────────────────────────────

  const itemCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))

  const subtotal = computed(() => items.value.reduce((sum, item) => sum + item.effectivePrice * item.quantity, 0))

  const discountAmount = computed(() => {
    if (!appliedDiscount.value) return 0
    const { type, value } = appliedDiscount.value
    if (type === 'percent') return subtotal.value * (value / 100)
    return Math.min(value, subtotal.value)
  })

  const total = computed(() => Math.max(0, subtotal.value - discountAmount.value))

  const hasDigitalItems = computed(() => items.value.some((i) => i.product.type === 'digital'))
  const hasPhysicalItems = computed(() => items.value.some((i) => i.product.type === 'physical'))

  // ─── Actions ─────────────────────────────────────────────────────

  function add({ product, variant, quantity = 1 }) {
    const effectivePrice = parseFloat(variant?.price ?? product.price)
    const key = `${product.id}-${variant?.id ?? 'base'}`
    const stockLimit = variant?.stock_quantity ?? product.stock_quantity ?? null

    const existing = items.value.find((i) => i.key === key)
    if (existing) {
      existing.quantity = clampToStock(existing.quantity + quantity, stockLimit)
    } else {
      items.value.push({
        id: Date.now(),
        key,
        product: {
          id: product.id,
          name: product.name,
          slug: product.slug,
          type: product.type,
          image: product.images?.[0]?.path ?? product.image ?? null,
        },
        variant: variant
          ? {
              id: variant.id,
              label: variant.label ?? '',
              price: parseFloat(variant.price),
            }
          : null,
        effectivePrice,
        price: effectivePrice,
        comparePrice: parseFloat(variant?.compare_price ?? product.compare_price ?? 0) || null,
        variantLabel: variant?.label ?? '',
        stockLimit,
        quantity: clampToStock(quantity, stockLimit),
      })
    }

    isCartOpen.value = true
    save()
  }

  // Bundles are cart-represented as a pseudo-product (id prefixed so it can
  // never collide with a real product id) so CartSidebar.vue/Checkout.vue's
  // existing item.product.name/image/type rendering works unchanged. The
  // cart-level type is only a UI hint (does the shipping form need to show,
  // does the "delivered by email" banner show) — the real per-component
  // product_type is what actually drives stock/tax/digital-delivery, decided
  // server-side in CheckoutController::store() from the bundle's real items,
  // not from this simplification.
  function addBundle({ bundle, quantity = 1 }) {
    const key = `bundle-${bundle.id}`
    const hasPhysical = (bundle.items ?? []).some((i) => i.product?.type !== 'digital')

    const existing = items.value.find((i) => i.key === key)
    if (existing) {
      existing.quantity += quantity
    } else {
      items.value.push({
        id: Date.now(),
        key,
        product: {
          id: `bundle-${bundle.id}`,
          name: bundle.name,
          slug: bundle.slug,
          type: hasPhysical ? 'physical' : 'digital',
          image: bundle.image ?? null,
        },
        variant: null,
        bundleId: bundle.id,
        effectivePrice: parseFloat(bundle.price),
        price: parseFloat(bundle.price),
        comparePrice: parseFloat(bundle.compare_price ?? 0) || null,
        variantLabel: '',
        stockLimit: null,
        quantity,
      })
    }

    isCartOpen.value = true
    save()
  }

  function remove(itemId) {
    items.value = items.value.filter((i) => i.id !== itemId)
    save()
  }

  function clampToStock(quantity, stockLimit) {
    if (stockLimit === null || stockLimit === undefined) return quantity
    return Math.min(quantity, Math.max(0, stockLimit))
  }

  function updateQuantity(itemId, quantity) {
    const item = items.value.find((i) => i.id === itemId)
    if (!item) return
    const clamped = clampToStock(quantity, item.stockLimit)
    if (clamped <= 0) {
      remove(itemId)
    } else {
      item.quantity = clamped
      save()
    }
  }

  function clear() {
    items.value = []
    appliedDiscount.value = null
    save()
  }

  function applyDiscount(discount) {
    appliedDiscount.value = discount
  }

  function removeDiscount() {
    appliedDiscount.value = null
  }

  function toggleCart() {
    isCartOpen.value = !isCartOpen.value
  }
  function openCart() {
    isCartOpen.value = true
  }
  function closeCart() {
    isCartOpen.value = false
  }

  function getCheckoutData() {
    return {
      items: items.value.map((item) =>
        item.bundleId
          ? { bundle_id: item.bundleId, quantity: item.quantity }
          : { product_id: item.product.id, variant_id: item.variant?.id ?? null, quantity: item.quantity },
      ),
      discount_code: appliedDiscount.value?.code ?? null,
    }
  }

  function save() {
    localStorage.setItem('shop_cart', JSON.stringify(items.value))
    trackAbandonedCart()
  }

  // Deferred import avoids a hard circular dependency between the two stores;
  // recovery-email tracking should never be able to break add-to-cart itself.
  function trackAbandonedCart() {
    import('@/Stores/cartTrackingStore')
      .then(({ useCartTrackingStore }) => {
        const tracking = useCartTrackingStore()
        if (items.value.length === 0) {
          tracking.track({ items: [] })
          return
        }
        tracking.track({
          items: items.value.map((item) => ({
            product_id: item.product.id,
            variant_id: item.variant?.id ?? null,
            name: item.product.name,
            quantity: item.quantity,
            price: item.price,
          })),
        })
      })
      .catch(() => {})
  }

  function load() {
    const saved = localStorage.getItem('shop_cart')
    if (saved) {
      try {
        items.value = JSON.parse(saved)
      } catch {
        // A cart someone has edited by hand, or one written by an older
        // version, is discarded rather than crashing every page.
      }
    }
  }

  load()

  return {
    items,
    isCartOpen,
    appliedDiscount,
    itemCount,
    subtotal,
    discountAmount,
    total,
    hasDigitalItems,
    hasPhysicalItems,
    add,
    remove,
    updateQuantity,
    clear,
    clearCart: clear,
    applyDiscount,
    removeDiscount,
    toggleCart,
    openCart,
    closeCart,
    getCheckoutData,
  }
})
