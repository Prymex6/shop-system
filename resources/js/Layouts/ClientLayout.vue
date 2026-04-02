<template>
  <!-- SEO + Favicon (#15) -->
  <Head>
    <title>{{ pageTitle }}</title>
    <meta name="description" :content="pageDescription" />
    <link v-if="tenant?.favicon_url" rel="icon" :href="tenant.favicon_url" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap"
      rel="stylesheet"
    />
  </Head>

  <!-- Preloader — brief branded spinner so the first paint doesn't feel like a bare, unstyled flash -->
  <div
    v-if="showPreloader"
    class="fixed inset-0 z-[100] bg-white flex items-center justify-center transition-opacity duration-500"
    :class="{ 'opacity-0 pointer-events-none': preloaderHiding }"
  >
    <div class="relative w-16 h-16">
      <div
        class="absolute inset-0 border-4 rounded-full"
        style="border-color: rgba(var(--color-primary-rgb, 79, 70, 229), 0.15)"
      ></div>
      <div
        class="absolute inset-0 border-4 border-transparent rounded-full animate-spin"
        style="border-top-color: var(--color-primary, #4f46e5)"
      ></div>
      <div class="absolute inset-0 flex items-center justify-center font-display font-bold text-lg theme-primary">
        {{ tenantInitial }}
      </div>
    </div>
  </div>

  <div class="ref-theme min-h-screen bg-white text-gray-900 antialiased flex flex-col">
    <FlashMessage />

    <!-- Scrolling trust strip — reuses the same "trust_badges" Homepage Builder
             block content as the homepage grid, so editing it once updates both. -->
    <div v-if="marqueeItems.length" class="marquee-bar relative overflow-hidden text-white text-xs sm:text-sm py-2.5">
      <div class="marquee-track flex items-center whitespace-nowrap">
        <span
          v-for="(item, i) in [...marqueeItems, ...marqueeItems]"
          :key="i"
          class="flex items-center gap-2 px-8 shrink-0"
        >
          <i :class="item.icon"></i> {{ item.label }}
        </span>
      </div>
    </div>

    <!-- Announcement Banner -->
    <AnnouncementBanner />

    <!-- Header -->
    <nav class="bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100">
      <div class="container mx-auto px-4 lg:px-6 py-4 flex items-center gap-6">
        <!-- Logo -->
        <Link :href="route('tenant.shop')" class="flex items-center gap-2.5 shrink-0 group">
          <img v-if="tenant?.logo_url" :src="tenant.logo_url" :alt="tenantName" class="h-11 w-auto object-contain" />
          <span
            v-else
            class="brand-mark w-10 h-10 rounded-xl flex items-center justify-center text-white font-display font-bold text-lg shrink-0 group-hover:scale-105 transition-transform"
          >
            {{ tenantInitial }}
          </span>
          <span class="font-display text-xl md:text-2xl font-bold tracking-tight text-gray-900">
            {{ tenantName }}<span class="theme-primary">.</span>
          </span>
        </Link>

        <!-- Desktop nav -->
        <div class="hidden md:flex items-center gap-6 font-medium text-[0.9rem] shrink-0">
          <Link
            :href="route('tenant.shop')"
            class="nav-link transition"
            :class="isActive('/') ? 'theme-primary font-semibold' : 'text-gray-600 hover:text-gray-900'"
          >
            {{ t('common.shop') }}
          </Link>
          <Link :href="route('tenant.shop.products')" class="nav-link transition text-gray-600 hover:text-gray-900">
            {{ t('common.all_products') }}
          </Link>
          <Link
            v-if="hasCollections"
            :href="route('tenant.collections.index')"
            class="nav-link transition"
            :class="isActive('/kolekcje') ? 'theme-primary font-semibold' : 'text-gray-600 hover:text-gray-900'"
          >
            {{ t('layout.clientlayout.collections') }}
          </Link>
          <Link
            v-if="hasBlog"
            :href="route('tenant.blog.index')"
            class="nav-link transition"
            :class="isActive('/blog') ? 'theme-primary font-semibold' : 'text-gray-600 hover:text-gray-900'"
          >
            Blog
          </Link>
          <Link
            :href="route('tenant.contact')"
            class="nav-link transition"
            :class="isActive('/kontakt') ? 'theme-primary font-semibold' : 'text-gray-600 hover:text-gray-900'"
          >
            {{ t('layout.clientlayout.contact') }}
          </Link>
        </div>

        <!-- Search bar (desktop) -->
        <div class="hidden md:block flex-1 max-w-sm ml-auto">
          <SearchBar />
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-4 shrink-0">
          <!-- Language switcher -->
          <LocaleSwitcher class="hidden sm:block" />

          <!-- Currency switcher -->
          <select
            v-if="enabledCurrencies.length > 1"
            :value="currentCurrency"
            @change="setCurrency($event.target.value)"
            class="hidden sm:block text-xs font-medium text-gray-600 border border-gray-200 rounded-full px-2.5 py-1 bg-white hover:border-gray-300 focus:outline-none focus:ring-2 focus:theme-primary-ring"
            :title="t('common.currency')"
          >
            <option v-for="c in enabledCurrencies" :key="c" :value="c">{{ c }}</option>
          </select>

          <!-- Wishlist (authenticated) -->
          <Link
            v-if="customer"
            :href="route('tenant.wishlist')"
            class="relative text-gray-500 hover:text-gray-900 transition p-1"
            :title="t('common.wishlist')"
          >
            <i class="fa-regular fa-heart text-lg"></i>
          </Link>

          <!-- Cart -->
          <button
            @click="cartStore.toggleCart()"
            class="relative flex items-center gap-2 px-4 py-2 rounded-full bg-gray-900 text-white font-medium text-sm hover:bg-gray-800 transition-all hover:scale-105 active:scale-95"
            :title="t('common.cart')"
          >
            <i class="fa-solid fa-bag-shopping text-sm"></i>
            <span class="hidden sm:inline">{{ t('common.cart') }}</span>
            <span
              v-if="cartStore.itemCount > 0"
              class="absolute -top-1.5 -right-1.5 inline-flex items-center justify-center theme-primary-bg text-white text-[10px] font-bold rounded-full w-5 h-5 leading-none shadow"
            >
              {{ cartStore.itemCount }}
            </span>
          </button>

          <!-- User panel -->
          <template v-if="customer">
            <div class="relative hidden md:block" ref="userMenuRef">
              <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 hover:opacity-80 transition">
                <span
                  class="w-8 h-8 theme-primary-bg rounded-full flex items-center justify-center text-white font-bold text-sm"
                >
                  {{ customer.name?.charAt(0)?.toUpperCase() }}
                </span>
                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
              </button>
              <div
                v-if="userMenuOpen"
                class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
              >
                <Link
                  :href="route('tenant.account')"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
                >
                  <i class="fa-solid fa-user w-4 text-gray-700"></i> {{ t('common.my_account') }}
                </Link>
                <Link
                  :href="route('tenant.client.logout')"
                  method="post"
                  as="button"
                  class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
                >
                  <i class="fa-solid fa-right-from-bracket w-4 text-gray-700"></i> {{ t('common.sign_out') }}
                </Link>
              </div>
            </div>
          </template>
          <template v-else>
            <Link
              :href="route('tenant.client.login')"
              class="hidden md:block text-gray-500 hover:text-gray-900 transition p-1"
              :title="t('common.sign_in')"
            >
              <i class="fa-regular fa-user text-lg"></i>
            </Link>
          </template>

          <!-- Hamburger mobile -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-700 focus:outline-none text-2xl">
            <i v-if="!mobileMenuOpen" class="fa-solid fa-bars"></i>
            <i v-else class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <!-- Availability strip (only shown when the shop can't currently take orders) -->
      <div
        v-if="!shopAvailable"
        class="bg-red-50 border-t border-red-100 text-red-700 text-xs font-medium text-center py-1.5"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block mr-1.5"></span>
        {{ t('layout.clientlayout.the_shop_is_closed_for_the') }}
      </div>

      <!-- Mobile search -->
      <div class="md:hidden border-t border-gray-100 px-4 py-2">
        <SearchBar />
      </div>

      <!-- Mobile menu -->
      <div
        v-show="mobileMenuOpen"
        class="md:hidden bg-white border-t border-gray-100 px-4 py-3 space-y-1 text-sm font-medium"
      >
        <Link
          :href="route('tenant.shop')"
          class="block py-2.5 nav-link text-gray-700"
          @click="mobileMenuOpen = false"
          >{{ t('common.shop') }}</Link
        >
        <Link
          :href="route('tenant.shop.products')"
          class="block py-2.5 nav-link text-gray-700"
          @click="mobileMenuOpen = false"
          >{{ t('common.all_products') }}</Link
        >
        <Link
          v-if="hasCollections"
          :href="route('tenant.collections.index')"
          class="block py-2.5 nav-link text-gray-700"
          @click="mobileMenuOpen = false"
          >{{ t('layout.clientlayout.collections') }}</Link
        >
        <Link
          v-if="hasBlog"
          :href="route('tenant.blog.index')"
          class="block py-2.5 nav-link text-gray-700"
          @click="mobileMenuOpen = false"
          >Blog</Link
        >
        <Link
          :href="route('tenant.contact')"
          class="block py-2.5 nav-link text-gray-700"
          @click="mobileMenuOpen = false"
          >{{ t('layout.clientlayout.contact') }}</Link
        >
        <template v-if="customer">
          <Link :href="route('tenant.account')" class="block py-2 nav-link">{{ t('common.my_account') }}</Link>
          <Link
            :href="route('tenant.client.logout')"
            method="post"
            as="button"
            class="block py-2 text-gray-500 hover:text-gray-700"
          >
            {{ t('common.sign_out') }}
          </Link>
        </template>
        <template v-else>
          <Link :href="route('tenant.client.login')" class="block py-2 hover:theme-primary">{{
            t('layout.clientlayout.sign_in')
          }}</Link>
          <Link :href="route('tenant.client.register')" class="block py-2 hover:theme-primary">{{
            t('common.register')
          }}</Link>
        </template>
        <div class="pt-2 border-t border-gray-100">
          <div class="flex items-center text-sm" :class="shopAvailable ? 'text-green-600' : 'text-red-500'">
            <span class="w-2 h-2 rounded-full mr-1.5" :class="shopAvailable ? 'bg-green-500' : 'bg-red-400'"></span>
            {{ shopAvailable ? t('common.in_stock') : t('client.shop.product.out_of_stock') }}
          </div>
        </div>
      </div>
    </nav>

    <!-- Vacation Banner only -->
    <div
      v-if="tenant?.vacation_mode"
      class="bg-yellow-50 border-b border-yellow-300 text-yellow-900 px-4 py-3 text-center text-sm font-medium"
    >
      <i class="fa-solid fa-triangle-exclamation mr-2 text-amber-500"></i>
      {{ tenant.vacation_message || t('common.the_shop_is_closed_for_the') }}
    </div>

    <!-- Main Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Cart Sidebar -->
    <CartSidebar />

    <!-- Footer -->
    <footer class="bg-gray-950 text-white">
      <!-- Main footer grid -->
      <div class="max-w-7xl mx-auto px-4 lg:px-8 pt-16 pb-10">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-10">
          <!-- Brand — spans 2 cols on lg -->
          <div class="col-span-2 lg:col-span-2">
            <div class="flex items-center gap-2.5 mb-5">
              <img v-if="tenant?.logo_url" :src="tenant.logo_url" :alt="tenantName" class="h-9 w-auto" />
              <span
                v-else
                class="brand-mark w-9 h-9 rounded-xl flex items-center justify-center text-white font-display font-bold text-base shrink-0"
                >{{ tenantInitial }}</span
              >
              <span class="font-display text-xl font-bold text-white"
                >{{ tenantName }}<span class="theme-primary">.</span></span
              >
            </div>
            <p class="text-gray-400 text-sm leading-relaxed max-w-sm mb-6">
              {{ tenant?.description || t('common.your_online_shop_with_fast_dispatch') }}
            </p>
            <!-- Contact info -->
            <ul class="text-gray-400 text-sm space-y-2 mb-6">
              <li v-if="tenant?.phone">
                <a :href="'tel:' + tenant.phone" class="flex items-center gap-2 hover:text-white transition">
                  <i class="fa-solid fa-phone w-4 text-center theme-primary"></i>
                  {{ tenant.phone }}
                </a>
              </li>
              <li v-if="tenant?.email">
                <a :href="'mailto:' + tenant.email" class="flex items-center gap-2 hover:text-white transition">
                  <i class="fa-solid fa-envelope w-4 text-center theme-primary"></i>
                  {{ tenant.email }}
                </a>
              </li>
              <li v-if="tenant?.address" class="flex items-start gap-2">
                <i class="fa-solid fa-location-dot w-4 text-center theme-primary mt-0.5 shrink-0"></i>
                <span>{{ tenant.address }}</span>
              </li>
            </ul>
            <!-- Social -->
            <div
              v-if="tenant?.facebook_url || tenant?.instagram_url || tenant?.tiktok_url"
              class="flex items-center gap-2"
            >
              <a
                v-if="tenant.facebook_url"
                :href="tenant.facebook_url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-9 h-9 bg-[#1877F2] hover:bg-[#0e63d0] rounded-xl flex items-center justify-center transition-colors"
                aria-label="Facebook"
              >
                <i class="fa-brands fa-facebook-f text-sm text-white"></i>
              </a>
              <a
                v-if="tenant.instagram_url"
                :href="tenant.instagram_url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-9 h-9 bg-gradient-to-tr from-[#f9ce34] via-[#ee2a7b] to-[#6228d7] hover:opacity-80 rounded-xl flex items-center justify-center transition-opacity"
                aria-label="Instagram"
              >
                <i class="fa-brands fa-instagram text-sm text-white"></i>
              </a>
              <a
                v-if="tenant.tiktok_url"
                :href="tenant.tiktok_url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-9 h-9 bg-black hover:bg-gray-800 rounded-xl flex items-center justify-center transition-colors border border-gray-700"
                aria-label="TikTok"
              >
                <i class="fa-brands fa-tiktok text-sm text-white"></i>
              </a>
            </div>
          </div>

          <!-- Sklep -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-5">{{ t('common.shop') }}</h4>
            <ul class="text-gray-400 text-sm space-y-3">
              <li>
                <Link :href="route('tenant.shop')" class="hover:text-white transition">{{
                  t('common.all_products')
                }}</Link>
              </li>
              <li v-if="hasCollections">
                <Link :href="route('tenant.collections.index')" class="hover:text-white transition">{{
                  t('layout.clientlayout.collections')
                }}</Link>
              </li>
              <li v-if="hasBlog">
                <Link :href="route('tenant.blog.index')" class="hover:text-white transition">Blog</Link>
              </li>
              <li>
                <Link :href="route('tenant.contact')" class="hover:text-white transition">{{
                  t('layout.clientlayout.contact')
                }}</Link>
              </li>
            </ul>
          </div>

          <!-- Customer service -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-5">
              {{ t('layout.clientlayout.customer_service') }}
            </h4>
            <ul class="text-gray-400 text-sm space-y-3">
              <li>
                <Link :href="route('tenant.shipping')" class="hover:text-white transition">{{
                  t('common.delivery_and_payment')
                }}</Link>
              </li>
              <li>
                <Link :href="route('tenant.returns')" class="hover:text-white transition">{{
                  t('layout.clientlayout.returns_and_complaints')
                }}</Link>
              </li>
              <li><Link :href="route('tenant.faq')" class="hover:text-white transition">FAQ</Link></li>
              <li v-if="customer">
                <Link :href="route('tenant.account')" class="hover:text-white transition">{{
                  t('common.my_account')
                }}</Link>
              </li>
              <li v-if="!customer">
                <Link :href="route('tenant.client.login')" class="hover:text-white transition">{{
                  t('common.sign_in')
                }}</Link>
              </li>
              <li v-if="!customer">
                <Link :href="route('tenant.client.register')" class="hover:text-white transition">{{
                  t('common.register')
                }}</Link>
              </li>
            </ul>
          </div>

          <!-- Prawne -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-5">
              {{ t('layout.clientlayout.legal_information') }}
            </h4>
            <ul class="text-gray-400 text-sm space-y-3">
              <li>
                <Link :href="route('tenant.terms')" class="hover:text-white transition">{{
                  t('layout.clientlayout.terms_and_conditions')
                }}</Link>
              </li>
              <li>
                <Link :href="route('tenant.privacy')" class="hover:text-white transition">{{
                  t('common.privacy_policy')
                }}</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <span class="text-gray-500 text-xs"
            >&copy; {{ new Date().getFullYear() }} {{ t('common.all_rights_reserved', { a: tenantName }) }}</span
          >
          <!-- Payment icons – automatyczne na podstawie aktywnych bramek -->
          <div v-if="paymentMethods.length" class="flex items-center gap-2">
            <span class="text-gray-600 text-xs mr-2">{{ t('layout.clientlayout.we_accept') }}</span>
            <span
              v-for="m in paymentMethods"
              :key="m.key"
              class="bg-gray-800 rounded px-2 py-1 text-[10px] font-bold tracking-wide"
              :class="m.color"
            >
              {{ m.label }}
            </span>
            <div class="flex items-center gap-1 text-gray-600 ml-3">
              <i class="fa-solid fa-lock text-[10px]"></i>
              <span class="text-[10px]">SSL</span>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Cookie consent (RODO) -->
    <CookieConsent
      :ga-id="tenant?.google_analytics_id || ''"
      :pixel-id="tenant?.facebook_pixel_id || ''"
      :tiktok-pixel-id="tenant?.tiktok_pixel_id || ''"
      :shop-name="tenantName"
      :shop-email="tenant?.email || ''"
      :primary-color="tenant?.theme_primary_color || '#4f46e5'"
    />

    <!-- Live Chat Widget -->
    <ChatWidget />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/cartStore'
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue'
import CartSidebar from '@/Components/Client/CartSidebar.vue'
import CookieConsent from '@/Components/CookieConsent.vue'
import AnnouncementBanner from '@/Components/Client/AnnouncementBanner.vue'
import ChatWidget from '@/Components/ChatWidget.vue'
import SearchBar from '@/Components/Client/SearchBar.vue'
import FlashMessage from '@/Components/FlashMessage.vue'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  title: { type: String, default: null },
  description: { type: String, default: null },
})

