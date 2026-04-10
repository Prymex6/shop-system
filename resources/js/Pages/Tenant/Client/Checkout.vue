<template>
  <Head :title="t('client.checkout.checkout')" />
  <ClientLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ t('client.checkout.completing_the_order') }}</h1>

      <!-- Empty cart -->
      <div v-if="cartStore.items.length === 0" class="text-center py-16 bg-white rounded-2xl shadow">
        <div class="text-6xl mb-4">🛒</div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">{{ t('common.your_cart_is_empty') }}</h2>
        <p class="text-gray-600 mb-6">{{ t('client.checkout.add_products_to_the_cart_to') }}</p>
        <a
          :href="route('tenant.shop')"
          class="inline-block theme-primary-bg hover:opacity-90 text-white px-6 py-3 rounded-xl font-semibold"
        >
          {{ t('common.go_to_the_shop') }}
        </a>
      </div>

      <form v-else @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Contact details -->
          <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">{{ t('client.checkout.contact_details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label for="checkout_customer_name" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('common.full_name_2')
                }}</label>
                <input
                  id="checkout_customer_name"
                  v-model="form.customer_name"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                />
              </div>
              <div>
                <label for="checkout_customer_email" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('common.email_2')
                }}</label>
                <input
                  id="checkout_customer_email"
                  v-model="form.customer_email"
                  @blur="onEmailEntered"
                  type="email"
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                />
              </div>
              <div>
                <label for="checkout_customer_phone" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('common.phone')
                }}</label>
                <input
                  id="checkout_customer_phone"
                  v-model="form.customer_phone"
                  type="tel"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                />
              </div>
            </div>
          </div>

          <!-- Shipping method (only for physical items) -->
          <div v-if="cartStore.hasPhysicalItems" class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">{{ t('common.delivery_method') }}</h2>
            <div class="space-y-3">
              <label
                v-for="method in shippingMethods"
                :key="method.id"
                class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all"
                :class="{
                  'border-indigo-500 bg-indigo-50': form.shipping_method_id === method.id,
                  'border-gray-200 hover:border-indigo-200': form.shipping_method_id !== method.id,
                }"
              >
                <input v-model="form.shipping_method_id" type="radio" :value="method.id" class="accent-indigo-600" />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-900">{{ method.name }}</span>
                    <span
                      class="text-xs px-2 py-0.5 rounded-full"
                      :class="{
                        'bg-blue-100 text-blue-700': method.type === 'standard',
                        'bg-orange-100 text-orange-700': method.type === 'express',
                        'bg-green-100 text-green-700': method.type === 'pickup',
                      }"
                      >{{ typeLabel(method.type) }}</span
                    >
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ method.delivery_days_min }}–{{ method.delivery_days_max }} dni roboczych
                  </p>
                </div>
                <div class="text-right">
                  <span v-if="effectiveShippingCost(method) === 0" class="text-green-600 font-semibold text-sm">{{
                    t('client.checkout.free')
                  }}</span>
                  <span v-else class="font-semibold text-gray-900">{{
                    formatPrice(effectiveShippingCost(method))
                  }}</span>
                  <p v-if="method.free_from && cartStore.subtotal < method.free_from" class="text-xs text-gray-400">
                    Darmowa od {{ formatPrice(method.free_from) }}
                  </p>
                </div>
              </label>
              <p v-if="!shippingMethods.length" class="text-gray-400 text-sm">
                {{ t('client.checkout.no_delivery_methods_available') }}
              </p>
            </div>
          </div>

          <!-- Parcel locker (only for carrier-delivered methods) -->
          <PickupPointPicker
            v-if="needsPickupPoint"
            v-model="pickupPoint"
            :shipping-method-id="form.shipping_method_id"
            :city="form.shipping_address.city"
          />

          <!-- Shipping address -->
          <div
            v-if="cartStore.hasPhysicalItems && selectedShippingMethod?.type !== 'pickup'"
            class="bg-white rounded-2xl shadow p-6"
          >
            <h2 class="text-xl font-semibold mb-4">{{ t('common.delivery_address') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="!needsPickupPoint" class="md:col-span-2">
                <label for="checkout_street" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('client.checkout.street_and_number')
                }}</label>
                <input
                  id="checkout_street"
                  v-model="form.shipping_address.street"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                />
              </div>
              <div>
                <label for="checkout_postcode" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('client.checkout.postcode')
                }}</label>
                <input
                  id="checkout_postcode"
                  v-model="form.shipping_address.postcode"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  placeholder="00-000"
                />
              </div>
              <div>
                <label for="checkout_city" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('client.checkout.city')
                }}</label>
                <input
                  id="checkout_city"
                  v-model="form.shipping_address.city"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                />
              </div>
              <div class="md:col-span-2">
                <label for="checkout_country" class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('common.country')
                }}</label>
                <input
                  id="checkout_country"
                  v-model="form.shipping_address.country"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  :placeholder="t('client.checkout.poland')"
                />
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">{{ t('client.checkout.additional_information') }}</h2>
            <textarea
              v-model="form.notes"
              rows="3"
              :placeholder="t('client.checkout.order_notes_optional')"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent resize-none"
            ></textarea>
          </div>

          <!-- Payment method -->
          <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">{{ t('common.payment_method') }}</h2>
            <div class="space-y-3">
              <label
                v-for="method in paymentMethods"
                :key="method.value"
                class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all"
                :class="{
                  'border-indigo-500 bg-indigo-50': form.payment_method === method.value,
                  'border-gray-200 hover:border-indigo-200': form.payment_method !== method.value,
                }"
              >
                <input v-model="form.payment_method" type="radio" :value="method.value" class="accent-indigo-600" />
                <div class="flex-1">
                  <p class="font-semibold text-gray-900">{{ method.label }}</p>
                  <p v-if="method.description" class="text-xs text-gray-500">{{ method.description }}</p>
                </div>
                <span
                  v-if="method.type === 'online'"
                  class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium"
                  >Online</span
                >
              </label>
              <p v-if="!paymentMethods.length" class="text-gray-400 text-sm">
                {{ t('client.checkout.no_payment_methods_available') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Right column: Order summary -->
        <div class="space-y-4">
          <div class="bg-white rounded-2xl shadow p-6 sticky top-4">
            <h2 class="text-xl font-semibold mb-4">{{ t('client.checkout.your_order') }}</h2>

            <!-- Items -->
            <div class="space-y-3 mb-4">
              <div v-for="item in cartStore.items" :key="item.key" class="flex justify-between text-sm">
                <div class="flex-1 pr-2">
                  <p class="font-medium text-gray-900">{{ item.product.name }}</p>
                  <p class="text-gray-500 text-xs">{{ item.variantLabel }} × {{ item.quantity }}</p>
                </div>
                <span class="font-semibold whitespace-nowrap">{{ formatPrice(item.price * item.quantity) }}</span>
              </div>
            </div>

            <div class="border-t pt-4 space-y-2 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>{{ t('common.products') }}</span>
                <span>{{ formatPrice(cartStore.subtotal) }}</span>
              </div>
              <div v-if="cartStore.hasPhysicalItems" class="flex justify-between text-gray-600">
                <span>{{ t('common.delivery') }}</span>
                <span>{{ selectedShippingCost !== null ? formatPrice(selectedShippingCost) : '—' }}</span>
              </div>
              <div v-if="discountAmount > 0" class="flex justify-between text-green-600 font-medium">
                <span>{{ t('common.discount') }}</span>
                <span>−{{ formatPrice(discountAmount) }}</span>
              </div>
            </div>

            <!-- Discount code -->
            <div class="border-t pt-4 mt-2">
              <div class="flex gap-2">
                <input
                  v-model="discountCode"
                  type="text"
                  :placeholder="t('client.checkout.discount_code')"
                  :aria-label="t('client.checkout.discount_code')"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  :disabled="!!appliedDiscount"
                  @keydown.enter.prevent="applyDiscount"
                />
                <button
                  v-if="!appliedDiscount"
                  type="button"
                  @click="applyDiscount"
                  class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-sm font-medium"
                >
                  {{ t('client.checkout.apply') }}
                </button>
                <button
                  v-else
                  type="button"
                  @click="removeDiscount"
                  class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-xl text-sm font-medium"
                >
                  {{ t('common.delete') }}
                </button>
              </div>
              <p v-if="discountError" class="text-red-500 text-xs mt-1" role="alert" aria-live="assertive">
                {{ discountError }}
              </p>
              <p v-if="appliedDiscount" class="text-green-600 text-xs mt-1" role="status" aria-live="polite">
                ✓ Rabat {{ formatPrice(discountAmount) }} zastosowany
              </p>
            </div>

            <!-- Gift card -->
            <div class="border-t pt-4 mt-2">
              <div class="flex gap-2">
                <input
                  v-model="giftCardCode"
                  type="text"
                  :placeholder="t('client.checkout.gift_card')"
                  :aria-label="t('client.checkout.gift_card')"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  :disabled="!!appliedGiftCard"
                  @keydown.enter.prevent="applyGiftCard"
                />
                <button
                  v-if="!appliedGiftCard"
                  type="button"
                  @click="applyGiftCard"
                  class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-sm font-medium"
                >
                  {{ t('client.checkout.apply') }}
                </button>
                <button
                  v-else
                  type="button"
                  @click="removeGiftCard"
                  class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-xl text-sm font-medium"
                >
                  {{ t('common.delete') }}
                </button>
              </div>
              <p v-if="giftCardError" class="text-red-500 text-xs mt-1" role="alert" aria-live="assertive">
                {{ giftCardError }}
              </p>
              <p v-if="appliedGiftCard" class="text-green-600 text-xs mt-1" role="status" aria-live="polite">
                ✓ Karta pokryje {{ formatPrice(giftCardAmount) }}
              </p>
            </div>

            <div v-if="giftCardAmount > 0" class="flex justify-between text-green-600 font-medium text-sm px-0">
              <span>{{ t('client.checkout.gift_card') }}</span>
              <span>−{{ formatPrice(giftCardAmount) }}</span>
            </div>

            <!-- Total -->
            <div class="border-t pt-4 mt-4 flex justify-between items-center">
              <span class="text-lg font-bold text-gray-900">{{ t('common.total') }}</span>
              <span class="text-2xl font-bold text-indigo-600">{{ formatPrice(orderTotal) }}</span>
            </div>
            <p v-if="isForeignCurrency" class="text-xs text-gray-400 mt-1">
              {{ t('client.checkout.you_will_be_charged_in_pln') }}
            </p>

            <!-- Digital info -->
            <div
              v-if="cartStore.hasDigitalItems"
              class="mt-4 bg-purple-50 border border-purple-100 rounded-xl p-3 text-xs text-purple-700"
            >
              {{ t('client.checkout.download_links_for_digital_products_are') }}
            </div>

            <!-- Terms -->
            <label class="flex items-start gap-2 mt-4 cursor-pointer">
              <input v-model="form.terms_accepted" type="checkbox" class="mt-1 accent-indigo-600" required />
              <span class="text-xs text-gray-600">
                {{ t('common.i_accept') }}
                <a :href="route('tenant.pages.terms')" target="_blank" class="text-indigo-600 underline">regulamin</a> i
                <a :href="route('tenant.pages.privacy')" target="_blank" class="text-indigo-600 underline">{{
                  t('common.the_privacy_policy')
                }}</a>
                *
              </span>
            </label>

            <!-- Order bump -->
            <label
              v-if="orderBumpProduct && !orderBumpAlreadyInCart"
              class="mt-4 flex items-start gap-3 p-3 border-2 border-dashed border-amber-300 bg-amber-50 rounded-xl cursor-pointer"
            >
              <input
                type="checkbox"
                v-model="orderBumpChecked"
                @change="onOrderBumpToggle"
                class="mt-1 accent-amber-600"
              />
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">
                  {{ t('common.add_as_well_a', { a: orderBumpProduct.name }) }}
                </p>
                <p class="text-xs text-gray-600 mt-0.5">
                  {{ t('common.just_a_one_click', { a: formatPrice(orderBumpProduct.price) }) }}
                </p>
              </div>
              <img
                v-if="orderBumpProduct.image"
                :src="'/storage/' + orderBumpProduct.image"
                :alt="orderBumpProduct.name"
                class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
              />
            </label>

            <!-- Error message -->
            <div
              v-if="errorMessage"
              class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"
              role="alert"
              aria-live="assertive"
            >
              {{ errorMessage }}
            </div>
            <p v-if="cartStore.hasPhysicalItems && !form.shipping_method_id" class="mt-4 text-xs text-amber-600">
              {{ t('client.checkout.choose_a_delivery_method_to_place') }}
            </p>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="
                isSubmitting || !form.payment_method || (cartStore.hasPhysicalItems && !form.shipping_method_id)
              "
              class="w-full mt-4 theme-primary-bg hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed text-white py-4 rounded-xl text-lg font-semibold transition-colors"
            >
              <span v-if="isSubmitting"
                ><i class="fa-solid fa-spinner fa-spin mr-2"></i>{{ t('client.checkout.processing') }}</span
              >
              <span v-else>{{ t('client.checkout.place_the_order') }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import PickupPointPicker from '@/Components/Client/PickupPointPicker.vue'
import { useCartStore } from '@/Stores/cartStore'
import { useCartTrackingStore } from '@/Stores/cartTrackingStore'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  shippingMethods: { type: Array, default: () => [] },
  paymentMethods: { type: Array, default: () => [] },
  loyaltyOptions: { type: Object, default: null },
  orderBumpProduct: { type: Object, default: null },
})

const cartStore = useCartStore()

// Prices browse in the customer's chosen currency (useCurrency), but payment
// here is always actually charged in PLN — showing a converted total at the
// moment of payment would misrepresent what's actually billed, so this page
// intentionally keeps formatPrice() in plain PLN and just discloses that.
const page = usePage()
const isForeignCurrency = computed(() => (page.props.current_currency ?? 'PLN') !== 'PLN')
const cartTracking = useCartTrackingStore()

// cartTrackingStore.setEmail() existed but was never called from anywhere —
// abandoned-cart reminder emails need an email to send to, and for guest
// checkout (the majority of traffic from anonymous TikTok-ad clicks) this
// was the only place one is ever typed before a cart could be "abandoned".
// Also immediately re-tracks with the now-known email instead of waiting
// for the next add/remove-from-cart action, which may never happen again
// if the customer leaves right after typing their email.
function onEmailEntered() {
  const email = form.customer_email?.trim()
  if (!email) return

  cartTracking.setEmail(email)
  cartTracking.track({
    email,
    items: cartStore.items.map((item) => ({
      product_id: item.product.id,
      variant_id: item.variant?.id ?? null,
      name: item.product.name,
      quantity: item.quantity,
      price: item.price,
    })),
  })
}

const form = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_method_id: null,
  shipping_address: {
    street: '',
    city: '',
    postcode: '',
    country: t('client.checkout.poland'),
  },
  billing_address: null,
  payment_method: '',
  notes: '',
  terms_accepted: false,
  discount_code: '',
  gift_card_code: '',
})

