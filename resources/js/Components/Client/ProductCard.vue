<template>
  <div class="product-card group bg-white rounded-3xl border border-gray-100 overflow-hidden transition-all">
    <!-- Image -->
    <Link
      :href="route('tenant.product.show', product.slug)"
      class="block aspect-square bg-gray-50 overflow-hidden relative"
    >
      <img
        v-if="primaryImage"
        :src="'/storage/' + primaryImage"
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-5xl text-gray-200">
        {{ product.type === 'digital' ? '📥' : '📦' }}
      </div>
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex flex-col gap-1">
        <span v-if="topLabel" :class="topLabel.badgeClass" class="text-xs font-bold px-2 py-0.5 rounded-full shadow">{{
          topLabel.label
        }}</span>
        <span v-if="discountPercent > 0" class="text-xs font-bold bg-red-500 text-white px-2 py-0.5 rounded-full shadow"
          >-{{ discountPercent }}%</span
        >
        <span
          v-if="product.is_featured && !topLabel"
          class="text-xs font-bold bg-yellow-400 text-gray-900 px-2 py-0.5 rounded-full shadow"
          >{{ t('common.featured') }}</span
        >
      </div>
    </Link>

    <!-- Info -->
    <div class="p-4 flex flex-col gap-1.5">
      <div class="flex items-center justify-between gap-2">
        <span
          v-if="product.category?.name"
          class="text-[11px] font-bold uppercase tracking-wide text-gray-400 truncate"
          >{{ product.category.name }}</span
        >
        <span v-else class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{
          product.type === 'digital' ? t('client.cartsidebar.digital') : t('common.product')
        }}</span>
        <StarRating v-if="product.reviews_count > 0" :rating="parseFloat(product.reviews_avg)" :compact="true" />
      </div>

      <Link
        :href="route('tenant.product.show', product.slug)"
        class="font-display font-bold text-gray-900 hover:theme-primary transition line-clamp-2 text-sm leading-snug"
      >
        {{ product.name }}
      </Link>

      <div class="flex items-baseline gap-2 mt-0.5">
        <span class="text-money font-bold text-gray-900">{{ priceLabel }}</span>
        <span
          v-if="!hasVariantRange && product.compare_price && product.compare_price > product.price"
          class="text-money text-sm text-gray-400 line-through"
        >
          {{ formatPrice(product.compare_price) }}
        </span>
      </div>

      <!-- Urgency: the same tenant-configured signals (Settings > urgency CTAs) already
           used on the product page, brought to the grid card too so the whole
           storefront reflects them, not just the detail view. -->
      <p v-if="lowStock" class="text-xs font-semibold text-red-600 flex items-center gap-1">
        <i class="fa-solid fa-fire"></i> {{ t('common.only_a_left', { a: product.stock_quantity }) }}
      </p>
      <p v-else-if="soldToday" class="text-xs font-medium text-emerald-600 flex items-center gap-1">
        <i class="fa-solid fa-bolt"></i> {{ soldToday }} sprzedanych dzisiaj
      </p>

      <!-- Tags -->
      <div v-if="productTags.length" class="flex flex-wrap gap-1">
        <span
          v-for="tag in productTags"
          :key="tag.value"
          :class="tag.class"
          class="text-xs px-2 py-0.5 rounded-full font-medium"
        >
          {{ tag.label }}
        </span>
      </div>

      <button @click.prevent="addToCart" class="btn btn--dark btn--compact full mt-1 w-full">
        {{ product.variants?.length ? t('common.choose_options') : product.type === 'digital' ? '📥 Kup' : '🛒 Dodaj' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import StarRating from './StarRating.vue'
import { useCartStore } from '@/Stores/cartStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
})

const cart = useCartStore()
const page = usePage()
const urgency = computed(() => page.props.tenant?.urgency ?? {})

