<template>
  <Head :title="t('pages.landing.a_a_cloud_platform_for_running', { a: appName })">
    <meta name="description" :content="t('pages.landing.a_is_a_modern_saas_platform', { a: appName })" />
    <meta name="robots" content="index, follow" />
    <meta property="og:type" content="website" />
    <meta property="og:title" :content="t('pages.landing.a_a_platform_for_building_online', { a: appName })" />
    <meta property="og:description" :content="t('pages.landing.a_modern_saas_platform_for_running')" />
    <meta property="og:locale" content="pl_PL" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" :content="t('pages.landing.a_a_platform_for_building_online', { a: appName })" />
  </Head>

  <div class="min-h-screen bg-white font-sans">
    <!-- Nav -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
          <img src="/images/logo.png" alt="Logo" class="h-8 w-auto" />
          <span class="text-2xl font-extrabold text-blue-700">{{ appName }}</span>
        </div>
        <div class="hidden md:flex items-center gap-8 text-sm text-gray-600">
          <a href="#funkcje" class="hover:text-blue-700 transition-colors">{{ t('pages.landing.features') }}</a>
          <a href="#jak-dziala" class="hover:text-blue-700 transition-colors">{{ t('pages.landing.how_it_works') }}</a>
          <a href="#cennik" class="hover:text-blue-700 transition-colors">{{ t('pages.landing.pricing') }}</a>
          <a href="#kontakt" class="hover:text-blue-700 transition-colors">{{ t('layout.clientlayout.contact') }}</a>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section
      class="bg-gradient-to-br from-blue-700 via-blue-800 to-blue-950 text-white py-28 px-4 overflow-hidden relative"
    >
      <div
        class="absolute inset-0 opacity-10"
        style="
          background-image:
            radial-gradient(circle at 20% 80%, white 1px, transparent 1px),
            radial-gradient(circle at 80% 20%, white 1px, transparent 1px);
          background-size: 60px 60px;
        "
      ></div>
      <div class="max-w-5xl mx-auto text-center relative">
        <div
          class="inline-block bg-blue-500/40 text-blue-100 text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-widest border border-blue-400/30"
        >
          {{ t('pages.landing.a_complete_saas_platform_for_online') }}
        </div>
        <h1 class="text-5xl md:text-6xl font-extrabold mb-6 leading-tight">
          {{ t('pages.landing.your_online_shop') }}<br />
          <span class="text-blue-200">w jednym systemie</span>
        </h1>
        <p class="text-xl text-blue-100 mb-6 max-w-3xl mx-auto leading-relaxed">
          {{ t('common.a_is_an_all_round_shop_platform', { a: appName }) }}
        </p>
        <p class="text-blue-200 text-base mb-10">{{ t('pages.landing.running_in_minutes_no_technical_knowledge') }}</p>

        <div class="flex flex-wrap justify-center gap-8 mt-8 text-blue-100 text-sm">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-check text-green-300 text-lg"></i> {{ t('pages.landing.unlimited_orders') }}
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-check text-green-300 text-lg"></i> {{ t('pages.landing.several_payment_gateways') }}
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-check text-green-300 text-lg"></i> {{ t('pages.landing.your_own_shop_subdomain') }}
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-check text-green-300 text-lg"></i> {{ t('pages.landing.updates_included') }}
          </div>
        </div>
      </div>
    </section>

    <!-- Stats bar -->
    <section class="bg-blue-600 text-white py-6 px-4">
      <div class="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div>
          <div class="text-3xl font-extrabold">30+</div>
          <div class="text-blue-200 text-sm mt-1">{{ t('pages.landing.system_modules') }}</div>
        </div>
        <div>
          <div class="text-3xl font-extrabold">24/7</div>
          <div class="text-blue-200 text-sm mt-1">{{ t('pages.landing.access_for_customers') }}</div>
        </div>
        <div>
          <div class="text-3xl font-extrabold">4</div>
          <div class="text-blue-200 text-sm mt-1">{{ t('pages.landing.payment_gateways') }}</div>
        </div>
        <div>
          <div class="text-3xl font-extrabold">100%</div>
          <div class="text-blue-200 text-sm mt-1">{{ t('pages.landing.in_the_cloud_nothing_to_install') }}</div>
        </div>
      </div>
    </section>

    <!-- Main Features -->
    <section id="funkcje" class="py-24 px-4 bg-white">
      <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-4">
          {{ t('pages.landing.everything_your_shop_needs') }}
        </h2>
        <p class="text-center text-gray-500 mb-16 max-w-2xl mx-auto">
          {{ t('pages.landing.a_complete_toolkit_built_for_e') }}
        </p>

        <div class="grid md:grid-cols-3 gap-8">
          <div
            v-for="f in mainFeatures"
            :key="f.title"
            class="bg-gray-50 rounded-2xl p-7 hover:shadow-md transition-shadow border border-gray-100"
          >
            <div class="text-4xl mb-4" v-html="f.icon"></div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ f.title }}</h3>
            <p class="text-gray-500 text-sm leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Deep dive: Order management -->
    <section class="bg-blue-50 py-24 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div>
            <div class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-3">
              {{ t('pages.landing.orders_and_payments') }}
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-5">
              {{ t('pages.landing.full_control_over_every_order') }}
            </h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
              {{ t('pages.landing.from_the_moment_a_customer_places') }}
            </p>
            <ul class="space-y-3 text-sm text-gray-700">
              <li v-for="item in orderFeatures" :key="item" class="flex items-start gap-3">
                <i class="fa-solid fa-check text-blue-500 font-bold mt-0.5"></i>{{ item }}
              </li>
            </ul>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="card in orderCards"
              :key="card.label"
              class="bg-white rounded-xl p-5 shadow-sm border border-blue-100"
            >
              <div class="text-3xl mb-3" v-html="card.icon"></div>
              <div class="font-semibold text-gray-800 text-sm">{{ card.label }}</div>
              <div class="text-xs text-gray-400 mt-1">{{ card.sub }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Staff roles section -->
    <section class="py-24 px-4 bg-white">
      <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">
          {{ t('pages.landing.a_dedicated_panel_for_every_role') }}
        </h2>
        <p class="text-center text-gray-500 mb-14 max-w-xl mx-auto">
          {{ t('pages.landing.everyone_sees_only_what_they_need') }}
        </p>
        <div class="grid md:grid-cols-4 gap-6">
          <div
            v-for="role in roles"
            :key="role.name"
            class="text-center p-6 rounded-2xl border-2 hover:border-blue-400 transition-colors"
            :class="role.borderClass"
          >
            <div class="text-4xl mb-3" v-html="role.icon"></div>
            <div class="font-bold text-gray-900 text-base mb-2">{{ role.name }}</div>
            <div class="text-sm text-gray-500 leading-relaxed">{{ role.desc }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Marketing & Loyalty -->
    <section class="bg-gray-50 py-24 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div class="grid grid-cols-2 gap-4 order-2 md:order-1">
            <div
              v-for="card in loyaltyCards"
              :key="card.label"
              class="bg-white rounded-xl p-5 shadow-sm border border-gray-100"
            >
              <div class="text-3xl mb-3" v-html="card.icon"></div>
              <div class="font-semibold text-gray-800 text-sm">{{ card.label }}</div>
              <div class="text-xs text-gray-400 mt-1">{{ card.sub }}</div>
            </div>
          </div>
          <div class="order-1 md:order-2">
            <div class="text-xs font-bold text-purple-600 uppercase tracking-widest mb-3">
              {{ t('pages.landing.loyalty_and_marketing') }}
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-5">
              {{ t('pages.landing.build_a_base_of_regular_customers') }}
            </h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
              {{ t('pages.landing.a_built_in_loyalty_programme_with') }}
            </p>
            <ul class="space-y-3 text-sm text-gray-700">
              <li v-for="item in loyaltyFeatures" :key="item" class="flex items-start gap-3">
                <i class="fa-solid fa-check text-purple-500 font-bold mt-0.5"></i>{{ item }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section id="jak-dziala" class="py-24 px-4 bg-white">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">
          {{ t('common.how_does_a_work', { a: appName }) }}
        </h2>
        <p class="text-center text-gray-500 mb-16 max-w-xl mx-auto">
          {{ t('pages.landing.from_signing_up_to_your_first') }}
        </p>

        <div class="grid md:grid-cols-3 gap-8">
          <div v-for="(step, i) in steps" :key="step.title" class="text-center">
            <div
              class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-extrabold mx-auto mb-5"
            >
              {{ i + 1 }}
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-3">{{ step.title }}</h3>
            <p class="text-gray-500 text-sm leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Technical features -->
    <section class="bg-gray-900 text-white py-24 px-4">
      <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-4">{{ t('pages.landing.technical_architecture') }}</h2>
        <p class="text-center text-gray-400 mb-14 max-w-xl mx-auto">
          {{ t('pages.landing.solid_architecture_data_security_and_tenant') }}
        </p>
        <div class="grid md:grid-cols-4 gap-6">
          <div v-for="t in techFeatures" :key="t.title" class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
            <div class="text-3xl mb-4" v-html="t.icon"></div>
            <div class="font-semibold text-white mb-2">{{ t.title }}</div>
            <div class="text-sm text-gray-400 leading-relaxed">{{ t.desc }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing -->
    <section id="cennik" class="py-24 px-4 bg-white">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">
          {{ t('pages.landing.simple_honest_pricing') }}
        </h2>
        <p class="text-center text-gray-500 mb-14">{{ t('pages.landing.full_access_to_the_system_no') }}</p>

        <div class="max-w-lg mx-auto bg-white rounded-3xl shadow-2xl border-2 border-blue-500 overflow-hidden">
          <div class="bg-blue-600 text-white text-center py-6 px-8">
            <div class="text-xs font-bold uppercase tracking-widest text-blue-200 mb-2">
              {{ t('pages.landing.full_access') }}
            </div>
            <div class="text-6xl font-extrabold">300</div>
            <div class="text-blue-200 text-sm mt-1">{{ t('pages.landing.pln_year_nbsp_nbsp_that_is') }}</div>
          </div>
          <div class="p-8">
            <div class="grid grid-cols-2 gap-2 mb-8">
              <div v-for="item in pricingFeatures" :key="item" class="flex items-start gap-2 text-sm text-gray-700">
                <i class="fa-solid fa-check text-green-500 font-bold shrink-0 mt-0.5"></i>
                <span>{{ item }}</span>
              </div>
            </div>
            <div class="border-t border-gray-100 pt-6 text-center text-xs text-gray-400">
              {{ t('pages.landing.which_edition_you_get_and_so') }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact / CTA -->
    <section id="kontakt" class="bg-blue-700 py-24 px-4">
      <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-white text-center mb-3">
          {{ t('pages.landing.interested_in_the_platform') }}
        </h2>
        <p class="text-blue-100 text-center mb-10 text-lg">
          {{ t('pages.landing.write_to_us_we_reply_within') }}
        </p>

        <!-- Success message -->
        <div v-if="contactSuccess" class="bg-green-500 text-white rounded-xl p-5 text-center mb-6 font-semibold">
          <i class="fa-solid fa-check mr-1 text-white"></i> {{ t('pages.landing.message_sent_we_will_be_in') }}
        </div>

        <!-- Contact Form -->
        <form v-else @submit.prevent="submitContact" class="bg-white rounded-2xl shadow-xl p-8 space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="Jan Kowalski"
              />
              <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('pages.landing.email') }}</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                :placeholder="t('common.john_shop_com')"
              />
              <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="+48 600 000 000"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('pages.landing.subject') }}</label>
              <input
                v-model="form.subject"
                type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                :placeholder="t('pages.landing.e_g_platform_demo')"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('pages.landing.message') }}</label>
            <textarea
              v-model="form.message"
              required
              rows="4"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
              :placeholder="t('pages.landing.describe_what_your_shop_needs')"
            ></textarea>
            <p v-if="errors.message" class="text-red-500 text-xs mt-1">{{ errors.message }}</p>
          </div>
          <button
            type="submit"
            :disabled="sending"
            class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold text-base rounded-xl transition-colors"
          >
            {{ sending ? t('common.sending') : t('common.send_message') }}
          </button>
        </form>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-10 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="flex items-center gap-2">
            <img src="/images/logo.png" alt="Logo" class="h-6 w-auto brightness-0 invert" />
            <span class="text-lg font-bold text-white">{{ appName }}</span>
          </div>
          <div class="text-sm text-center text-gray-500">
            {{ t('pages.landing.a_cloud_platform_for_running_an') }}
          </div>
          <div class="text-sm">
            &copy; {{ new Date().getFullYear() }} {{ t('common.all_rights_reserved', { a: appName }) }}
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Head, usePage, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const page = usePage()
const appName = computed(() => page.props.app_name ?? 'Storelo')
const ga4Id = import.meta.env.VITE_GA4_ID || null