const discountCode = ref('')
const appliedDiscount = ref(null)
const discountAmount = ref(0)
const discountError = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

const giftCardCode = ref('')
const appliedGiftCard = ref(null)
const giftCardAmount = ref(0)
const giftCardError = ref('')

// Pre-fill from auth if available
const auth = window?.__page?.props?.auth
if (auth?.customer) {
  form.customer_name = auth.customer.name ?? ''
  form.customer_email = auth.customer.email ?? ''
  form.customer_phone = auth.customer.phone ?? ''
}

const pickupPoint = ref(null)

const selectedShippingMethod = computed(
  () => props.shippingMethods.find((m) => m.id === form.shipping_method_id) ?? null,
)

// Only a method fulfilled by a carrier has lockers to pick from; everything
// else is delivered however the shop delivers it.
const needsPickupPoint = computed(() => Boolean(selectedShippingMethod.value?.carrier))

const freeShippingThreshold = computed(() => Number(page.props.tenant?.free_shipping_threshold ?? 0))

const effectiveShippingCost = (method) => {
  if (freeShippingThreshold.value > 0 && cartStore.subtotal >= freeShippingThreshold.value) return 0
  if (method.free_from && cartStore.subtotal >= method.free_from) return 0
  return parseFloat(method.price ?? 0)
}

