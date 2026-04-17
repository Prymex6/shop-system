<template>
  <ManagerLayout :title="t('common.orders')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ t('common.orders') }}
            <span
              v-if="newOrdersCount > 0"
              class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-100 text-red-800"
            >
              {{ newOrdersCount }} nowych
            </span>
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ t('manager.orders.index.manage_the_shop_s_orders') }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="selectedOrders.length > 0"
            @click="openPickingList"
            class="bg-green-600 text-white px-3 py-2 rounded-md font-semibold hover:bg-green-700 transition text-sm flex items-center gap-2"
          >
            <i class="fa-solid fa-list-check"></i>
            Lista kompletacji ({{ selectedOrders.length }})
          </button>
          <button
            @click="showImportModal = true"
            class="bg-gray-100 text-gray-700 px-3 py-2 rounded-md font-semibold hover:bg-gray-200 transition text-sm flex items-center gap-2"
          >
            <i class="fa-solid fa-file-csv"></i>
            {{ t('manager.customers.index.import_csv') }}
          </button>
          <button
            @click="manualOrderOpen = true"
            class="bg-blue-600 text-white px-3 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm flex items-center gap-2"
          >
            <i class="fa-solid fa-plus"></i>
            {{ t('manager.orders.index.new_order') }}
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.status') }}</label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">{{ t('common.all') }}</option>
              <option value="pending">{{ t('manager.orders.index.placed') }}</option>
              <option value="awaiting_payment">{{ t('manager.orders.index.awaiting_payment') }}</option>
              <option value="paid">{{ t('manager.orders.index.paid_2') }}</option>
              <option value="completed">{{ t('common.completed') }}</option>
              <option value="cancelled">{{ t('manager.orders.index.cancelled') }}</option>
              <option value="refunded">{{ t('manager.orders.index.refunded_2') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.orders.index.fulfilment')
            }}</label>
            <select
              v-model="filters.fulfillment_status"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">{{ t('common.all') }}</option>
              <option value="unfulfilled">{{ t('manager.orders.index.pending') }}</option>
              <option value="processing">{{ t('client.ordertracking.being_fulfilled') }}</option>
              <option value="shipped">{{ t('manager.orders.index.sent') }}</option>
              <option value="delivered">{{ t('manager.orders.index.delivered') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.payment') }}</label>
            <select
              v-model="filters.payment_status"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">{{ t('common.all') }}</option>
              <option value="pending">{{ t('manager.orders.index.pending_2') }}</option>
              <option value="paid">{{ t('manager.orders.index.paid') }}</option>
              <option value="failed">{{ t('manager.orders.index.failed') }}</option>
              <option value="refunded">{{ t('manager.orders.index.refunded') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.search') }}</label>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              :placeholder="t('manager.orders.index.number_name_phone')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Orders List -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left">
                  <input
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 rounded border-gray-300"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.customers.show.number') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.customer') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.orders.index.fulfilment') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.payment_method') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.payment_status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.customers.show.amount') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.date') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                <td class="px-3 py-4 whitespace-nowrap">
                  <input
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 rounded border-gray-300"
                    :value="order.id"
                    v-model="selectedOrders"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">#{{ order.order_number }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ order.customer_name }}</div>
                  <div class="text-sm text-gray-500">{{ order.customer_phone }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="fulfillmentClass(order.fulfillment_status)"
                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ fulfillmentLabel(order.fulfillment_status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select
                    :value="order.status"
                    @change="updateStatus(order, $event.target.value)"
                    :class="statusClass(order.status)"
                    class="text-xs font-semibold rounded px-2 py-1 border-0 cursor-pointer"
                  >
                    <option value="pending">{{ t('manager.orders.index.placed') }}</option>
                    <option value="awaiting_payment">{{ t('manager.orders.index.awaiting_payment') }}</option>
                    <option value="paid">{{ t('manager.orders.index.paid_2') }}</option>
                    <option value="completed">{{ t('common.completed') }}</option>
                    <option value="cancelled">{{ t('manager.orders.index.cancelled') }}</option>
                    <option value="refunded">{{ t('manager.orders.index.refunded_2') }}</option>
                  </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-900">{{ paymentMethodLabel(order.payment_method) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select
                    :value="order.payment_status"
                    @change="updatePaymentStatus(order, $event.target.value)"
                    :class="paymentStatusClass(order.payment_status)"
                    class="text-xs font-semibold rounded px-2 py-1 border-0 cursor-pointer"
                  >
                    <option value="pending">{{ t('manager.orders.index.pending_2') }}</option>
                    <option value="paid">{{ t('manager.orders.index.paid') }}</option>
                    <option value="failed">{{ t('manager.orders.index.failed') }}</option>
                    <option value="refunded">{{ t('manager.orders.index.refunded') }}</option>
                    <option value="refund_failed">{{ t('manager.orders.index.refund_failed') }}</option>
                  </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatPrice(order.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div>{{ formatDate(order.created_at) }}</div>
                  <div class="text-xs text-gray-400">{{ timeAgo(order.created_at) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button @click="viewOrder(order)" class="text-blue-600 hover:text-blue-900">
                    {{ t('common.details') }}
                  </button>
                </td>
              </tr>

              <tr v-if="orders.data.length === 0">
                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                  {{ t('common.no_orders') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="orders.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ t('common.showing') }} <span class="font-medium">{{ orders.from }}</span> {{ t('common.to') }}
              <span class="font-medium">{{ orders.to }}</span> z <span class="font-medium">{{ orders.total }}</span>
              {{ t('common.results') }}
            </div>
            <div class="flex space-x-2">
              <template v-for="link in orders.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  ]"
                  v-html="link.label"
                ></Link>
                <span
                  v-else
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md opacity-50 cursor-not-allowed',
                  ]"
                  v-html="link.label"
                ></span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Import CSV Modal -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showImportModal = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('manager.orders.index.import_orders_from_csv') }}</h3>
          <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
            <span class="text-2xl">×</span>
          </button>
        </div>
        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm text-blue-700">
            {{ t('manager.orders.index.the_csv_file_should_have_these') }}
            <code class="font-mono text-xs">customer_name, customer_email, customer_phone, total, status</code>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.productimportmodal.csv_file')
            }}</label>
            <input
              type="file"
              accept=".csv"
              @change="importFile = $event.target.files[0]"
              class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md p-2"
            />
          </div>
          <div class="flex justify-end gap-3">
            <button
              @click="showImportModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              @click="handleImport"
              :disabled="!importFile || importing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
            >
              {{ importing ? t('common.importing') : 'Importuj' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div
      v-if="selectedOrder"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="selectedOrder = null"
    >
      <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white mb-10">
        <div class="space-y-4">
          <div class="flex justify-between items-start">
            <h3 class="text-lg font-medium text-gray-900">
              {{ t('common.order_hash_a', { a: selectedOrder.order_number }) }}
            </h3>
            <button @click="selectedOrder = null" class="text-gray-400 hover:text-gray-600">
              <span class="text-2xl">×</span>
            </button>
          </div>

          <!-- Customer Info -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 mb-2">{{ t('manager.manualordermodal.customer_details') }}</h4>
            <div class="space-y-1 text-sm">
              <p>
                <span class="text-gray-600">{{ t('manager.orders.index.first_name') }}</span>
                {{ selectedOrder.customer_name }}
              </p>
              <p>
                <span class="text-gray-600">{{ t('manager.orders.index.email') }}</span>
                {{ selectedOrder.customer_email || t('common.none') }}
              </p>
              <p>
                <span class="text-gray-600">{{ t('manager.orders.index.phone') }}</span>
                {{ selectedOrder.customer_phone }}
              </p>
              <p v-if="selectedOrder.shipping_address">
                <span class="text-gray-600">{{ t('manager.orders.index.address') }}</span>
                {{ selectedOrder.shipping_address?.street }}, {{ selectedOrder.shipping_address?.postcode }}
                {{ selectedOrder.shipping_address?.city }}
              </p>
              <p v-if="false"></p>
            </div>
          </div>

          <!-- Order Items -->
          <div>
            <h4 class="font-medium text-gray-900 mb-2">{{ t('common.order_items') }}</h4>
            <div class="space-y-2">
              <div
                v-for="item in selectedOrder.items"
                :key="item.id"
                class="flex justify-between items-start p-3 bg-gray-50 rounded"
              >
                <div class="flex-1">
                  <p class="font-medium text-gray-900">
                    {{ item.quantity }}x {{ item.name }}
                    <span v-if="item.variant_name" class="text-gray-600">({{ item.variant_name }})</span>
                  </p>
                  <p v-if="item.notes" class="text-sm text-gray-500 italic">
                    {{ item.notes }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="font-medium text-gray-900">{{ formatPrice(item.price * item.quantity) }}</p>
                  <p class="text-xs text-gray-500">{{ formatPrice(item.price) }} / szt</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div class="border-t pt-4">
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ t('manager.orders.index.subtotal') }}</span>
                <span>{{ formatPrice(selectedOrder.subtotal) }}</span>
              </div>
              <div v-if="selectedOrder.shipping_cost > 0" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ t('manager.orders.index.delivery') }}</span>
                <span>{{ formatPrice(selectedOrder.shipping_cost) }}</span>
              </div>
              <div v-if="selectedOrder.discount > 0" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ t('manager.orders.index.discount') }}</span>
                <span class="text-red-600">-{{ formatPrice(selectedOrder.discount) }}</span>
              </div>
              <div class="flex justify-between font-bold text-lg border-t pt-2">
                <span>{{ t('manager.orders.index.total') }}</span>
                <span>{{ formatPrice(selectedOrder.total) }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedOrder.notes" class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 mb-1">{{ t('manager.orders.index.order_notes') }}</h4>
            <p class="text-sm text-gray-700">{{ selectedOrder.notes }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-2 border-t">
            <a
              v-if="$page.props.app_version === 'test'"
              :href="route('tenant.manager.orders.invoice', selectedOrder.order_number)"
              target="_blank"
              class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors"
            >
              {{ t('manager.orders.index.invoice_print') }}
            </a>
            <button
              @click="selectedOrder = null"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
            >
              {{ t('common.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Manual Order Modal -->
    <ManualOrderModal :show="manualOrderOpen" @close="manualOrderOpen = false" @success="onManualOrderCreated" />
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ManualOrderModal from '@/Components/Manager/ManualOrderModal.vue'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'

const { t, locale } = useI18n()
const { relative } = useFormatting()

const manualOrderOpen = ref(false)
const onManualOrderCreated = (orderNumber) => {
  router.reload({ only: ['orders'], preserveScroll: true })
}

const showImportModal = ref(false)
const importFile = ref(null)
const importing = ref(false)

const handleImport = async () => {
  if (!importFile.value) return
  importing.value = true
  const formData = new FormData()
  formData.append('file', importFile.value)
  try {
    await window.axios.post(route('tenant.manager.orders.import'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    showImportModal.value = false
    importFile.value = null
    router.reload({ only: ['orders'] })
  } catch (e) {
    alert(t('common.import_error') + (e?.response?.data?.message ?? t('common.unknown_error')))
  } finally {
    importing.value = false
  }
}

const props = defineProps({
  orders: Object,
})

const selectedOrder = ref(null)
const selectedOrders = ref([])
let searchTimeout = null

const allSelected = computed(
  () => props.orders?.data?.length > 0 && props.orders.data.every((o) => selectedOrders.value.includes(o.id)),
)

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedOrders.value = []
  } else {
    selectedOrders.value = props.orders?.data?.map((o) => o.id) ?? []
  }
}

const openPickingList = () => {
  if (selectedOrders.value.length === 0) return
  const params = selectedOrders.value.map((id) => `order_ids[]=${id}`).join('&')
  window.open(`/manager/orders/picking-list?${params}`, '_blank')
}

const newOrdersCount = ref(0)
const originalTitle = document.title

const updateTitle = () => {
  document.title = newOrdersCount.value > 0 ? `(${newOrdersCount.value}) ${originalTitle}` : originalTitle
}

const page = usePage()

onMounted(() => {
  const tenantId = page.props.tenant?.id
  if (window.Echo && tenantId) {
    window.Echo.private(`orders.${tenantId}`)
      .listen('.OrderCreated', () => {
        newOrdersCount.value++
        updateTitle()
        router.reload({ only: ['orders'], preserveScroll: true })
      })
      .listen('.order.status-changed', () => {
        router.reload({ only: ['orders'], preserveScroll: true })
      })
  }
})

onUnmounted(() => {
  document.title = originalTitle
})

const filters = reactive({
  status: new URL(window.location.href).searchParams.get('status') || '',
  fulfillment_status: new URL(window.location.href).searchParams.get('fulfillment_status') || '',
  payment_status: new URL(window.location.href).searchParams.get('payment_status') || '',
  search: new URL(window.location.href).searchParams.get('search') || '',
})

const formatPrice = (price) => {
  return new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: 'PLN',
  }).format(price)
}

const formatDate = (date) => {
  return new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const timeAgo = (date) => {
  return relative.value(date)
}

const fulfillmentLabel = (s) => {
  return (
    {
      unfulfilled: t('manager.orders.index.pending'),
      processing: t('client.ordertracking.being_fulfilled'),
      shipped: t('manager.orders.index.sent'),
      delivered: t('manager.orders.index.delivered'),
    }[s] ?? s
  )
}

const fulfillmentClass = (s) => {
  return (
    {
      unfulfilled: 'bg-yellow-100 text-yellow-800',
      processing: 'bg-blue-100 text-blue-800',
      shipped: 'bg-blue-100 text-blue-900',
      delivered: 'bg-green-100 text-green-800',
    }[s] ?? 'bg-gray-100 text-gray-800'
  )
}

const statusClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-700',
    awaiting_payment: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-200 text-green-900',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const paymentMethodLabel = (method) => {
  const labels = {
    przelewy24: 'Przelewy24',
    payu: 'PayU',
    tpay: 'Tpay',
    stripe: 'Stripe',
    bank_transfer: t('manager.manualordermodal.bank_transfer'),
    cash_on_delivery: t('common.cash_on_delivery'),
  }
  return labels[method] || method || '—'
}

const paymentStatusLabel = (status) => {
  const labels = {
    pending: t('manager.orders.index.pending_2'),
    paid: t('manager.orders.index.paid'),
    failed: t('manager.orders.index.failed'),
    refunded: t('manager.orders.index.refunded'),
  }
  return labels[status] || status
}

const paymentStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const applyFilters = () => {
  newOrdersCount.value = 0
  document.title = originalTitle
  router.get(route('tenant.manager.orders.index'), filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const updateStatus = (order, newStatus) => {
  router.patch(
    route('tenant.manager.orders.update-status', order.id),
    {
      status: newStatus,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  )
}

const updatePaymentStatus = (order, newPaymentStatus) => {
  router.patch(
    route('tenant.manager.orders.update-payment-status', order.id),
    {
      payment_status: newPaymentStatus,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  )
}

const viewOrder = (order) => {
  selectedOrder.value = order
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