// Analytics is loaded from script, not written into <Head> through v-html.
// A <component :is="'script'"> inside <Head> only runs because Inertia
// happens to re-insert it; injecting it here is what every other tag on this
// project already does, and it is legible.
onMounted(() => {
  if (!ga4Id) return

  const loader = document.createElement('script')
  loader.async = true
  loader.src = `https://www.googletagmanager.com/gtag/js?id=${ga4Id}`
  document.head.appendChild(loader)

  window.dataLayer = window.dataLayer || []
  const gtag = (...args) => window.dataLayer.push(args)
  gtag('js', new Date())
  gtag('config', ga4Id)
})

const props = defineProps({
  plans: { type: Array, default: () => [] },
})

// Contact form
const contactSuccess = computed(() => !!page.props.flash?.contact_success)
const sending = ref(false)
const form = useForm({ name: '', email: '', phone: '', subject: '', message: '' })
const errors = computed(() => form.errors)

const submitContact = () => {
  sending.value = true
  form.post(route('contact.store'), {
    onFinish: () => {
      sending.value = false
    },
  })
}

const mainFeatures = [
  {
    icon: '<i class="fa-solid fa-box-open text-blue-500"></i>',
    title: t('common.product_management'),
    desc: t('common.categories_products_variants_by_size_and'),
  },
  {
    icon: '<i class="fa-solid fa-credit-card text-green-600"></i>',
    title: t('manager.settings.index.payment_gateways'),
    desc: t('common.przelewy24_payu_and_tpay_integrations_with'),
  },
  {
    icon: '<i class="fa-solid fa-truck text-blue-600"></i>',
    title: t('common.orders_and_delivery'),
    desc: t('common.online_orders_with_a_full_status'),
  },
  {
    icon: '<i class="fa-solid fa-warehouse text-slate-600"></i>',
    title: t('common.warehouse_and_stock_taking'),
    desc: t('common.stock_across_several_warehouses_with_adjustments'),
  },
  {
    icon: '<i class="fa-solid fa-star text-yellow-400"></i>',
    title: t('common.loyalty_programme'),
    desc: t('common.points_for_every_purchase_four_tiers'),
  },
  {
    icon: '<i class="fa-solid fa-chart-bar text-indigo-500"></i>',
    title: t('pages.landing.reports_and_statistics'),
    desc: t('common.revenue_charts_over_30_days_popular'),
  },
  {
    icon: '<i class="fa-solid fa-tag text-pink-500"></i>',
    title: t('pages.landing.promotions_and_discounts'),
    desc: t('common.discount_codes_flash_sales_bundles_and'),
  },
  {
    icon: '<i class="fa-solid fa-envelope text-blue-500"></i>',
    title: t('pages.landing.email_marketing'),
    desc: t('common.email_campaigns_with_customer_segments_and'),
  },
  {
    icon: '<i class="fa-solid fa-rotate-left text-orange-500"></i>',
    title: t('pages.landing.returns_rma'),
    desc: t('common.a_returns_and_complaints_module_customers'),
  },
  {
    icon: '<i class="fa-solid fa-gift text-red-500"></i>',
    title: t('pages.landing.gift_cards'),
    desc: t('common.gift_cards_of_any_value_customers'),
  },
  {
    icon: '<i class="fa-solid fa-lock text-red-500"></i>',
    title: t('common.access_control'),
    desc: t('common.permissions_by_role_each_role_sees'),
  },
  {
    icon: '<i class="fa-solid fa-shield-halved text-blue-600"></i>',
    title: t('common.security'),
    desc: t('common.passwords_and_api_keys_encrypted_https'),
  },
]

