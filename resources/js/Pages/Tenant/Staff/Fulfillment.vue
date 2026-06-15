<template>
  <StaffLayout :title="t('common.order_fulfilment')">
    <div class="space-y-6">
      <h1 class="text-3xl font-bold text-gray-900">{{ t('common.order_fulfilment') }}</h1>

      <div v-if="orders.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <div v-for="order in orders" :key="order.id" class="bg-white rounded-2xl border border-gray-100 p-5">
          <!-- Order header -->
          <div class="flex items-center justify-between mb-3">
            <div>
              <p class="font-bold text-gray-900">{{ order.order_number }}</p>
              <p class="text-xs text-gray-400">{{ formatDate(order.created_at) }}</p>
            </div>
            <span
              class="text-xs font-semibold px-2 py-1 rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-700': order.fulfillment_status === 'unfulfilled',
                'bg-blue-100 text-blue-700': order.fulfillment_status === 'processing',
              }"
            >
              {{ statusLabel(order.fulfillment_status) }}
            </span>
          </div>

          <!-- Items — checkboxes only show up once there's more than one
               physical item, so a partial shipment can be selected without
               cluttering the common single-item case. -->
          <div class="space-y-1 mb-4">
            <label
              v-for="item in order.items"
              :key="item.id"
              class="flex items-center justify-between text-sm"
              :class="physicalItems(order).length > 1 && item.product_type !== 'digital' ? 'cursor-pointer' : ''"
            >
              <span class="flex items-center gap-2 text-gray-700">
                <input
                  v-if="physicalItems(order).length > 1 && item.product_type !== 'digital'"
                  type="checkbox"
                  v-model="selected[order.id]"
                  :value="item.id"
                  class="rounded border-gray-300"
                />
                {{ item.quantity }}× {{ item.name }}
                <span
                  v-if="item.product_type === 'digital'"
                  class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded"
                  >cyfrowy</span
                >
                <span
                  v-else-if="item.fulfillment_status && item.fulfillment_status !== 'unfulfilled'"
                  class="text-xs px-1.5 py-0.5 rounded"
                  :class="{
                    'bg-blue-100 text-blue-700': item.fulfillment_status === 'processing',
                    'bg-green-100 text-green-700':
                      item.fulfillment_status === 'shipped' || item.fulfillment_status === 'delivered',
                  }"
                  >{{ itemStatusLabel(item.fulfillment_status) }}</span
                >
              </span>
              <span class="text-gray-500 text-xs" v-if="item.variant_label">{{ item.variant_label }}</span>
            </label>
          </div>

          <!-- Customer & Shipping -->
          <div class="text-xs text-gray-500 space-y-0.5 mb-4">
            <p>👤 {{ order.customer_name }}</p>
            <p v-if="order.shipping_method">🚚 {{ order.shipping_method.name }}</p>
            <p v-if="order.shipping_address">
              📍 {{ order.shipping_address.street }}, {{ order.shipping_address.city }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex gap-2">
            <button
              v-if="order.fulfillment_status === 'unfulfilled'"
              :disabled="!selected[order.id]?.length"
              @click="updateStatus(order, 'processing')"
              class="flex-1 bg-blue-50 text-blue-700 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-blue-100 transition disabled:opacity-40 disabled:cursor-not-allowed"
            >
              ▶ Zacznij{{ partialLabel(order) }}
            </button>
            <button
              v-if="order.fulfillment_status !== 'unfulfilled'"
              :disabled="!selected[order.id]?.length"
              @click="updateStatus(order, 'shipped')"
              class="flex-1 bg-green-50 text-green-700 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-green-100 transition disabled:opacity-40 disabled:cursor-not-allowed"
            >
              {{ t('common.dispatched') }}{{ partialLabel(order) }}
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20 text-gray-400">
        <p class="text-5xl mb-4">📦</p>
        <p class="text-lg font-medium">{{ t('staff.fulfillment.no_orders_to_fulfil') }}</p>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  orders: { type: Array, default: () => [] },
})

function physicalItems(order) {
  return (order.items ?? []).filter((i) => i.product_type !== 'digital')
}

// Every physical item starts selected — the common case (whole order ships
// together) stays a single click; unchecking items is how a partial
// shipment ("2 of 3") gets expressed.
const selected = reactive({})
function resetSelection() {
  for (const order of props.orders) {
    selected[order.id] = physicalItems(order).map((i) => i.id)
  }
}
resetSelection()
watch(() => props.orders, resetSelection)

function partialLabel(order) {
  const all = physicalItems(order).length
  const chosen = selected[order.id]?.length ?? 0
  return chosen > 0 && chosen < all ? ` (${chosen}/${all})` : ''
}

function statusLabel(status) {
  return (
    {
      unfulfilled: t('manager.orders.index.pending'),
      processing: t('staff.fulfillment.in_progress'),
      shipped: t('manager.orders.index.sent'),
    }[status] ?? status
  )
}

function itemStatusLabel(status) {
  return (
    { processing: 'w trakcie', shipped: t('common.sent_2'), delivered: 'dostarczono', cancelled: 'anulowano' }[
      status
    ] ?? status
  )
}

function formatDate(dt) {
  return new Date(dt).toLocaleString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function updateStatus(order, status) {
  const itemIds = selected[order.id] ?? []
  router.patch(
    route('tenant.staff.fulfillment.update-status', order.id),
    {
      fulfillment_status: status,
      item_ids: itemIds.length && itemIds.length < physicalItems(order).length ? itemIds : undefined,
    },
    { preserveScroll: true },
  )
}
</script>
