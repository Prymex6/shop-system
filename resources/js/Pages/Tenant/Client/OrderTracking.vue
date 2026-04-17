<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-blue-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b sticky top-0 z-10">
      <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <a :href="route('tenant.shop')" class="text-indigo-600 hover:text-indigo-700 font-medium">
          {{ t('common.back_to_the_shop') }}
        </a>
        <h1 class="text-xl font-bold text-gray-900">{{ t('client.ordertracking.order_tracking') }}</h1>
      </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
      <!-- Status Card -->
      <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <div class="text-center mb-6">
          <p class="text-gray-600 text-sm mb-1">{{ t('client.ordertracking.order_number') }}</p>
          <h2 class="text-3xl font-bold text-gray-900">#{{ localOrder.order_number }}</h2>
          <p class="text-xs text-gray-400 mt-1">{{ formatDate(localOrder.created_at) }}</p>
        </div>

        <!-- Big icon -->
        <div class="flex justify-center mb-5">
          <div
            class="w-24 h-24 rounded-full flex items-center justify-center text-5xl"
            :class="{
              'bg-yellow-100': ['pending', 'confirmed'].includes(localOrder.status),
              'bg-blue-100': ['paid', 'processing', 'shipped'].includes(localOrder.status),
              'bg-green-100': localOrder.status === 'delivered',
              'bg-red-100': localOrder.status === 'cancelled',
              'bg-indigo-100': localOrder.status === 'refunded',
            }"
          >
            {{ statusIcon }}
          </div>
        </div>

        <div class="text-center">
          <h3 class="text-2xl font-bold text-gray-900">{{ statusTitle }}</h3>
          <p class="text-gray-500 mt-1">{{ statusDesc }}</p>
        </div>

        <!-- Payment alert -->
        <div
          v-if="localOrder.payment_status === 'awaiting_payment'"
          class="mt-5 bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center"
        >
          <p class="text-yellow-800 font-medium">{{ t('client.ordertracking.waiting_for_the_payment_to_be') }}</p>
          <p class="text-yellow-600 text-sm mt-1">{{ t('client.ordertracking.once_the_payment_clears_the_order') }}</p>
        </div>

        <!-- Tracking number -->
        <div v-if="localOrder.tracking_number" class="mt-5 bg-indigo-50 border border-indigo-100 rounded-xl p-4">
          <p class="text-sm text-indigo-800 font-medium">
            {{ t('client.ordertracking.tracking_number') }}
            <span class="font-mono">{{ localOrder.tracking_number }}</span>
          </p>
          <p v-if="localOrder.tracking_carrier" class="text-xs text-indigo-600 mt-0.5">
            Kurier: {{ localOrder.tracking_carrier }}
          </p>
        </div>
      </div>

      <!-- Progress Timeline -->
      <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-6">{{ t('common.fulfilment_status') }}</h3>
        <div class="space-y-5">
          <div v-for="(step, idx) in timelineSteps" :key="step.key" class="flex items-start gap-4">
            <div class="flex flex-col items-center">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 font-semibold"
                :class="step.reached ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
              >
                <i v-if="step.reached" class="fa-solid fa-check"></i>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <div
                v-if="idx < timelineSteps.length - 1"
                class="w-0.5 h-6 mt-1"
                :class="step.reached ? 'bg-green-300' : 'bg-gray-200'"
              ></div>
            </div>
            <div class="pt-1">
              <p class="font-semibold text-gray-900">{{ step.label }}</p>
              <p class="text-sm text-gray-500">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Details -->
      <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ t('client.ordertracking.order_details') }}</h3>

        <!-- Parcel locker -->
        <div v-if="localOrder.pickup_point_code" class="mb-4 pb-4 border-b">
          <p class="text-sm text-gray-500 mb-1">{{ t('client.ordertracking.parcel_locker') }}</p>
          <p class="font-medium text-gray-900">
            <span class="font-mono">{{ localOrder.pickup_point_code }}</span>
            <span v-if="localOrder.pickup_point_data" class="block text-gray-600 font-normal">
              {{ localOrder.pickup_point_data.street }}, {{ localOrder.pickup_point_data.postCode }}
              {{ localOrder.pickup_point_data.city }}
            </span>
          </p>
        </div>

        <!-- Shipping address -->
        <div v-if="localOrder.shipping_address && !localOrder.pickup_point_code" class="mb-4 pb-4 border-b">
          <p class="text-sm text-gray-500 mb-1">{{ t('common.delivery_address') }}</p>
          <p class="font-medium text-gray-900">
            {{ localOrder.shipping_address.street }}, {{ localOrder.shipping_address.postcode }}
            {{ localOrder.shipping_address.city }}
          </p>
        </div>

        <!-- Shipping method -->
        <div v-if="localOrder.shipping_method" class="mb-4 pb-4 border-b">
          <p class="text-sm text-gray-500 mb-1">{{ t('common.delivery_method') }}</p>
          <p class="font-medium text-gray-900">{{ localOrder.shipping_method.name }}</p>
        </div>

        <!-- Items -->
        <div class="mb-4 pb-4 border-b">
          <p class="text-sm text-gray-500 mb-1">{{ t('client.ordertracking.products_ordered') }}</p>
          <p v-if="isPartiallyShipped" class="text-xs text-indigo-600 mb-2">
            {{ t('client.ordertracking.some_items_have_already_shipped_the') }}
          </p>
          <div class="space-y-2">
            <div v-for="item in localOrder.items" :key="item.id" class="flex justify-between text-sm">
              <div>
                <span class="font-medium text-gray-900">{{ item.quantity }}× {{ item.name }}</span>
                <span v-if="item.variant_label" class="text-gray-400 ml-1">({{ item.variant_label }})</span>
                <span
                  v-if="item.product_type === 'digital'"
                  class="ml-1 text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded"
                  >{{ t('client.cartsidebar.digital') }}</span
                >
                <span
                  v-else-if="itemStatusLabel(item.fulfillment_status)"
                  class="ml-1 text-xs px-1.5 py-0.5 rounded"
                  :class="itemStatusClass(item.fulfillment_status)"
                >
                  {{ itemStatusLabel(item.fulfillment_status) }}
                </span>
              </div>
              <span class="font-semibold text-gray-800">{{ formatPrice(item.price * item.quantity) }}</span>
            </div>
          </div>
        </div>

        <!-- Totals -->
        <div class="space-y-2 text-sm">
          <div class="flex justify-between text-gray-600">
            <span>{{ t('common.products') }}</span>
            <span>{{ formatPrice(localOrder.subtotal) }}</span>
          </div>
          <div v-if="localOrder.shipping_cost > 0" class="flex justify-between text-gray-600">
            <span>{{ t('common.delivery') }}</span>
            <span>{{ formatPrice(localOrder.shipping_cost) }}</span>
          </div>
          <div v-if="localOrder.discount > 0" class="flex justify-between text-green-600">
            <span>{{ t('common.discount') }}</span>
            <span>−{{ formatPrice(localOrder.discount) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
            <span>{{ t('common.total') }}</span>
            <span>{{ formatPrice(localOrder.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Digital downloads -->
      <div v-if="localOrder.download_links?.length" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ t('client.ordertracking.digital_downloads') }}</h3>
        <div class="space-y-3">
          <div
            v-for="link in localOrder.download_links"
            :key="link.id"
            class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl"
          >
            <div>
              <p class="font-medium text-indigo-900">{{ link.file?.name }}</p>
              <p class="text-xs text-indigo-500">
                {{ t('common.downloads_a_of_b', { a: link.download_count, b: link.max_downloads ?? '∞' }) }}
              </p>
            </div>
            <a
              :href="route('tenant.download', link.token)"
              class="theme-primary-bg hover:opacity-90 text-white text-sm px-4 py-2 rounded-xl font-semibold transition"
              >{{ t('common.download') }}</a
            >
          </div>
        </div>
      </div>

      <!-- Post-purchase upsell -->
      <div v-if="upsellProducts.length" class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ t('client.ordertracking.you_might_also_like') }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ t('client.ordertracking.add_it_to_the_cart_and') }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div v-for="p in upsellProducts" :key="p.id" class="text-center">
            <img
              v-if="p.image"
              :src="'/storage/' + p.image"
              :alt="p.name"
              class="w-full aspect-square object-cover rounded-xl mb-2"
            />
            <div
              v-else
              class="w-full aspect-square rounded-xl bg-gray-100 flex items-center justify-center text-3xl mb-2"
            >
              📦
            </div>
            <p class="text-sm font-medium text-gray-900 line-clamp-2 leading-tight">{{ p.name }}</p>
            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ formatPrice(p.price) }}</p>
            <button
              @click="buyAgain(p)"
              class="mt-2 w-full text-xs font-semibold theme-primary-bg hover:opacity-90 text-white rounded-lg py-1.5"
            >
              {{ t('common.add_to_cart') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Return request -->
      <div
        v-if="localOrder.fulfillment_status === 'delivered'"
        class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 text-center"
      >
        <p class="text-gray-700 mb-3">{{ t('client.ordertracking.want_to_return_something_from_this') }}</p>
        <a
          :href="rmaCreateLink"
          class="inline-block bg-white border border-gray-300 hover:border-gray-400 text-gray-700 text-sm px-5 py-2.5 rounded-xl font-semibold transition"
        >
          <i class="fa-solid fa-rotate-left mr-1"></i> {{ t('common.request_a_return') }}
        </a>
      </div>

      <!-- Contact -->
      <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 text-center">
        <p class="text-gray-700 mb-1">{{ t('client.ordertracking.questions_about_your_order') }}</p>
        <p class="text-sm text-gray-500">{{ t('client.ordertracking.get_in_touch') }}</p>
        <a
          v-if="shopPhone"
          :href="'tel:' + shopPhone"
          class="inline-block mt-2 text-xl font-bold text-indigo-600 hover:text-indigo-700"
        >
          <i class="fa-solid fa-phone mr-1"></i> {{ shopPhone }}
        </a>
        <a v-if="shopEmail" :href="'mailto:' + shopEmail" class="block mt-1 text-sm text-indigo-500 hover:underline">
          {{ shopEmail }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/cartStore'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  order: Object,
  upsellProducts: { type: Array, default: () => [] },
})

const page = usePage()
const shopPhone = computed(() => page.props.tenant?.phone ?? null)
const shopEmail = computed(() => page.props.tenant?.email ?? null)
const cartStore = useCartStore()

// Not literally "one click, no re-payment" — the configured gateways
// (Przelewy24/PayU/Tpay) are redirect-based with no saved-card/off-session
// charge support, so there's no way to bill an existing order again without
// the customer re-entering payment. This starts a fresh cart with the
// suggested product and goes straight to checkout instead.
function buyAgain(product) {
  cartStore.add({ product, quantity: 1 })
  router.visit(route('tenant.checkout'))
}

const localOrder = ref({ ...props.order })

// Guests reach this page via a signed tracking_token in the URL (no session
// tying them to the order) — carry it forward so the RMA form can authorize
// them the same way, instead of requiring a customer login they don't have.
const trackingToken = new URLSearchParams(window.location.search).get('token')
const rmaCreateLink = computed(() => {
  const base = route('tenant.rma.create', localOrder.value.order_number)
  return trackingToken ? `${base}?token=${trackingToken}` : base
})

let pollInterval = null

// 'completed' is not a real order status value (the enum is pending/
// confirmed/paid/processing/shipped/delivered/cancelled/refunded) — with
// only 'cancelled'/'refunded' as stop conditions, a normally successful
// order stuck at 'delivered' polled every 30s indefinitely for as long as
// this tab stayed open.
const isFinalStatus = (status) => ['delivered', 'cancelled', 'refunded'].includes(status)

onMounted(() => {
  if (!isFinalStatus(localOrder.value.status)) {
    pollInterval = setInterval(async () => {
      try {
        const res = await fetch(route('tenant.order.status', localOrder.value.order_number))
        const data = await res.json()
        if (data.status) localOrder.value.status = data.status
        if (data.fulfillment_status) localOrder.value.fulfillment_status = data.fulfillment_status
        if (data.tracking_number) localOrder.value.tracking_number = data.tracking_number
        if (isFinalStatus(data.status)) clearInterval(pollInterval)
      } catch {
        // The next poll is thirty seconds away; the page keeps what it has.
      }
    }, 30000)
  }

  trackConversion()
})

onUnmounted(() => clearInterval(pollInterval))

// Ad-platform Purchase/CompletePayment events — the confirmation page is the
// only real "thank you page" in the app, but previously sent nothing to any
// ad platform, so TikTok/Meta campaigns could never optimize for or report
// actual sales. Gated by the same GDPR marketing consent CookieConsent.vue
// already requires before loading fbq/ttq, and deduped per order_number so
// a page refresh or later revisit doesn't double-count the conversion.
const trackConversion = () => {
  const order = localOrder.value
  if (!order?.order_number) return

  const dedupeKey = `conversion_tracked_${order.order_number}`
  if (sessionStorage.getItem(dedupeKey)) return

  const contentIds = (order.items ?? []).filter((i) => i.product_id).map((i) => String(i.product_id))
  let tracked = false

  if (typeof window.fbq === 'function') {
    window.fbq('track', 'Purchase', {
      value: order.total,
      currency: order.currency ?? 'PLN',
      content_ids: contentIds,
      content_type: 'product',
      num_items: order.items?.length ?? 1,
    })
    tracked = true
  }

  if (typeof window.ttq === 'object' && typeof window.ttq.track === 'function') {
    window.ttq.track('CompletePayment', {
      value: order.total,
      currency: order.currency ?? 'PLN',
      contents: contentIds.map((id) => ({ content_id: id, content_type: 'product' })),
    })
    tracked = true
  }

  if (tracked) sessionStorage.setItem(dedupeKey, '1')
}

const statusIcon = computed(() => {
  // Previously kept its own status vocabulary (pending/awaiting_payment/
  // paid/completed) that never matched the real orders.status enum
  // (pending/confirmed/paid/processing/shipped/delivered/cancelled/
  // refunded) — every status past "paid" fell through to the generic
  // fallback, so a shipped or delivered order showed a vague "Przetwarzanie"
  // instead of its real state.
  const map = {
    pending: '⏳',
    confirmed: '✅',
    paid: '💳',
    processing: '📦',
    shipped: '🚚',
    delivered: '🎉',
    cancelled: '❌',
    refunded: '↩️',
  }
  return map[localOrder.value.status] ?? '📦'
})

const statusTitle = computed(() => {
  const map = {
    pending: t('common.order_placed'),
    confirmed: t('common.order_confirmed'),
    paid: t('common.paid_being_fulfilled'),
    processing: t('client.ordertracking.being_fulfilled'),
    shipped: t('common.order_dispatched'),
    delivered: t('common.order_delivered'),
    cancelled: t('common.order_cancelled'),
    refunded: t('common.refund'),
  }
  return map[localOrder.value.status] ?? t('client.ordertracking.processing')
})

const statusDesc = computed(() => {
  const map = {
    pending: t('common.your_order_has_been_placed_and'),
    confirmed: t('common.the_shop_has_confirmed_your_order'),
    paid: t('common.payment_confirmed_we_are_getting_your'),
    processing: t('common.we_are_picking_your_order'),
    shipped: t('common.your_parcel_is_on_its_way'),
    delivered: t('common.the_order_has_been_delivered_thank'),
    cancelled: t('common.the_order_has_been_cancelled'),
    refunded: t('common.the_money_has_been_refunded_to'),
  }
  return map[localOrder.value.status] ?? ''
})

const statusOrder = ['pending', 'confirmed', 'paid', 'processing', 'shipped', 'delivered']

const timelineSteps = computed(() => {
  const currentIdx = statusOrder.indexOf(localOrder.value.status)
  return [
    {
      key: 'pending',
      label: t('common.order_placed'),
      desc: t('common.your_order_is_in_the_system'),
      threshold: 'pending',
    },
    { key: 'paid', label: t('manager.orders.index.paid_2'), desc: t('common.payment_confirmed'), threshold: 'paid' },
    {
      key: 'processing',
      label: t('client.ordertracking.being_fulfilled'),
      desc: t('common.we_are_picking_your_order'),
      threshold: 'processing',
    },
    {
      key: 'delivered',
      label: t('manager.orders.index.delivered'),
      desc: t('common.order_delivered_thank_you'),
      threshold: 'delivered',
    },
  ].map((step) => ({
    ...step,
    reached: currentIdx >= statusOrder.indexOf(step.threshold),
  }))
})

// Order.fulfillment_status alone can't express "shipped 2 of 3 items" — it's
// now a derived aggregate of the items' own statuses, so a partial shipment
// shows as "processing" overall. Surface the per-item detail here instead of
// letting that read as if nothing had shipped yet.
const physicalItemStatuses = computed(() =>
  (localOrder.value.items ?? []).filter((i) => i.product_type !== 'digital').map((i) => i.fulfillment_status),
)

const isPartiallyShipped = computed(() => {
  const statuses = physicalItemStatuses.value
  const shippedOrLater = statuses.filter((s) => ['shipped', 'delivered'].includes(s)).length
  return shippedOrLater > 0 && shippedOrLater < statuses.length
})

function itemStatusLabel(status) {
  return (
    {
      processing: t('client.ordertracking.being_fulfilled'),
      shipped: t('manager.abandonedcarts.index.sent'),
      delivered: t('client.ordertracking.delivered'),
      cancelled: t('client.ordertracking.cancelled'),
    }[status] ?? null
  )
}

function itemStatusClass(status) {
  return {
    'bg-blue-100 text-blue-700': status === 'processing',
    'bg-green-100 text-green-700': status === 'shipped' || status === 'delivered',
    'bg-red-100 text-red-700': status === 'cancelled',
  }
}

function formatDate(dt) {
  return new Date(dt).toLocaleString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}
</script>