const orderFeatures = [
  t('landing.order_queue_numbering'),
  t('landing.websocket_orders'),
  t('landing.status_tracking'),
  t('landing.email_on_status'),
  t('landing.codes_deducted'),
  t('landing.many_payment_methods'),
  t('landing.courier_integration'),
  t('landing.holiday_mode'),
]

const orderCards = [
  {
    icon: '<i class="fa-solid fa-bolt text-yellow-400"></i>',
    label: t('pages.landing.real_time'),
    sub: t('pages.landing.websockets_reverb'),
  },
  {
    icon: '<i class="fa-solid fa-credit-card text-green-500"></i>',
    label: t('common.online_payments'),
    sub: 'P24 / PayU / Tpay',
  },
  {
    icon: '<i class="fa-solid fa-box text-blue-500"></i>',
    label: t('common.parcel_tracking'),
    sub: t('common.tracking_number_on_the_order'),
  },
  {
    icon: '<i class="fa-solid fa-file-lines text-indigo-500"></i>',
    label: t('client.account.order_history'),
    sub: t('pages.landing.filtering_and_csv_export'),
  },
]

const roles = [
  {
    icon: '<i class="fa-solid fa-user-tie text-blue-600"></i>',
    name: 'Manager',
    desc: t('common.full_access_products_orders_staff_reports'),
    borderClass: 'border-blue-200 bg-blue-50',
  },
  {
    icon: '<i class="fa-solid fa-people-carry-box text-orange-500"></i>',
    name: t('pages.landing.staff_member'),
    desc: t('common.fulfilling_orders_moving_statuses_along_handling'),
    borderClass: 'border-orange-200 bg-orange-50',
  },
  {
    icon: '<i class="fa-solid fa-warehouse text-slate-600"></i>',
    name: t('pages.landing.warehouse_staff'),
    desc: t('common.stock_levels_adjustments_several_warehouses_and'),
    borderClass: 'border-slate-200 bg-slate-50',
  },
  {
    icon: '<i class="fa-solid fa-user text-green-600"></i>',
    name: t('common.customer'),
    desc: t('common.signing_up_purchase_history_the_loyalty'),
    borderClass: 'border-green-200 bg-green-50',
  },
]

