<template>
  <ManagerLayout :title="t('manager.orders.show.order_a', { a: order.order_number })">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <Link
            :href="route('tenant.manager.orders.index')"
            class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2"
          >
            {{ t('manager.orders.show.back_to_the_order_list') }}
          </Link>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('common.order_hash_a', { a: order.order_number }) }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ formatDate(order.created_at) }}</p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusClass(order.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
            {{ statusLabel(order.status) }}
          </span>
          <!-- Faktura VAT – tylko wersja testowa -->
          <a
            v-if="$page.props.app_version === 'test'"
            :href="route('tenant.manager.orders.invoice', order.order_number)"
            target="_blank"
            class="px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-50 border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors"
          >
            {{ t('manager.orders.show.vat_invoice') }}
          </a>
        </div>
      </div>

      <!-- Status update -->
      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-3">{{ t('manager.orders.show.change_the_status') }}</h2>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="s in availableStatuses"
            :key="s.value"
            @click="updateStatus(s.value)"
            :disabled="order.status === s.value || updating"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
              order.status === s.value
                ? 'bg-gray-200 text-gray-500 cursor-default'
                : 'bg-white border border-gray-300 hover:border-gray-400 text-gray-700',
            ]"
          >
            {{ s.label }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer info -->
        <div class="bg-white rounded-lg shadow p-5">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('manager.manualordermodal.customer_details') }}</h2>
          <dl class="space-y-2 text-sm">
            <div class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.full_name') }}</dt>
              <dd class="font-medium">{{ order.customer_name }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.phone') }}</dt>
              <dd>
                <a :href="`tel:${order.customer_phone}`" class="text-blue-600 hover:underline">{{
                  order.customer_phone
                }}</a>
              </dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.email') }}</dt>
              <dd>
                <a :href="`mailto:${order.customer_email}`" class="text-blue-600 hover:underline">{{
                  order.customer_email
                }}</a>
              </dd>
            </div>
            <div v-if="order.shipping_address" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.address') }}</dt>
              <dd>
                {{ order.shipping_address?.street }}, {{ order.shipping_address?.postcode }}
                {{ order.shipping_address?.city }}
              </dd>
            </div>
            <div v-if="order.pickup_point_code" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('client.ordertracking.parcel_locker') }}</dt>
              <dd>
                <span class="font-mono font-semibold">{{ order.pickup_point_code }}</span>
                <span v-if="order.pickup_point_data" class="block text-gray-500">
                  {{ order.pickup_point_data.street }}, {{ order.pickup_point_data.postCode }}
                  {{ order.pickup_point_data.city }}
                </span>
              </dd>
            </div>
            <div v-if="order.notes" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('manager.orders.show.notes') }}</dt>
              <dd class="text-yellow-700 bg-yellow-50 px-2 py-1 rounded">{{ order.notes }}</dd>
            </div>
          </dl>
        </div>

        <!-- Order info -->
        <div class="bg-white rounded-lg shadow p-5">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('manager.orders.show.order_details') }}</h2>
          <dl class="space-y-2 text-sm">
            <div v-if="order.shipping_method" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.delivery_2') }}</dt>
              <dd class="font-medium">{{ order.shipping_method.name }}</dd>
            </div>
            <div v-if="order.fulfillment_status" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('manager.orders.index.fulfilment') }}</dt>
              <dd class="font-medium">{{ fulfillmentLabel(order.fulfillment_status) }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.payment') }}</dt>
              <dd>{{ paymentLabel(order.payment_method) }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('common.payment_status') }}</dt>
              <dd :class="order.payment_status === 'paid' ? 'text-green-700 font-semibold' : 'text-yellow-700'">
                {{ paymentStatusLabel(order.payment_status) }}
              </dd>
            </div>
            <div v-if="order.paid_at" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('manager.orders.show.paid_on') }}</dt>
              <dd>{{ formatDate(order.paid_at) }}</dd>
            </div>
            <div v-if="order.tracking_number" class="flex gap-2">
              <dt class="text-gray-500 w-28 shrink-0">{{ t('manager.orders.show.tracking_number') }}</dt>
              <dd class="font-mono text-sm">{{ order.tracking_number }}</dd>
            </div>
          </dl>

          <button
            v-if="canCreateLabel"
            type="button"
            class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-60"
            :disabled="creatingLabel"
            @click="createLabel"
          >
            {{ creatingLabel ? t('common.sending_4') : t('common.send_by_inpost') }}
          </button>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">{{ t('common.order_items') }}</h2>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="pb-2">{{ t('common.product') }}</th>
              <th class="pb-2 text-center">{{ t('common.quantity') }}</th>
              <th class="pb-2 text-right">{{ t('common.price') }}</th>
              <th class="pb-2 text-right">{{ t('manager.orders.show.total') }}</th>
              <th class="pb-2 text-right">{{ t('manager.orders.index.fulfilment') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in order.items" :key="item.id" class="py-2">
              <td class="py-2">
                <div class="font-medium">{{ item.name }}</div>
                <div v-if="item.variant_label" class="text-gray-500 text-xs">{{ item.variant_label }}</div>
                <span
                  v-if="item.product_type === 'digital'"
                  class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded"
                  >{{ t('client.cartsidebar.digital') }}</span
                >
              </td>
              <td class="py-2 text-center">{{ item.quantity }}</td>
              <td class="py-2 text-right">{{ formatMoney(item.price) }}</td>
              <td class="py-2 text-right font-medium">{{ formatMoney(item.price * item.quantity) }}</td>
              <td class="py-2 text-right">
                <span
                  v-if="item.product_type !== 'digital'"
                  class="text-xs px-1.5 py-0.5 rounded"
                  :class="{
                    'bg-gray-100 text-gray-500': item.fulfillment_status === 'unfulfilled',
                    'bg-blue-100 text-blue-700': item.fulfillment_status === 'processing',
                    'bg-green-100 text-green-700':
                      item.fulfillment_status === 'shipped' || item.fulfillment_status === 'delivered',
                    'bg-red-100 text-red-700': item.fulfillment_status === 'cancelled',
                  }"
                  >{{ itemFulfillmentLabel(item.fulfillment_status) }}</span
                >
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Totals -->
        <div class="mt-4 pt-4 border-t space-y-1 text-sm">
          <div class="flex justify-between text-gray-600">
            <span>{{ t('manager.orders.show.subtotal') }}</span>
            <span>{{ formatMoney(order.subtotal) }}</span>
          </div>
          <div v-if="order.shipping_cost > 0" class="flex justify-between text-gray-600">
            <span>{{ t('common.delivery') }}</span>
            <span>{{ formatMoney(order.shipping_cost) }}</span>
          </div>
          <div v-if="order.discount > 0" class="flex justify-between text-green-700">
            <span>{{ t('common.discount') }}</span>
            <span>-{{ formatMoney(order.discount) }}</span>
          </div>
          <div class="flex justify-between font-bold text-base pt-1 border-t">
            <span>{{ t('common.total') }}</span>
            <span>{{ formatMoney(order.total) }}</span>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  order: Object,
})

