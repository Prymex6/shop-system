<template>
  <ClientLayout :title="shopName || t('common.shop')">
    <!-- ═══ HERO SLIDER ══════════════════════════════════════════════ -->
    <div class="max-w-7xl mx-auto px-4 lg:px-8 pt-6 pb-10">
      <section
        class="hero-banner-section relative overflow-hidden text-white select-none rounded-3xl"
        style="height: 520px; min-height: 380px"
      >
        <div class="dot-grid-overlay absolute inset-0 pointer-events-none"></div>

        <!-- Slide content -->
        <div class="relative z-10 flex flex-col justify-center h-full px-8 sm:px-12 md:max-w-lg">
          <TransitionGroup name="hero-content">
            <div :key="activeSlide">
              <div
                v-if="heroBadge"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 backdrop-blur-sm mb-5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-medium text-white/85">{{ heroBadge }}</span>
              </div>

              <h1
                class="font-display text-3xl sm:text-4xl md:text-[2.75rem] font-bold mb-3 leading-[1.1] tracking-tight"
              >
                {{ heroSlides[activeSlide]?.title }}
              </h1>
              <p class="text-sm sm:text-base mb-7 text-white/85 leading-relaxed">
                {{ heroSlides[activeSlide]?.subtitle }}
              </p>

              <!-- CTA -->
              <div class="flex items-center gap-5">
                <Link
                  :href="heroSlides[activeSlide]?.ctaUrl || route('tenant.shop')"
                  class="btn bg-white text-gray-900 text-sm hover:bg-gray-100 transition-all shadow-xl"
                >
                  {{ heroSlides[activeSlide]?.ctaLabel || t('common.browse_the_shop') }}
                </Link>
                <Link
                  :href="route('tenant.shop.products')"
                  class="btn bg-white/10 border border-white/20 text-white hover:bg-white/15 text-sm backdrop-blur-sm"
                >
                  {{ t('client.shop.index.see_the_products') }} <i class="fa-solid fa-arrow-right text-xs"></i>
                </Link>
              </div>

              <!-- Stats — real store numbers, only shown once there's enough signal to be worth showing -->
              <div v-if="statItems.length" class="flex gap-8 sm:gap-10 pt-8">
                <div v-for="stat in statItems" :key="stat.label">
                  <p class="font-display text-2xl sm:text-3xl font-bold text-white">{{ stat.value }}</p>
                  <p class="text-xs sm:text-sm text-white/50 mt-1">{{ stat.label }}</p>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <!-- The slide's photo: a framed panel on the right, not a background for the whole section -->
        <div
          class="hidden md:block absolute right-8 lg:right-14 top-1/2 -translate-y-1/2 z-[5] w-[38%] max-w-sm aspect-square"
        >
          <TransitionGroup name="hero-fade">
            <img
              v-for="(slide, i) in heroSlides"
              v-show="i === activeSlide"
              :key="i"
              :src="slide.image"
              :alt="slide.title"
              class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl ring-1 ring-white/20 rotate-2"
            />
          </TransitionGroup>
        </div>

        <!-- Arrows and counter, bottom right -->
        <div v-if="heroSlides.length > 1" class="absolute bottom-5 right-6 z-20 flex items-center gap-3">
          <button
            @click="prevSlide"
            :aria-label="t('client.shop.index.previous_slide')"
            class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-sm flex items-center justify-center transition-all"
          >
            <i class="fa-solid fa-chevron-left text-white text-xs"></i>
          </button>
          <span class="text-xs font-medium text-white/70 tabular-nums"
            >{{ activeSlide + 1 }} / {{ heroSlides.length }}</span
          >
          <button
            @click="nextSlide"
            :aria-label="t('client.shop.index.next_slide')"
            class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-sm flex items-center justify-center transition-all"
          >
            <i class="fa-solid fa-chevron-right text-white text-xs"></i>
          </button>
        </div>
      </section>
    </div>

    <!-- ═══ DYNAMIC BLOCKS ══════════════════════════════════════════════ -->
    <template v-for="block in activeBlocks" :key="block.id">
      <!-- TRUST BADGES (also reused for the "guarantee_badges" block id) -->
      <section v-if="block.type === 'trust_badges'" class="bg-gray-50 border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 lg:px-8 py-14">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
            <div
              v-for="badge in block.settings?.badges?.length ? block.settings.badges : trustBadges"
              :key="badge.label"
              class="flex items-center gap-3.5"
            >
              <div
                class="rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm"
                style="width: 3.25rem; height: 3.25rem"
              >
                <i
                  :class="[badge.icon, badge.color?.startsWith('#') ? '' : badge.color, 'text-lg']"
                  :style="badge.color?.startsWith('#') ? { color: badge.color } : undefined"
                ></i>
              </div>
              <div>
                <div class="text-sm font-bold text-gray-900 leading-tight">{{ badge.label }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ badge.sub }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TESTIMONIALS -->
      <section v-else-if="block.type === 'testimonials'" class="bg-white py-16">
        <div class="max-w-6xl mx-auto px-4 lg:px-8">
          <SectionHeader
            :label="block.settings?.subheading || t('manager.homepagebuilder.index.what_customers_say_about_us')"
            :title="block.settings?.heading || t('common.customer_reviews')"
          />
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div
              v-for="n in [1, 2, 3]"
              :key="n"
              v-show="block.settings?.[`item${n}_text`]"
              class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center"
            >
              <div
                class="w-14 h-14 rounded-full theme-primary-bg text-white font-bold text-lg flex items-center justify-center mx-auto mb-3"
              >
                {{ (block.settings?.[`item${n}_author`] || '?').trim().charAt(0).toUpperCase() }}
              </div>
              <div class="flex gap-0.5 mb-3 justify-center">
                <i
                  v-for="s in 5"
                  :key="s"
                  class="fa-solid fa-star text-sm"
                  :class="
                    s <= (parseInt(block.settings?.[`item${n}_rating`]) || 5) ? 'text-yellow-400' : 'text-gray-200'
                  "
                ></i>
              </div>
              <p class="text-gray-700 text-sm leading-relaxed mb-4">"{{ block.settings?.[`item${n}_text`] }}"</p>
              <p class="text-sm font-bold text-gray-900">{{ block.settings?.[`item${n}_author`] }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section v-else-if="block.type === 'faq'" class="bg-gray-50 py-16">
        <div class="max-w-4xl mx-auto px-4 lg:px-8">
          <SectionHeader
            :label="block.settings?.subheading || 'FAQ'"
            :title="block.settings?.heading || t('common.frequently_asked_questions')"
          />
          <div
            class="mt-8 divide-y divide-gray-200 bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm"
          >
            <template v-for="n in [1, 2, 3, 4]" :key="n">
              <div v-if="block.settings?.[`q${n}`]" class="px-6">
                <button
                  type="button"
                  class="w-full flex items-center justify-between gap-4 py-5 text-left"
                  @click="toggleFaq(block.id + n)"
                >
                  <span class="font-bold text-gray-900 text-base">{{ block.settings[`q${n}`] }}</span>
                  <i
                    class="fa-solid fa-chevron-down text-gray-400 text-sm transition-transform shrink-0"
                    :class="{ 'rotate-180': openFaqs.has(block.id + n) }"
                  ></i>
                </button>
                <div v-show="openFaqs.has(block.id + n)" class="pb-5 text-[0.9375rem] text-gray-600 leading-relaxed">
                  {{ block.settings[`a${n}`] }}
                </div>
              </div>
            </template>
          </div>
        </div>
      </section>

      <!-- CATEGORIES -->
      <div v-else-if="block.type === 'categories' && categories.length" id="tresc" class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
          <SectionHeader
            :label="block.settings?.subheading || t('manager.homepagebuilder.index.browse_the_range')"
            :title="block.settings?.heading || t('common.categories')"
          />
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
            <Link
              v-for="(cat, i) in categories"
              :key="cat.id"
              :href="route('tenant.category.show', cat.slug)"
              class="group relative overflow-hidden rounded-3xl transition-all duration-300 hover:-translate-y-1.5"
              :class="
                cat.image
                  ? 'aspect-[4/3] flex items-end shadow-sm hover:shadow-xl'
                  : 'aspect-square flex flex-col items-center justify-center text-center p-5 shadow-lg hover:shadow-2xl'
              "
              :style="cat.image ? undefined : { background: categoryVisual(cat.name, i).gradient }"
            >
              <template v-if="cat.image">
                <img
                  :src="'/storage/' + cat.image"
                  :alt="cat.name"
                  class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                <div class="relative z-10 w-full p-4">
                  <span class="text-white font-bold text-sm md:text-base leading-tight drop-shadow">{{
                    cat.name
                  }}</span>
                  <div class="flex items-center gap-1 mt-1">
                    <span class="text-white/70 text-xs">{{ t('client.shop.index.browse') }}</span>
                    <i
                      class="fa-solid fa-arrow-right text-white/70 text-[10px] group-hover:translate-x-1 transition-transform"
                    ></i>
                  </div>
                </div>
              </template>
              <template v-else>
                <div
                  class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl backdrop-blur-sm group-hover:scale-110 transition-transform mb-3"
                >
                  <i :class="categoryVisual(cat.name, i).icon"></i>
                </div>
                <span class="font-display font-bold text-white text-sm md:text-base leading-tight">{{ cat.name }}</span>
                <div class="flex items-center gap-1 mt-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                  <span class="text-white/70 text-xs">{{ t('client.shop.index.browse') }}</span>
                  <i
                    class="fa-solid fa-arrow-right text-white/70 text-[10px] group-hover:translate-x-1 transition-transform"
                  ></i>
                </div>
              </template>
            </Link>
          </div>
        </div>
      </div>

      <!-- FEATURED PRODUCTS -->
      <div v-else-if="block.type === 'featured_products' && featuredProducts.length" class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
          <SectionHeader
            :label="block.settings?.subheading || t('common.picked_for_you')"
            :title="block.settings?.heading || t('common.featured_products')"
            :link="route('tenant.shop.products')"
            link-:label="t('common.see_all')"
          />
          <ProductGrid :products="featuredProducts" />
        </div>
      </div>

      <!-- BESTSELLERS -->
      <div v-else-if="block.type === 'bestsellers' && bestSellers.length" class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
          <SectionHeader
            :label="block.settings?.subheading || t('common.most_popular')"
            :title="block.settings?.heading || 'Bestsellery'"
            :link="route('tenant.shop.products')"
            link-:label="t('common.the_whole_shop')"
          />
          <ProductGrid :products="bestSellers" />
        </div>
      </div>

      <!-- NEW ARRIVALS -->
      <div v-else-if="block.type === 'new_arrivals' && newProducts.length" class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
          <SectionHeader
            :label="block.settings?.subheading || t('common.just_added')"
            :title="block.settings?.heading || t('common.new_in')"
            :link="route('tenant.shop.products')"
            link-:label="t('common.the_whole_shop')"
          />
          <ProductGrid :products="newProducts" />
        </div>
      </div>

      <!-- BANNER — split layout (text + floating image) when an image is set,
           matching the reference's "Promocje" section 1:1; centered fallback
           without one so the block still works with just text. -->
      <section
        v-else-if="block.type === 'banner' && (block.settings?.heading || block.settings?.text)"
        class="relative py-16 sm:py-24 overflow-hidden"
        :style="{ backgroundColor: block.settings?.bg_color || '#1e40af' }"
      >
        <div
          class="absolute inset-0 opacity-60"
          style="
            background-image:
              radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.08), transparent 45%),
              radial-gradient(circle at 85% 85%, rgba(255, 255, 255, 0.06), transparent 40%);
          "
        ></div>
        <div class="dot-grid-overlay absolute inset-0 pointer-events-none opacity-30"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid gap-12 items-center" :class="block.settings?.image_url ? 'lg:grid-cols-2' : ''">
            <div
              class="space-y-6 text-center"
              :class="block.settings?.image_url ? 'lg:text-left' : 'max-w-2xl mx-auto'"
            >
              <span
                v-if="block.settings?.eyebrow"
                class="inline-block px-4 py-1.5 rounded-full bg-white/15 text-white text-xs font-bold uppercase tracking-wider backdrop-blur-sm"
                >{{ block.settings.eyebrow }}</span
              >
              <h2
                v-if="block.settings?.heading"
                class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight"
              >
                {{ block.settings.heading }}
              </h2>
              <p
                v-if="block.settings?.text"
                class="text-white/80 text-lg"
                :class="block.settings?.image_url ? 'max-w-md' : 'max-w-xl mx-auto'"
              >
                {{ block.settings.text }}
              </p>
              <div :class="block.settings?.image_url ? '' : 'flex justify-center'">
                <CountdownBoxes
                  v-if="block.settings?.countdown_end"
                  :end="block.settings.countdown_end"
                  :align="block.settings?.image_url ? 'start' : 'center'"
                />
              </div>
              <Link
                v-if="block.settings?.btn_label && block.settings?.btn_url"
                :href="block.settings.btn_url"
                class="btn bg-white hover:bg-gray-100 shadow-2xl"
                :style="{ color: block.settings?.bg_color || '#1e40af' }"
              >
                {{ block.settings.btn_label }}
                <i class="fa-solid fa-arrow-right"></i>
              </Link>
            </div>

            <div v-if="block.settings?.image_url" class="hidden lg:block relative">
              <div class="absolute inset-0 bg-white/20 rounded-full blur-3xl"></div>
              <img
                :src="block.settings.image_url"
                alt=""
                class="relative rounded-[2.5rem] shadow-2xl border border-white/20 object-cover w-full aspect-[4/3]"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- NEWSLETTER -->
      <section v-else-if="block.type === 'newsletter'" class="bg-gray-900 py-20">
        <div class="max-w-xl mx-auto px-4 text-center">
          <div
            class="w-14 h-14 mx-auto mb-5 rounded-2xl bg-white/10 flex items-center justify-center text-white text-xl"
          >
            <i class="fa-solid fa-envelope-open-text"></i>
          </div>
          <h2 class="font-display text-2xl md:text-3xl font-bold text-white mb-2 tracking-tight">
            {{ block.settings?.heading || t('manager.homepagebuilder.index.keep_up_to_date') }}
          </h2>
          <p class="text-gray-400 mb-7 text-sm">
            {{ block.settings?.text || t('common.subscribe_and_be_first_to_hear') }}
          </p>
          <form class="flex gap-2 max-w-sm mx-auto" @submit.prevent="subscribeNewsletter">
            <input
              v-model="newsletterEmail"
              type="email"
              required
              :placeholder="t('client.shop.index.your_email')"
              class="flex-1 px-4 py-3 rounded-full text-sm bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:theme-primary-ring"
            />
            <input
              v-model="newsletterWebsite"
              type="text"
              tabindex="-1"
              autocomplete="off"
              style="position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0"
              aria-hidden="true"
            />
            <button
              type="submit"
              :disabled="newsletterSubmitting"
              class="btn btn--primary text-sm disabled:opacity-50 shrink-0"
            >
              {{ t('common.subscribe') }}
            </button>
          </form>
          <p
            v-if="newsletterMessage"
            class="text-sm mt-3"
            :class="newsletterError ? 'text-red-400' : 'text-green-400'"
            role="status"
            aria-live="polite"
          >
            {{ newsletterMessage }}
          </p>
        </div>
      </section>
    </template>

    <!-- Empty state — only renders (and only then takes up any space) when there's genuinely nothing else on the page -->
    <div v-if="!featuredProducts.length && !bestSellers.length && !newProducts.length" class="bg-gray-50 pb-16">
      <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="text-center py-28 bg-white rounded-3xl border border-dashed border-gray-200 mt-16">
          <div class="text-7xl mb-5">🛍️</div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ t('client.shop.index.shop_under_construction') }}</h3>
          <p class="text-gray-400">{{ t('client.shop.index.our_products_will_appear_here_soon') }}</p>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed, defineComponent, h, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import ProductCard from '@/Components/Client/ProductCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// ── Inline sub-components ───────────────────────────────────────────────────