const loyaltyCards = [
  {
    icon: '<i class="fa-solid fa-medal text-amber-700"></i>',
    label: t('common.bronze_tier'),
    sub: t('common.where_new_customers_start'),
  },
  {
    icon: '<i class="fa-solid fa-award text-gray-400"></i>',
    label: t('pages.landing.silver_tier'),
    sub: t('common.a_discount_once_the_threshold_is'),
  },
  {
    icon: '<i class="fa-solid fa-trophy text-yellow-400"></i>',
    label: t('common.gold_tier'),
    sub: t('common.better_rewards_and_priority'),
  },
  {
    icon: '<i class="fa-solid fa-gem text-blue-400"></i>',
    label: t('pages.landing.platinum_tier'),
    sub: t('common.exclusive_vip_perks'),
  },
]

const loyaltyFeatures = [
  t('landing.points_per_purchase'),
  t('landing.reward_thresholds'),
  t('landing.email_campaigns'),
  'HMAC-bezpieczny link wypisania z listy mailingowej',
  t('landing.csv_export'),
  t('landing.purchase_history'),
]

const steps = [
  {
    title: t('pages.landing.shop_registration'),
    desc: t('common.an_administrator_creates_your_shop_s'),
  },
  {
    title: t('pages.landing.install_wizard'),
    desc: t('common.three_steps_set_up_the_shop'),
  },
  {
    title: t('pages.landing.sell_and_earn'),
    desc: t('common.you_add_products_set_up_the'),
  },
]