const lowStock = computed(
  () =>
    urgency.value.stock_enabled &&
    props.product.track_stock &&
    props.product.stock_quantity > 0 &&
    props.product.stock_quantity <= urgency.value.stock_threshold,
)

const soldToday = ref(0)
onMounted(() => {
  const u = urgency.value
  if (u.sold_enabled && !lowStock.value) {
    soldToday.value = Math.floor(Math.random() * ((u.sold_max ?? 84) - (u.sold_min ?? 12) + 1)) + (u.sold_min ?? 12)
  }
})

const primaryImage = computed(() => props.product.images?.[0]?.path ?? props.product.image ?? null)

const discountPercent = computed(() => {
  if (!props.product.compare_price || props.product.compare_price <= props.product.price) return 0
  return Math.round(((props.product.compare_price - props.product.price) / props.product.compare_price) * 100)
})

const minPrice = computed(() => {
  const variants = props.product.variants
  if (!variants?.length) return parseFloat(props.product.price ?? 0)
  return Math.min(...variants.map((v) => parseFloat(v.price)))
})

const maxPrice = computed(() => {
  const variants = props.product.variants
  if (!variants?.length) return parseFloat(props.product.price ?? 0)
  return Math.max(...variants.map((v) => parseFloat(v.price)))
})

const hasVariantRange = computed(() => minPrice.value !== maxPrice.value)

const priceLabel = computed(() => {
  const fmt = (p) => new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(p)
  if (hasVariantRange.value) return fmt(minPrice.value) + ' – ' + fmt(maxPrice.value)
  return fmt(minPrice.value)
})

const TAG_STYLES = {
  new: {
    value: 'new',
    label: t('common.new'),
    class: 'bg-blue-100 text-blue-800',
    badgeClass: 'bg-blue-500 text-white',
  },
  bestseller: {
    value: 'bestseller',
    label: 'Bestseller',
    class: 'bg-orange-100 text-orange-800',
    badgeClass: 'bg-orange-500 text-white',
  },
  sale: {
    value: 'sale',
    label: t('common.sale'),
    class: 'bg-red-100 text-red-800',
    badgeClass: 'bg-red-500 text-white',
  },
  hot: { value: 'hot', label: 'Hit', class: 'bg-rose-100 text-rose-800', badgeClass: 'bg-rose-600 text-white' },
  limited: {
    value: 'limited',
    label: t('client.productcard.limited'),
    class: 'bg-purple-100 text-purple-800',
    badgeClass: 'bg-purple-600 text-white',
  },
  free_ship: {
    value: 'free_ship',
    label: t('common.free_delivery'),
    class: 'bg-green-100 text-green-800',
    badgeClass: 'bg-green-600 text-white',
  },
  handmade: {
    value: 'handmade',
    label: 'Handmade',
    class: 'bg-amber-100 text-amber-800',
    badgeClass: 'bg-amber-600 text-white',
  },
  premium: {
    value: 'premium',
    label: 'Premium',
    class: 'bg-gray-100 text-gray-800',
    badgeClass: 'bg-gray-800 text-white',
  },
}

// Which badge wins the one slot on the image.
const BADGE_PRIORITY = ['sale', 'bestseller', 'hot', 'new', 'limited', 'free_ship', 'premium', 'handmade']

const productTags = computed(() => (props.product.tags || []).map((t) => TAG_STYLES[t]).filter(Boolean))

const topLabel = computed(() => {
  const tags = props.product.tags || []
  for (const key of BADGE_PRIORITY) {
    if (tags.includes(key)) return TAG_STYLES[key]
  }
  return null
})

const { formatPrice } = useCurrency()

function addToCart() {
  // Products with variants (size/color) need the picker on the product page —
  // adding blind here would place an order with no size/color recorded.
  if (props.product.variants?.length) {
    router.visit(route('tenant.product.show', props.product.slug))
    return
  }
  cart.add({ product: props.product, variant: null, quantity: 1 })
}
</script>