const page = usePage()
const cartStore = useCartStore()
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)

const handleClickOutside = (e) => {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const tenant = computed(() => page.props.tenant)
const customer = computed(() => page.props.auth?.customer)
const tenantName = computed(() => tenant.value?.name || t('common.shop'))
const tenantInitial = computed(() => tenantName.value.trim().charAt(0).toUpperCase() || 'S')
const hasCollections = computed(() => page.props.has_collections)
const hasBlog = computed(() => page.props.has_blog)

const DEFAULT_MARQUEE_ITEMS = [
  { icon: 'fa-solid fa-truck-fast', label: t('common.free_delivery_over_199_pln') },
  { icon: 'fa-solid fa-rotate-left', label: t('common.30_days_to_return') },
  { icon: 'fa-solid fa-shield-halved', label: t('common.secure_payments') },
  { icon: 'fa-solid fa-headset', label: t('common.customer_support') },
]
const marqueeItems = computed(() => {
  const blocks = tenant.value?.homepage_blocks
  const trustBlock = Array.isArray(blocks)
    ? blocks.find((b) => b.id === 'trust_badges' || b.type === 'trust_badges')
    : null
  const badges = trustBlock?.settings?.badges
  if (Array.isArray(badges) && badges.length) {
    return badges.map((b) => ({
      icon: b.icon || 'fa-solid fa-circle-check',
      label: b.label + (b.sub ? ' — ' + b.sub : ''),
    }))
  }
  return DEFAULT_MARQUEE_ITEMS
})

const { currentCurrency, enabledCurrencies } = useCurrency()
function setCurrency(currency) {
  router.post(route('tenant.currency.set'), { currency }, { preserveScroll: true })
}

// Previously this layout ignored the `title`/`description` props that 12+
// client pages already passed it (product name, category name, search
// query...), rendering the exact same <title> and meta description on
// every single page — a storefront-wide duplicate-title SEO problem, and a
// poor landing-page quality score for TikTok/Google ads pointed at specific
// products.
const pageTitle = computed(() => (props.title ? `${props.title} — ${tenantName.value}` : tenantName.value))
const pageDescription = computed(() => {
  const raw = props.description || tenant.value?.description || tenantName.value
  // Strip any stray HTML (product/category descriptions are often rich text)
  // and keep it within a reasonable meta-description length.
  const plain = String(raw)
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
  return plain.length > 160 ? plain.slice(0, 157) + '...' : plain
})

const PAYMENT_META = {
  visa: { label: 'VISA', color: 'text-gray-300' },
  mastercard: { label: 'MASTERCARD', color: 'text-gray-300' },
  blik: { label: 'BLIK', color: 'text-blue-400' },
  przelewy: { label: 'PRZELEWY24', color: 'text-gray-300' },
  przelew: { label: 'PRZELEW', color: 'text-gray-300' },
  pobranie: { label: 'POBRANIE', color: 'text-yellow-400' },
}
const paymentMethods = computed(() =>
  (tenant.value?.payment_methods ?? []).map((key) => ({ key, ...PAYMENT_META[key] })).filter((m) => m.label),
)

// Preloader — shown only very briefly on first paint, not on every Inertia
// navigation. Gated via sessionStorage rather than a module-level JS
// variable: Vite's per-page dynamic-import chunks don't reliably share
// top-level module state across an Inertia SPA transition in this setup
// (observed: a fresh ClientLayout mount after navigating between two lazy
// page chunks re-ran the module's top-level code), so a module-scoped flag
// re-armed itself on some navigations. sessionStorage is a real browser API
// keyed to the tab, immune to that.
let sawPreloaderThisTab = false
try {
  sawPreloaderThisTab = sessionStorage.getItem('shop_preloader_shown') === '1'
} catch (e) {
  /* private mode etc. */
}

const showPreloader = ref(!sawPreloaderThisTab)
const preloaderHiding = ref(false)

onMounted(() => {
  applyThemeCSS()

  if (showPreloader.value) {
    try {
      sessionStorage.setItem('shop_preloader_shown', '1')
    } catch (e) {
      /* private mode etc. */
    }
    setTimeout(() => {
      preloaderHiding.value = true
    }, 350)
    setTimeout(() => {
      showPreloader.value = false
    }, 850)
  }

  // Save table ID from QR code URL (?table=1) to localStorage
  const urlParams = new URLSearchParams(window.location.search)
  const tableParam = urlParams.get('table')
  if (tableParam && /^\d+$/.test(tableParam)) {
    localStorage.setItem('qr_table_id', tableParam)
  }
})

// --- Theme CSS ---
const fontFamilies = {
  inter: "'Inter', 'Segoe UI', sans-serif",
  roboto: "'Roboto', 'Arial', sans-serif",
  merriweather: "'Merriweather', 'Georgia', serif",
  playfair: "'Playfair Display', 'Georgia', serif",
}

const themeCSS = computed(() => {
  const primary = tenant.value?.theme_primary_color || '#4f46e5'
  const font = tenant.value?.theme_font || 'inter'
  // Strip dangerous patterns to prevent style-block injection (defense-in-depth alongside backend validation)
  const rawCss = tenant.value?.custom_css || ''
  const customCss = rawCss
    .replace(/<\/style/gi, '')
    .replace(/<script/gi, '')
    .replace(/javascript\s*:/gi, '')
    .replace(/expression\s*\(/gi, '')
    .replace(/url\s*\(\s*["']?\s*javascript/gi, '')
  const fontFamily = fontFamilies[font] || fontFamilies.inter

  const hexToRgb = (hex) => {
    const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return r ? [parseInt(r[1], 16), parseInt(r[2], 16), parseInt(r[3], 16)] : [79, 70, 229]
  }
  const [pr, pg, pb] = hexToRgb(primary)
  const rgb = `${pr}, ${pg}, ${pb}`
  // Darker + deeper-saturated shade of the primary, used for the hero
  // gradient — computed instead of hardcoded so it still fits whatever
  // brand color a tenant picks in Settings, not just one preset hue.
  const mix = (c, target, amount) => Math.round(c + (target - c) * amount)
  const darkR = mix(pr, 0, 0.55),
    darkG = mix(pg, 0, 0.55),
    darkB = mix(pb, 0, 0.55)

  return `
:root {
  --color-primary: ${primary};
  --color-primary-rgb: ${rgb};
  --color-primary-dark: rgb(${darkR}, ${darkG}, ${darkB});
  --font-main: ${fontFamily};
  --shadow-card: 0 1px 2px rgba(15,23,42,.04), 0 8px 24px -8px rgba(15,23,42,.10);
  --shadow-card-hover: 0 4px 12px rgba(15,23,42,.06), 0 16px 40px -12px rgba(15,23,42,.16);
}
body { font-family: var(--font-main); }
.theme-primary { color: var(--color-primary) !important; }
.theme-primary-bg { background-color: var(--color-primary) !important; }
.theme-primary-border { border-color: var(--color-primary) !important; }
.theme-primary-bg-light { background-color: rgba(var(--color-primary-rgb), 0.08) !important; }
.theme-primary-ring { --tw-ring-color: var(--color-primary) !important; box-shadow: var(--tw-ring-offset-shadow,0 0 #0000),0 0 0 2px var(--color-primary),var(--tw-shadow,0 0 #0000) !important; }
.theme-primary-accent { accent-color: var(--color-primary) !important; }
.nav-link:hover, .hover\\:theme-primary:hover { color: var(--color-primary) !important; }
.hover\\:theme-primary-bg:hover { background-color: var(--color-primary) !important; opacity: 0.9; }
.group:hover .group-hover\\:theme-primary { color: var(--color-primary) !important; }
.group:hover .group-hover\\:theme-primary-border { border-color: var(--color-primary) !important; }

/* ── Storefront design layer ────────────────────────────────────────────
   Shared button system, card elevation and typographic refinements used
   across the storefront (homepage, product page). */
.ref-theme { -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }

.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: .75rem;
  padding: 1rem 2rem; border-radius: 1rem;
  font-weight: 600; font-size: 1rem; line-height: 1.2;
  cursor: pointer; border: none; transition: transform .2s ease, opacity .15s ease, box-shadow .15s ease, background-color .2s ease;
}
.btn:disabled { cursor: not-allowed; }
.btn:hover:not(:disabled) { transform: scale(1.05); }
.btn:active:not(:disabled) { transform: scale(0.95); }
.btn--primary { background-color: var(--color-primary); color: #fff; box-shadow: 0 8px 20px -6px rgba(var(--color-primary-rgb), .55); }
.btn--primary:hover:not(:disabled) { opacity: .92; }
/* Neutral-by-default, brand-color-on-hover — matches the reference's product
   card buttons (dark by default, not flooded in the tenant's accent color;
   the color only appears as feedback when you actually interact). */
.btn--dark { background-color: #111827; color: #fff; }
.btn--dark:hover:not(:disabled) { background-color: var(--color-primary); }
.btn--compact { padding: .625rem 1.25rem; font-size: .875rem; border-radius: .75rem; }
.full { width: 100%; }

/* Hero: dark, moody base (not a flat flood of the tenant's brand color) with
   the brand color used sparingly as a glow accent — a saturated color filling
   the whole hero reads as generic/promo-ish; a near-black base with the same
   color as a highlight reads as premium regardless of which hue a tenant
   picks. The slide photo, if any, is a separate floating/contained element
   positioned by the page template, not a full-bleed background. */
.hero-banner-section {
  background: linear-gradient(135deg, #0b0f1a 0%, #161b2e 100%);
}
.hero-banner-section::before {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background-image: radial-gradient(circle at 12% 25%, rgba(var(--color-primary-rgb), .45), transparent 45%),
                     radial-gradient(circle at 90% 85%, rgba(var(--color-primary-rgb), .28), transparent 50%);
}

.product-single__title { letter-spacing: -0.01em; line-height: 1.15; }
.text-money { font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }

/* Paired display face for headings/logo — independent of the tenant's chosen
   body font (theme_font), the same way the .btn/.product-card system is a
   fixed design layer rather than a per-tenant setting. */
.font-display { font-family: 'Space Grotesk', var(--font-main), sans-serif; letter-spacing: -0.02em; }
.brand-mark { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); }

/* Subtle dot-grid texture for dark/gradient sections (hero, banner) */
.dot-grid-overlay {
  background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1px);
  background-size: 28px 28px;
}

/* Top scrolling trust strip */
.marquee-bar { background: linear-gradient(to right, var(--color-primary-dark), var(--color-primary)); }
.marquee-track { animation: marquee-scroll 22s linear infinite; }
.marquee-bar:hover .marquee-track { animation-play-state: paused; }
@keyframes marquee-scroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
@media (prefers-reduced-motion: reduce) {
  .marquee-track { animation: none; }
}

/* Product cards get a soft, layered shadow instead of a flat border-only look */
.product-card { box-shadow: var(--shadow-card); transition: box-shadow .2s ease, transform .2s ease; }
.product-card:hover { box-shadow: var(--shadow-card-hover); transform: translateY(-2px); }

${customCss}
`.trim()
})

// Inject theme CSS into <head> dynamically (can't use <style> tag in Vue template)
const applyThemeCSS = () => {
  let el = document.getElementById('tenant-theme')
  if (!el) {
    el = document.createElement('style')
    el.id = 'tenant-theme'
    document.head.appendChild(el)
  }
  el.textContent = themeCSS.value
}
watch(themeCSS, applyThemeCSS)

const hasGoogleReviews = computed(() => !!tenant.value?.google_place_id)

const shopAvailable = computed(() => {
  if (!tenant.value) return true
  return !tenant.value.orders_paused && !tenant.value.vacation_mode
})

const isActive = (path) => {
  const url = page.url || ''
  if (path === '/') return url === '/' || url === ''
  return url.startsWith(path)
}
</script>