const techFeatures = [
  {
    icon: '<i class="fa-solid fa-bolt text-yellow-400"></i>',
    title: t('pages.landing.websockets_reverb'),
    desc: t('common.orders_and_statuses_update_live_with'),
  },
  {
    icon: '<i class="fa-solid fa-box text-indigo-500"></i>',
    title: 'Multi-tenant SaaS',
    desc: t('common.every_shop_has_its_own_database'),
  },
  {
    icon: '<i class="fa-solid fa-mobile-screen text-green-500"></i>',
    title: t('pages.landing.pwa_mobile'),
    desc: t('common.the_app_is_a_progressive_web'),
  },
  {
    icon: '<i class="fa-solid fa-lock text-red-500"></i>',
    title: t('pages.landing.encryption'),
    desc: t('common.gateway_api_keys_and_passwords_are'),
  },
]

const pricingFeatures = [
  t('landing.unlimited_orders'),
  t('landing.manager_and_staff_panel'),
  t('landing.own_subdomain'),
  t('landing.loyalty_four_tiers'),
  'Powiadomienia e-mail',
  'Kody rabatowe i promocje',
  'Raporty i eksport CSV',
  'Karty podarunkowe',
  'Marketing e-mailowy',
  t('landing.rma_module'),
  t('landing.warehouse_management'),
  t('landing.flash_sales'),
  'Kreator instalacji',
  'System wsparcia',
  'Aktualizacje w cenie',
  'Wsparcie techniczne',
]
</script>