const SectionHeader = defineComponent({
  props: { label: String, title: String, link: String, linkLabel: String },
  setup(props) {
    return () =>
      h('div', { class: 'flex items-end justify-between mb-8' }, [
        h('div', {}, [
          h('p', { class: 'text-xs font-bold theme-primary uppercase tracking-[0.14em] mb-1.5' }, props.label),
          h('h2', { class: 'font-display text-3xl md:text-4xl font-bold text-gray-900 leading-tight' }, props.title),
        ]),
        props.link
          ? h(
              Link,
              {
                href: props.link,
                class:
                  'text-sm font-semibold theme-primary hover:opacity-70 transition flex items-center gap-1.5 whitespace-nowrap',
              },
              () => [props.linkLabel, h('i', { class: 'fa-solid fa-arrow-right text-xs' })],
            )
          : null,
      ])
  },
})

const ProductGrid = defineComponent({
  props: { products: Array },
  setup(props) {
    return () =>
      h(
        'div',
        { class: 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 md:gap-6' },
        props.products.map((product) => h(ProductCard, { key: product.id, product })),
      )
  },
})

// Optional countdown for the "banner" block (editable via Homepage Builder's
// countdown_end field) — self-contained so its 1s timer only runs while a
// banner with a countdown is actually mounted on the page.
const CountdownBoxes = defineComponent({
  props: { end: String, align: { type: String, default: 'center' } },
  setup(props) {
    const now = ref(Date.now())
    let timer = null
    onMounted(() => {
      timer = setInterval(() => {
        now.value = Date.now()
      }, 1000)
    })
    onUnmounted(() => clearInterval(timer))

    const parts = computed(() => {
      const endMs = new Date(props.end).getTime()
      if (isNaN(endMs)) return null
      const diff = endMs - now.value
      if (diff <= 0) return null
      return {
        d: Math.floor(diff / 86400000),
        h: Math.floor((diff % 86400000) / 3600000),
        m: Math.floor((diff % 3600000) / 60000),
        s: Math.floor((diff % 60000) / 1000),
      }
    })

    return () => {
      if (!parts.value) return null
      const box = (val, label) =>
        h(
          'div',
          { class: 'bg-white/15 backdrop-blur-sm rounded-2xl px-4 py-3 sm:px-5 sm:py-4 text-center min-w-[64px]' },
          [
            h(
              'p',
              { class: 'font-display text-2xl sm:text-3xl font-bold text-white tabular-nums' },
              String(val).padStart(2, '0'),
            ),
            h('p', { class: 'text-white/60 text-[10px] sm:text-xs uppercase tracking-wider mt-1' }, label),
          ],
        )
      const justify = props.align === 'start' ? 'justify-start' : 'justify-center'
      return h('div', { class: `flex items-center ${justify} gap-2.5 sm:gap-3 mb-8 flex-wrap` }, [
        box(parts.value.d, 'Dni'),
        box(parts.value.h, 'Godzin'),
        box(parts.value.m, 'Minut'),
        box(parts.value.s, 'Sekund'),
      ])
    }
  },
})

// ── Props & state ─────────────────────────────────────────────────────────────

const props = defineProps({
  shopName: { type: String, default: '' },
  categories: { type: Array, default: () => [] },
  featuredProducts: { type: Array, default: () => [] },
  bestSellers: { type: Array, default: () => [] },
  newProducts: { type: Array, default: () => [] },
  heroImageUrl: { type: String, default: null },
  heroTitle: { type: String, default: null },
  heroSubtitle: { type: String, default: null },
  heroSlidesProp: { type: Array, default: () => [] },
  heroBadge: { type: String, default: null },
  logoUrl: { type: String, default: null },
  homepageBlocks: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

// Real store numbers (never fabricated) — each stat only appears once there's
// enough of it to be worth showing, so a brand-new shop doesn't display "0 opinii".
const statItems = computed(() => {
  const items = []
  if (props.stats?.products > 0) items.push({ value: `${props.stats.products}+`, label: t('common.products_on_offer') })
  if (props.stats?.reviews_count > 0)
    items.push({
      value: `${props.stats.reviews_avg}★`,
      label: t('client.shop.index.a_customer_reviews', { a: props.stats.reviews_count }),
    })
  if (props.stats?.customers > 0) items.push({ value: `${props.stats.customers}+`, label: t('common.happy_customers') })
  return items
})

// The active blocks, minus the hero: that one renders above them on its own.
const DEFAULT_BLOCK_ORDER = [
  'trust_badges',
  'categories',
  'featured_products',
  'bestsellers',
  'new_arrivals',
  'banner',
  'newsletter',
]
const activeBlocks = computed(() => {
  const blocks = props.homepageBlocks?.length
    ? props.homepageBlocks
    : DEFAULT_BLOCK_ORDER.map((type) => ({
        id: type,
        type,
        enabled: !['banner', 'newsletter'].includes(type),
        settings: {},
      }))
  return blocks.filter((b) => b.enabled && b.type !== 'hero')
})

// What the slider shows before a shop has set anything up.
const DEFAULT_SLIDES = [
  {
    image: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=1920&auto=format&fit=crop',
    title: null,
    subtitle: t('common.discover_exceptional_products_of_the_highest'),
    ctaLabel: t('common.browse_the_shop'),
    ctaUrl: null,
  },
  {
    image: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1920&auto=format&fit=crop',
    title: t('common.new_in_the_shop'),
    subtitle: t('common.see_what_has_just_arrived'),
    ctaLabel: t('common.see_what_is_new'),
    ctaUrl: null,
  },
  {
    image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1920&auto=format&fit=crop',
    title: t('client.shop.category.bestsellers'),
    subtitle: t('common.our_best_selling_products_in_one'),
    ctaLabel: t('client.shop.category.bestsellers'),
    ctaUrl: null,
  },
]

const heroSlides = computed(() => {
  if (props.heroSlidesProp?.length) return props.heroSlidesProp
  // A single hero image from settings becomes a single slide.
  if (props.heroImageUrl)
    return [
      {
        image: props.heroImageUrl,
        title: props.heroTitle || props.shopName,
        subtitle: props.heroSubtitle || t('common.discover_exceptional_products_of_the_highest'),
        ctaLabel: t('common.browse_the_shop'),
        ctaUrl: null,
      },
    ]
  // Last resort: three example slides.
  return DEFAULT_SLIDES.map((s, i) => ({
    ...s,
    title: i === 0 ? props.heroTitle || props.shopName : s.title,
  }))
})

// ── Slider logic ────────────────────────────────────────────────────────────
const activeSlide = ref(0)
let autoplayTimer = null

function nextSlide() {
  activeSlide.value = (activeSlide.value + 1) % heroSlides.value.length
  resetAutoplay()
}
function prevSlide() {
  activeSlide.value = (activeSlide.value - 1 + heroSlides.value.length) % heroSlides.value.length
  resetAutoplay()
}
function resetAutoplay() {
  clearInterval(autoplayTimer)
  autoplayTimer = setInterval(nextSlide, 5000)
}

onMounted(() => {
  if (heroSlides.value.length > 1) resetAutoplay()
})
onUnmounted(() => clearInterval(autoplayTimer))

const trustBadges = [
  {
    icon: 'fa-solid fa-truck-fast',
    color: 'text-emerald-600',
    bg: 'bg-emerald-50',
    label: t('common.free_delivery'),
    sub: t('manager.homepagebuilder.index.over_199_pln'),
  },
  {
    icon: 'fa-solid fa-shield-halved',
    color: 'text-blue-600',
    bg: 'bg-blue-50',
    label: t('common.secure_payments'),
    sub: t('client.shop.index.ssl_3d_secure'),
  },
  {
    icon: 'fa-solid fa-rotate-left',
    color: 'text-violet-600',
    bg: 'bg-violet-50',
    label: t('common.30_days_to_return'),
    sub: t('common.no_reason_needed'),
  },
  {
    icon: 'fa-solid fa-headset',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
    label: t('common.customer_support'),
    sub: t('common.mon_fri_9_5'),
  },
]

// Fixed decorative cycle (not tenant-configurable, same as the .btn/.product-card
// design layer) — categories are real DB rows, only the tile's color/icon is assigned.
const CATEGORY_GRADIENTS = [
  'linear-gradient(135deg, #6366f1 0%, #7c3aed 100%)',
  'linear-gradient(135deg, #10b981 0%, #0d9488 100%)',
  'linear-gradient(135deg, #f97316 0%, #ef4444 100%)',
  'linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%)',
  'linear-gradient(135deg, #ec4899 0%, #e11d48 100%)',
  'linear-gradient(135deg, #a855f7 0%, #6366f1 100%)',
  'linear-gradient(135deg, #eab308 0%, #f97316 100%)',
  'linear-gradient(135deg, #14b8a6 0%, #0891b2 100%)',
]

// Keyword → icon, checked against the (Polish) category name so common shop
// categories get a matching pictogram instead of a purely arbitrary one;
// falls back to a fixed per-index icon when nothing matches.
const CATEGORY_ICON_RULES = [
  [/elektr|rtv|agd|telewiz/i, 'fa-solid fa-plug'],
  [/telefon|smartfon|mobiln/i, 'fa-solid fa-mobile-screen'],
  [/komputer|laptop/i, 'fa-solid fa-laptop'],
  [/audio|słuchaw|głośnik|muzyk/i, 'fa-solid fa-headphones'],
  [/dom|ogr[oó]d|meble|wnętrz/i, 'fa-solid fa-house'],
  [/moda|odzie[żz]|ubran|ubior/i, 'fa-solid fa-shirt'],
  [/but[yó]|obuwie/i, 'fa-solid fa-shoe-prints'],
  [/sport|fitness|trening/i, 'fa-solid fa-dumbbell'],
  [/uroda|kosmet|piel[eę]gnacj/i, 'fa-solid fa-spa'],
  [/dziec|zabawk/i, 'fa-solid fa-baby'],
  [/gam(e|ing)|gr[ay] wideo/i, 'fa-solid fa-gamepad'],
  [/ksi[ąa][żz]k|papierni/i, 'fa-solid fa-book'],
  [/bi[żz]uteri|gem|zegar/i, 'fa-solid fa-gem'],
  [/spo[żz]yw|jedzeni|kuchni/i, 'fa-solid fa-utensils'],
  [/zwierz[ęe]|pet/i, 'fa-solid fa-paw'],
  [/samoch[oó]d|moto|akcesori.*aut/i, 'fa-solid fa-car'],
  [/zdrowi|apte/i, 'fa-solid fa-heart-pulse'],
]
const CATEGORY_ICON_FALLBACK = [
  'fa-solid fa-basket-shopping',
  'fa-solid fa-tag',
  'fa-solid fa-gift',
  'fa-solid fa-star',
]

function categoryVisual(name, index) {
  const match = CATEGORY_ICON_RULES.find(([re]) => re.test(name || ''))
  return {
    gradient: CATEGORY_GRADIENTS[index % CATEGORY_GRADIENTS.length],
    icon: match ? match[1] : CATEGORY_ICON_FALLBACK[index % CATEGORY_ICON_FALLBACK.length],
  }
}

const openFaqs = ref(new Set())
function toggleFaq(key) {
  const next = new Set(openFaqs.value)
  if (next.has(key)) {
    next.delete(key)
  } else {
    next.add(key)
  }
  openFaqs.value = next
}

const newsletterEmail = ref('')
const newsletterWebsite = ref('')
const newsletterSubmitting = ref(false)
const newsletterMessage = ref('')
const newsletterError = ref(false)

async function subscribeNewsletter() {
  newsletterSubmitting.value = true
  newsletterMessage.value = ''
  try {
    await window.axios.post(route('tenant.newsletter.subscribe'), {
      email: newsletterEmail.value,
      website: newsletterWebsite.value,
    })
    newsletterError.value = false
    newsletterMessage.value = t('common.thank_you_for_subscribing_to_the')
    newsletterEmail.value = ''
  } catch (e) {
    newsletterError.value = true
    newsletterMessage.value = e?.response?.data?.errors?.email?.[0] ?? t('common.it_could_not_be_saved_try')
  } finally {
    newsletterSubmitting.value = false
  }
}
</script>

<style scoped>
.hero-fade-enter-active,
.hero-fade-leave-active {
  transition: opacity 0.8s ease;
}
.hero-fade-enter-from,
.hero-fade-leave-to {
  opacity: 0;
}

.hero-content-enter-active {
  transition: all 0.5s ease 0.2s;
}
.hero-content-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
</style>
