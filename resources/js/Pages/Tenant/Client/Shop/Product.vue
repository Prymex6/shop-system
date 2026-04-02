<template>
  <ClientLayout :title="product.name" :description="product.short_description || product.description">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <Link :href="route('tenant.shop')" class="hover:text-indigo-600">{{ t('common.shop') }}</Link>
        <span>/</span>
        <Link
          v-if="product.category"
          :href="route('tenant.category.show', product.category.slug)"
          class="hover:text-indigo-600"
        >
          {{ product.category.name }}
        </Link>
        <span v-if="product.category">/</span>
        <span class="text-gray-900 font-medium">{{ product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Gallery -->
        <div>
          <div class="aspect-square rounded-3xl overflow-hidden bg-gray-100 mb-3 shadow-sm">
            <img
              v-if="activeImage || product.image"
              :src="activeImage ? '/storage/' + activeImage : '/storage/' + product.image"
              :alt="product.name"
              :width="activeImageDims.width || undefined"
              :height="activeImageDims.height || undefined"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-7xl text-gray-300">
              {{ product.type === 'digital' ? '📥' : '📦' }}
            </div>
          </div>
          <div v-if="product.images?.length" class="flex gap-2 overflow-x-auto pb-2">
            <button
              v-for="img in product.images"
              :key="img.id"
              @click="activeImage = img.path"
              class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition"
              :class="activeImage === img.path ? 'border-indigo-500' : 'border-transparent'"
            >
              <!-- thumbnail_path is a small, separately-generated file (ProductImageProcessor)
                   — previously this served the same full-size original as the hero image,
                   four to five times over, to fill a 64x64px button. -->
              <img
                :src="thumbnailSrc(img)"
                :alt="img.alt"
                width="64"
                height="64"
                loading="lazy"
                class="w-full h-full object-cover"
              />
            </button>
          </div>
        </div>

        <!-- Product Info -->
        <div class="flex flex-col gap-6">
          <!-- Type badge -->
          <div class="flex items-center gap-2">
            <span
              v-if="product.type === 'digital'"
              class="text-xs font-semibold bg-purple-100 text-purple-700 px-2 py-1 rounded-full"
            >
              {{ t('client.shop.product.digital_product') }}
            </span>
            <span
              v-if="product.type === 'physical'"
              class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-1 rounded-full"
            >
              {{ t('client.shop.product.physical_product') }}
            </span>
            <span
              v-if="!product.is_in_stock"
              class="text-xs font-semibold bg-red-100 text-red-700 px-2 py-1 rounded-full"
            >
              {{ t('client.shop.product.out_of_stock') }}
            </span>
          </div>

          <h1 class="font-display product-single__title text-3xl md:text-4xl font-bold text-gray-900">
            {{ product.name }}
          </h1>

          <!-- Product labels / tags -->
          <div v-if="productLabels.length" class="flex flex-wrap gap-1.5">
            <span
              v-for="label in productLabels"
              :key="label.value"
              :class="label.class"
              class="text-xs font-semibold px-2.5 py-1 rounded-full"
            >
              {{ label.label }}
            </span>
          </div>

          <!-- Rating -->
          <div v-if="product.reviews_count > 0" class="flex items-center gap-2">
            <StarRating :rating="parseFloat(product.reviews_avg)" />
            <span class="text-sm text-gray-500">({{ product.reviews_count }} recenzji)</span>
          </div>

          <!-- Price -->
          <div class="flex items-baseline gap-3">
            <span class="text-money text-3xl font-bold text-gray-900">{{
              formatPrice(selectedVariant?.price ?? product.price)
            }}</span>
            <span
              v-if="product.compare_price && product.compare_price > product.price"
              class="text-xl text-gray-400 line-through"
            >
              {{ formatPrice(product.compare_price) }}
            </span>
            <span
              v-if="product.compare_price && product.compare_price > product.price"
              class="text-sm font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full"
            >
              -{{ discountPercent }}%
            </span>
          </div>

          <!-- Short description -->
          <p v-if="product.short_description" class="text-gray-600">{{ product.short_description }}</p>

          <!-- ── URGENCY CTA BLOCK ─────────────────────────────── -->
          <div v-if="showAnyUrgency" class="space-y-2">
            <!-- 1. Countdown: flash sale -->
            <div
              v-if="urgency.countdown_enabled && flashSaleEndsAt"
              class="flex items-center gap-3 bg-red-50 border border-red-100 rounded-xl px-4 py-3"
            >
              <i class="fa-solid fa-fire text-red-500 text-lg shrink-0"></i>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-0.5">
                  {{ t('client.shop.product.flash_offer_ends_in') }}
                </p>
                <p class="text-xl font-mono font-bold text-red-600 tabular-nums">{{ flashCountdown }}</p>
              </div>
            </div>

            <!-- 2. Countdown: active global promotion -->
            <div
              v-else-if="urgency.countdown_enabled && promotionEndsAt"
              class="flex items-center gap-3 bg-orange-50 border border-orange-100 rounded-xl px-4 py-3"
            >
              <i class="fa-solid fa-tag text-orange-500 text-lg shrink-0"></i>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-orange-700 uppercase tracking-wide mb-0.5">
                  {{ t('client.shop.product.the_promotion_ends_in') }}
                </p>
                <p class="text-xl font-mono font-bold text-orange-600 tabular-nums">{{ promoCountdown }}</p>
              </div>
            </div>

            <!-- 3. Low stock -->
            <div
              v-if="
                urgency.stock_enabled &&
                product.track_stock &&
                product.stock_quantity > 0 &&
                product.stock_quantity <= urgency.stock_threshold
              "
              class="flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2.5 text-sm"
            >
              <i class="fa-solid fa-triangle-exclamation text-amber-500 shrink-0"></i>
              <span class="text-amber-800 font-medium"
                >{{ t('client.shop.product.only') }} <strong>{{ product.stock_quantity }}</strong>
                {{ t('client.shop.product.left') }}</span
              >
            </div>

            <!-- 4. Viewers -->
            <div
              v-if="urgency.viewers_enabled && viewersCount > 0"
              class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 rounded-xl px-4 py-2.5 border border-gray-100"
            >
              <i class="fa-solid fa-eye text-gray-400 shrink-0"></i>
              <span
                ><strong>{{ viewersCount }}</strong>
                {{ t('client.shop.product.people_are_viewing_this_product_right') }}</span
              >
            </div>

            <!-- 5. Sold recently -->
            <div
              v-if="urgency.sold_enabled && soldCount > 0"
              class="flex items-center gap-2 text-sm text-green-700 bg-green-50 rounded-xl px-4 py-2.5 border border-green-100"
            >
              <i class="fa-solid fa-arrow-trend-up text-green-500 shrink-0"></i>
              <span
                ><strong>{{ soldCount }}</strong> {{ t('client.shop.product.people_bought_this_in_the_last') }}</span
              >
            </div>

            <!-- 6. Delivery deadline -->
            <div
              v-if="urgency.delivery_enabled && !deliveryDeadlinePassed"
              class="flex items-center gap-2 text-sm bg-blue-50 border border-blue-100 rounded-xl px-4 py-2.5"
            >
              <i class="fa-solid fa-truck-fast text-blue-500 shrink-0"></i>
              <span class="text-blue-800"
                >{{ t('client.shop.product.order_by') }} <strong>{{ urgency.delivery_cutoff }}</strong> —
                <strong>{{ t('client.shop.product.and_it_ships_today') }}</strong></span
              >
              <span class="ml-auto font-mono text-blue-600 font-semibold tabular-nums text-xs">{{
                deliveryCountdown
              }}</span>
            </div>
          </div>
          <!-- ── END URGENCY ──────────────────────────────────── -->

          <!-- Variant Selector -->
          <VariantSelector
            v-if="product.variants?.length"
            :variants="product.variants"
            :attributes="attributes"
            @change="onVariantChange"
          />

          <!-- Quantity -->
          <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">{{ t('common.quantity_2') }}</label>
            <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
              <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-2 hover:bg-gray-50 transition">−</button>
              <span class="px-4 py-2 font-semibold min-w-[3rem] text-center">{{ qty }}</span>
              <button @click="qty++" class="px-3 py-2 hover:bg-gray-50 transition">+</button>
            </div>
          </div>

          <!-- Add to cart -->
          <div class="flex gap-3">
            <button
              @click="addToCart"
              :disabled="!canAddToCart"
              class="btn btn--primary full flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{
                product.variants?.length && !selectedVariant
                  ? t('client.productmodal.choose_a_variant')
                  : product.type === 'digital'
                    ? t('client.shop.product.buy_now_digital')
                    : t('client.shop.product.add_to_cart_button')
              }}
            </button>
            <button
              @click="toggleWishlist"
              class="p-3 border border-gray-200 rounded-xl hover:bg-red-50 hover:border-red-200 transition"
              :title="inWishlist ? t('client.wishlist.remove_from_the_wishlist') : t('common.add_to_the_wishlist')"
            >
              <span class="text-xl">{{ inWishlist ? '❤️' : '🤍' }}</span>
            </button>
          </div>

          <!-- Stock status -->
          <p
            v-if="product.track_stock && product.stock_quantity <= 5 && product.stock_quantity > 0"
            class="text-sm text-orange-600 font-medium"
          >
            ⚠️ {{ t('common.only_a_left_long', { a: product.stock_quantity }) }}
          </p>

          <!-- Digital info -->
          <div
            v-if="product.type === 'digital'"
            class="bg-purple-50 border border-purple-100 rounded-xl p-4 text-sm text-purple-800 space-y-1"
          >
            <p class="font-semibold">{{ t('client.shop.product.about_the_digital_file') }}</p>
            <p v-if="product.download_limit">{{ t('common.download_limit_a', { a: product.download_limit }) }}</p>
            <p v-else>{{ t('client.shop.product.unlimited_downloads') }}</p>
            <p v-if="product.download_expires_hours">
              ⏰ Link aktywny przez {{ product.download_expires_hours }} godzin
            </p>
            <p v-else>{{ t('client.shop.product.link_with_no_time_limit') }}</p>
            <p>{{ t('client.shop.product.available_the_moment_you_pay') }}</p>
          </div>

          <!-- Shipping info for physical -->
          <div v-if="product.type === 'physical'" class="text-sm text-gray-600 space-y-1">
            <p>{{ t('client.shop.product.this_product_can_be_shipped') }}</p>
            <p v-if="product.weight">⚖️ Waga: {{ product.weight }} kg</p>
          </div>
        </div>
      </div>

      <!-- Description + Features tabs -->
      <div v-if="product.description || product.features?.length" class="mt-12">
        <!-- Tab switcher -->
        <div class="flex gap-1 border-b border-gray-200 mb-6" role="tablist">
          <button
            v-if="product.description"
            id="tab-description"
            role="tab"
            :aria-selected="detailTab === 'description'"
            aria-controls="tabpanel-description"
            @click="detailTab = 'description'"
            class="px-5 py-2.5 text-sm font-medium border-b-2 transition -mb-px"
            :class="
              detailTab === 'description'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            "
          >
            {{ t('common.product_description') }}
          </button>
          <button
            v-if="product.features?.length"
            id="tab-features"
            role="tab"
            :aria-selected="detailTab === 'features'"
            aria-controls="tabpanel-features"
            @click="detailTab = 'features'"
            class="px-5 py-2.5 text-sm font-medium border-b-2 transition -mb-px"
            :class="
              detailTab === 'features'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            "
          >
            {{ t('client.shop.product.specification') }}
          </button>
        </div>

        <div
          v-if="detailTab === 'description'"
          id="tabpanel-description"
          role="tabpanel"
          aria-labelledby="tab-description"
          class="prose max-w-none text-gray-700"
          v-html="product.description"
        />

        <div
          v-if="detailTab === 'features' && product.features?.length"
          id="tabpanel-features"
          role="tabpanel"
          aria-labelledby="tab-features"
        >
          <table class="w-full text-sm border-collapse">
            <tbody>
              <tr v-for="(f, i) in product.features" :key="f.id ?? i" :class="i % 2 === 0 ? 'bg-gray-50' : 'bg-white'">
                <td class="px-4 py-3 font-medium text-gray-700 w-1/3 border border-gray-100">{{ f.name }}</td>
                <td class="px-4 py-3 text-gray-900 border border-gray-100">{{ f.value }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reviews -->
      <ProductReviews :reviews="product.reviews" :product-id="product.id" :order-id="verifiedOrderId" class="mt-12" />

      <!-- Frequently bought together -->
      <section v-if="frequentlyBoughtWith.length" class="mt-16">
        <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-6">
          {{ t('client.shop.product.frequently_bought_together') }}
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <ProductCard v-for="p in frequentlyBoughtWith" :key="p.id" :product="p" />
        </div>
      </section>

      <!-- Related -->
      <section v-if="related.length" class="mt-16">
        <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-6">
          {{ t('client.shop.product.similar_products') }}
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <ProductCard v-for="p in related" :key="p.id" :product="p" />
        </div>
      </section>

      <!-- Guarantee badges (shared with Homepage Builder's "guarantee_badges" block) -->
      <section v-if="guaranteeBlock" class="mt-16 bg-gray-50 rounded-2xl border border-gray-100 py-8 px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div
            v-for="badge in guaranteeBlock.settings?.badges ?? []"
            :key="badge.label"
            class="flex items-center gap-3"
          >
            <div
              class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
              :style="{ backgroundColor: badge.bg }"
            >
              <i :class="[badge.icon, 'text-lg']" :style="{ color: badge.color }"></i>
            </div>
            <div>
              <div class="text-sm font-bold text-gray-900 leading-tight">{{ badge.label }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ badge.sub }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Testimonials (shared with Homepage Builder's "testimonials" block) -->
      <section v-if="testimonialsBlock" class="mt-16">
        <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-6">
          {{ testimonialsBlock.settings?.heading || t('common.customer_reviews') }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="n in [1, 2, 3]"
            :key="n"
            v-show="testimonialsBlock.settings?.[`item${n}_text`]"
            class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center"
          >
            <div
              class="w-14 h-14 rounded-full theme-primary-bg text-white font-bold text-lg flex items-center justify-center mx-auto mb-3"
            >
              {{ (testimonialsBlock.settings?.[`item${n}_author`] || '?').trim().charAt(0).toUpperCase() }}
            </div>
            <div class="flex gap-0.5 mb-3 justify-center">
              <i
                v-for="s in 5"
                :key="s"
                class="fa-solid fa-star text-sm"
                :class="
                  s <= (parseInt(testimonialsBlock.settings?.[`item${n}_rating`]) || 5)
                    ? 'text-yellow-400'
                    : 'text-gray-200'
                "
              ></i>
            </div>
            <p class="text-gray-700 text-sm leading-relaxed mb-4">
              "{{ testimonialsBlock.settings?.[`item${n}_text`] }}"
            </p>
            <p class="text-sm font-bold text-gray-900">{{ testimonialsBlock.settings?.[`item${n}_author`] }}</p>
          </div>
        </div>
      </section>

      <!-- FAQ (shared with Homepage Builder's "faq" block) -->
      <section v-if="faqBlock" class="mt-16 max-w-4xl">
        <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-6 tracking-tight">
          {{ faqBlock.settings?.heading || t('common.frequently_asked_questions') }}
        </h2>
        <div class="divide-y divide-gray-200 bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
          <template v-for="n in [1, 2, 3, 4]" :key="n">
            <div v-if="faqBlock.settings?.[`q${n}`]" class="px-6">
              <button
                type="button"
                class="w-full flex items-center justify-between gap-4 py-5 text-left"
                @click="toggleFaq(n)"
              >
                <span class="font-bold text-gray-900 text-base">{{ faqBlock.settings[`q${n}`] }}</span>
                <i
                  class="fa-solid fa-chevron-down text-gray-400 text-sm transition-transform shrink-0"
                  :class="{ 'rotate-180': openFaqs.has(n) }"
                ></i>
              </button>
              <div v-show="openFaqs.has(n)" class="pb-5 text-[0.9375rem] text-gray-600 leading-relaxed">
                {{ faqBlock.settings[`a${n}`] }}
              </div>
            </div>
          </template>
        </div>
      </section>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import ProductCard from '@/Components/Client/ProductCard.vue'
import StarRating from '@/Components/Client/StarRating.vue'
import VariantSelector from '@/Components/Client/VariantSelector.vue'
import ProductReviews from '@/Components/Client/ProductReviews.vue'
import { useCartStore } from '@/Stores/cartStore'
import { useWishlistStore } from '@/Stores/wishlistStore'
import { useRecentlyViewedStore } from '@/Stores/recentlyViewedStore'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
  related: { type: Array, default: () => [] },
  frequentlyBoughtWith: { type: Array, default: () => [] },
  attributes: { type: Array, default: () => [] },
  flashSaleEndsAt: { type: String, default: null },
  verifiedOrderId: { type: Number, default: null },
  siteBlocks: { type: Array, default: () => [] },
})

const guaranteeBlock = computed(() => props.siteBlocks.find((b) => b.id === 'guarantee_badges') ?? null)
const testimonialsBlock = computed(() => props.siteBlocks.find((b) => b.id === 'testimonials') ?? null)
const faqBlock = computed(() => props.siteBlocks.find((b) => b.id === 'faq') ?? null)

const openFaqs = ref(new Set())
function toggleFaq(n) {
  const next = new Set(openFaqs.value)
  if (next.has(n)) {
    next.delete(n)
  } else {
    next.add(n)
  }
  openFaqs.value = next
}

const page = usePage()
const cart = useCartStore()
const wishlist = useWishlistStore()
const recentlyViewed = useRecentlyViewedStore()

// ── Urgency config from shared props ──────────────────────
const urgency = computed(
  () =>
    page.props.tenant?.urgency ?? {
      countdown_enabled: false,
      stock_enabled: false,
      stock_threshold: 5,
      viewers_enabled: false,
      viewers_min: 5,
      viewers_max: 24,
      sold_enabled: false,
      sold_min: 12,
      sold_max: 84,
      delivery_enabled: false,
      delivery_cutoff: '14:00',
    },
)

const flashSaleEndsAt = computed(() => (props.flashSaleEndsAt ? new Date(props.flashSaleEndsAt) : null))
const promotionEndsAt = computed(() =>
  page.props.active_promotion?.ends_at ? new Date(page.props.active_promotion.ends_at) : null,
)

// Social proof — random stable values generated once on mount
const viewersCount = ref(0)
const soldCount = ref(0)

// Countdown strings
const flashCountdown = ref('')
const promoCountdown = ref('')
const deliveryCountdown = ref('')

const deliveryDeadlinePassed = computed(() => {
  if (!urgency.value.delivery_cutoff) return true
  const [h, m] = urgency.value.delivery_cutoff.split(':').map(Number)
  const cutoff = new Date()
  cutoff.setHours(h, m, 0, 0)
  return new Date() >= cutoff
})

function formatCountdown(targetDate) {
  if (!targetDate) return ''
  const diff = targetDate - new Date()
  if (diff <= 0) return '00:00:00'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  const s = Math.floor((diff % 60_000) / 1_000)
  if (h >= 24) {
    const days = Math.floor(h / 24)
    return `${days}d ${String(h % 24).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  }
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function formatDeliveryCountdown() {
  const [h, m] = (urgency.value.delivery_cutoff ?? '14:00').split(':').map(Number)
  const cutoff = new Date()
  cutoff.setHours(h, m, 0, 0)
  const diff = cutoff - new Date()
  if (diff <= 0) return ''
  const hh = Math.floor(diff / 3_600_000)
  const mm = Math.floor((diff % 3_600_000) / 60_000)
  const ss = Math.floor((diff % 60_000) / 1_000)
  return `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}:${String(ss).padStart(2, '0')}`
}

function tickCountdowns() {
  if (flashSaleEndsAt.value) flashCountdown.value = formatCountdown(flashSaleEndsAt.value)
  if (promotionEndsAt.value) promoCountdown.value = formatCountdown(promotionEndsAt.value)
  if (urgency.value.delivery_enabled) deliveryCountdown.value = formatDeliveryCountdown()
}

let timer = null

const showAnyUrgency = computed(() => {
  const u = urgency.value
  return (
    (u.countdown_enabled && (flashSaleEndsAt.value || promotionEndsAt.value)) ||
    (u.stock_enabled &&
      props.product.track_stock &&
      props.product.stock_quantity > 0 &&
      props.product.stock_quantity <= u.stock_threshold) ||
    (u.viewers_enabled && viewersCount.value > 0) ||
    (u.sold_enabled && soldCount.value > 0) ||
    (u.delivery_enabled && !deliveryDeadlinePassed.value)
  )
})

onMounted(() => {
  recentlyViewed.add(props.product)

  const u = urgency.value
  if (u.viewers_enabled) {
    viewersCount.value = Math.floor(Math.random() * (u.viewers_max - u.viewers_min + 1)) + u.viewers_min
  }
  if (u.sold_enabled) {
    soldCount.value = Math.floor(Math.random() * (u.sold_max - u.sold_min + 1)) + u.sold_min
  }

  tickCountdowns()
  timer = setInterval(tickCountdowns, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

// ── Product logic ──────────────────────────────────────────
const qty = ref(1)
const selectedVariant = ref(null)
const activeImage = ref(props.product.images?.[0]?.path ?? null)
const detailTab = ref(props.product.description ? 'description' : 'features')

// width/height hints for the hero <img> (reduces layout shift while it loads) —
// falls back to no explicit size for a variant-swapped image path, which
// doesn't carry stored dimensions.
const activeImageDims = computed(() => {
  if (!activeImage.value) {
    return { width: props.product.image_width, height: props.product.image_height }
  }
  const match = props.product.images?.find((img) => img.path === activeImage.value)
  return { width: match?.width ?? null, height: match?.height ?? null }
})

const thumbnailSrc = (img) => {
  const src = img.thumbnail_path || img.path
  return src?.startsWith('/') || src?.startsWith('http') ? src : '/storage/' + src
}

const inWishlist = computed(() => wishlist.has(props.product.id))

const LABEL_STYLES = {
  new: { value: 'new', label: t('common.new'), class: 'bg-blue-100 text-blue-800' },
  bestseller: { value: 'bestseller', label: 'Bestseller', class: 'bg-orange-100 text-orange-800' },
  sale: { value: 'sale', label: t('common.sale'), class: 'bg-red-100 text-red-800' },
  hot: { value: 'hot', label: 'Hit', class: 'bg-rose-100 text-rose-800' },
  limited: { value: 'limited', label: t('client.productcard.limited'), class: 'bg-purple-100 text-purple-800' },
  free_ship: { value: 'free_ship', label: t('common.free_delivery'), class: 'bg-green-100 text-green-800' },
  handmade: { value: 'handmade', label: 'Handmade', class: 'bg-amber-100 text-amber-800' },
  premium: { value: 'premium', label: 'Premium', class: 'bg-gray-200 text-gray-800' },
}

const productLabels = computed(() => (props.product.tags || []).map((t) => LABEL_STYLES[t]).filter(Boolean))

const discountPercent = computed(() => {
  if (!props.product.compare_price) return 0
  return Math.round(((props.product.compare_price - props.product.price) / props.product.compare_price) * 100)
})

const { formatPrice } = useCurrency()

function onVariantChange(variant) {
  selectedVariant.value = variant
  if (variant?.image) activeImage.value = variant.image
}

const canAddToCart = computed(
  () =>
    props.product.is_published &&
    (!props.product.variants?.length || (selectedVariant.value && selectedVariant.value.is_active !== false)),
)

function addToCart() {
  if (!canAddToCart.value) return
  cart.add({
    product: props.product,
    variant: selectedVariant.value,
    quantity: qty.value,
  })
}

function toggleWishlist() {
  wishlist.toggle(props.product.id)
}
</script>