const selectedShippingCost = computed(() => {
  if (!selectedShippingMethod.value) return null
  return effectiveShippingCost(selectedShippingMethod.value)
})

// Order bump: a merchant-picked product offered as a one-click add right
// before payment. It's just a normal cart item under the hood — checking
// the box calls the same cartStore.add() the shop page uses, so it prices,
// taxes, and validates through the exact same server-side pipeline as
// everything else in the cart. If the customer already has this product in
// their cart from normal shopping, the offer doesn't make sense — hide it
// rather than risk a confusing double-add.
const orderBumpAlreadyInCart = computed(
  () => props.orderBumpProduct && cartStore.items.some((item) => item.product.id === props.orderBumpProduct.id),
)
const orderBumpChecked = ref(false)

function onOrderBumpToggle() {
  if (!props.orderBumpProduct) return
  if (orderBumpChecked.value) {
    cartStore.add({ product: props.orderBumpProduct, quantity: 1 })
  } else {
    const item = cartStore.items.find((i) => i.product.id === props.orderBumpProduct.id)
    if (item) cartStore.remove(item.id)
  }
}

const orderTotal = computed(() => {
  const base = cartStore.subtotal
  const shipping = cartStore.hasPhysicalItems && selectedShippingCost.value !== null ? selectedShippingCost.value : 0
  return Math.max(0, base + shipping - discountAmount.value - giftCardAmount.value)
})