const page = usePage()
const updating = ref(false)
const creatingLabel = ref(false)

// A label can be bought once, for an order the customer picked a locker for.
const canCreateLabel = computed(() => Boolean(props.order.pickup_point_code) && !props.order.tracking_number)

function createLabel() {
  if (creatingLabel.value) return
  creatingLabel.value = true

  router.post(
    route('tenant.manager.orders.label.create', props.order.id),
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        creatingLabel.value = false
      },
    },
  )
}

onMounted(() => {
  const tenantId = page.props.tenant?.id
  if (window.Echo && tenantId) {
    window.Echo.private(`orders.${tenantId}`).listen('.order.status-changed', (e) => {
      if (e.order?.id === props.order.id) {
        router.reload({ only: ['order'], preserveScroll: true })
      }
    })
  }
})

const availableStatuses = [
  { value: 'pending', label: t('manager.orders.index.placed') },
  { value: 'awaiting_payment', label: t('manager.orders.index.awaiting_payment') },
  { value: 'paid', label: t('manager.orders.index.paid_2') },
  { value: 'completed', label: t('common.completed') },
  { value: 'cancelled', label: t('manager.orders.index.cancelled') },
  { value: 'refunded', label: t('manager.orders.index.refunded_2') },
]

function updateStatus(status) {
  if (updating.value) return
  updating.value = true
  router.patch(
    route('tenant.manager.orders.update-status', props.order.id),
    { status },
    {
      preserveScroll: true,
      onFinish: () => {
        updating.value = false
      },
    },
  )
}

function statusLabel(s) {
  return availableStatuses.find((x) => x.value === s)?.label ?? s
}

function statusClass(s) {
  const map = {
    pending: 'bg-gray-100 text-gray-700',
    awaiting_payment: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-900',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
  }
  return map[s] ?? 'bg-gray-100 text-gray-700'
}

function fulfillmentLabel(s) {
  return (
    {
      unfulfilled: t('manager.orders.index.pending'),
      processing: t('client.ordertracking.being_fulfilled'),
      shipped: t('manager.orders.index.sent'),
      delivered: t('manager.orders.index.delivered'),
    }[s] ?? s
  )
}

function itemFulfillmentLabel(s) {
  return (
    {
      unfulfilled: 'oczekuje',
      processing: 'w realizacji',
      shipped: t('common.sent_2'),
      delivered: 'dostarczono',
      cancelled: 'anulowano',
    }[s] ?? s
  )
}

function paymentLabel(m) {
  return (
    {
      przelewy24: 'Przelewy24',
      payu: 'PayU',
      tpay: 'Tpay',
      stripe: t('manager.orders.show.card_stripe'),
      bank_transfer: t('manager.manualordermodal.bank_transfer'),
      cash_on_delivery: t('common.cash_on_delivery'),
    }[m] ?? m
  )
}

function paymentStatusLabel(s) {
  return (
    {
      pending: t('manager.orders.index.pending'),
      awaiting_payment: t('manager.orders.index.awaiting_payment'),
      paid: t('manager.orders.index.paid_2'),
      failed: t('manager.orders.index.failed'),
      refunded: t('manager.orders.index.refunded'),
    }[s] ?? s
  )
}

function formatMoney(val) {
  return Number(val ?? 0).toFixed(2) + ' ' + t('common.currency_pln')
}

function formatDate(d) {
  return d ? new Date(d).toLocaleString(locale.value) : '–'
}
</script>