function typeLabel(type) {
  return (
    {
      standard: t('client.checkout.standard'),
      express: t('client.checkout.express'),
      pickup: t('manager.shipping.index.collection_in_person'),
      digital: t('client.checkout.digital'),
    }[type] ?? type
  )
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

async function applyDiscount() {
  if (!discountCode.value.trim()) return
  discountError.value = ''
  try {
    const res = await axios.post(route('tenant.checkout.validate-discount'), {
      code: discountCode.value.trim(),
      subtotal: cartStore.subtotal,
    })
    appliedDiscount.value = res.data.discount
    discountAmount.value = res.data.discount.amount
    form.discount_code = discountCode.value.trim()
  } catch (e) {
    discountError.value = e.response?.data?.message ?? t('common.that_code_is_not_right')
  }
}

function removeDiscount() {
  appliedDiscount.value = null
  discountAmount.value = 0
  discountCode.value = ''
  form.discount_code = ''
}

async function applyGiftCard() {
  if (!giftCardCode.value.trim()) return
  giftCardError.value = ''
  try {
    const res = await axios.post(route('tenant.checkout.gift-card'), {
      code: giftCardCode.value.trim(),
      total: orderTotal.value,
    })
    appliedGiftCard.value = res.data
    giftCardAmount.value = res.data.amount
    form.gift_card_code = res.data.code
  } catch (e) {
    giftCardError.value = e.response?.data?.message ?? t('common.that_code_is_not_right')
  }
}

function removeGiftCard() {
  appliedGiftCard.value = null
  giftCardAmount.value = 0
  giftCardCode.value = ''
  form.gift_card_code = ''
}

async function submitOrder() {
  if (isSubmitting.value) return
  isSubmitting.value = true
  errorMessage.value = ''

  try {
    if (needsPickupPoint.value && !pickupPoint.value?.code) {
      errorMessage.value = t('common.choose_the_parcel_locker_the_delivery')
      isSubmitting.value = false
      return
    }

    const payload = {
      ...form,
      items: cartStore.getCheckoutData().items,
      pickup_point_code: needsPickupPoint.value ? pickupPoint.value.code : null,
      pickup_point_data: needsPickupPoint.value ? pickupPoint.value.data : null,
    }

    if (appliedDiscount.value) {
      payload.discount_code = appliedDiscount.value.code
    }

    const res = await axios.post(route('tenant.checkout.store'), payload)

    // Mark the tracked cart converted so it doesn't trigger a "you left
    // something in your cart" email for an order that already went through.
    cartTracking.convert()

    if (res.data.redirect_to_payment) {
      cartStore.clearCart()
      window.location.href = res.data.payment_url
      return
    }

    cartStore.clearCart()
    window.location.href = route('tenant.order.tracking', res.data.order_number) + '?token=' + res.data.tracking_token
  } catch (e) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      errorMessage.value = Object.values(e.response.data.errors).flat().join(' ')
    } else {
      errorMessage.value = e.response?.data?.message ?? t('common.something_went_wrong_try_again')
    }
    isSubmitting.value = false
  }
}
</script>
