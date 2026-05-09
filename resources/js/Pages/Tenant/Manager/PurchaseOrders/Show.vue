<template>
  <ManagerLayout :title="`PO: ${po.po_number ?? po.id}`">
    <div class="space-y-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm">
        <Link :href="route('tenant.manager.purchase-orders.index')" class="text-blue-600 hover:text-blue-900">
          {{ t('common.purchase_orders') }}
        </Link>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700 font-medium">{{ po.po_number ?? `PO-${po.id}` }}</span>
      </div>

      <!-- PO header -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ po.po_number ?? `PO-${po.id}` }}</h1>
            <p class="text-gray-500 text-sm mt-1">
              {{ t('manager.purchaseorders.show.supplier') }}
              <span class="font-medium text-gray-700">{{ po.supplier?.company_name ?? '—' }}</span>
            </p>
            <p v-if="po.expected_at" class="text-gray-500 text-sm">
              {{ t('manager.purchaseorders.show.expected_delivery_date') }}
              <span class="font-medium text-gray-700">{{ formatDate(po.expected_at) }}</span>
            </p>
            <p v-if="po.notes" class="text-sm text-gray-600 mt-2 bg-gray-50 p-2 rounded">{{ po.notes }}</p>
          </div>
          <span :class="statusBadge(po.status)" class="px-3 py-1.5 rounded-full text-sm font-semibold shrink-0">
            {{ statusLabel(po.status) }}
          </span>
        </div>
      </div>

      <!-- Items table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-bold text-gray-900">{{ t('common.order_items') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.product') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.purchaseorders.show.order_qty') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.unit_price') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.value') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.purchaseorders.show.received') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in po.items" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ item.product?.name ?? item.product_name }}</td>
              <td class="px-4 py-3 text-center text-gray-700">{{ item.quantity }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ formatPrice(item.unit_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900">
                {{ formatPrice(item.quantity * item.unit_price) }}
              </td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="item.received_quantity >= item.quantity ? 'text-green-600 font-semibold' : 'text-gray-500'"
                >
                  {{ item.received_quantity ?? 0 }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50 border-t border-gray-200">
            <tr>
              <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">{{ t('common.total_3') }}</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900 text-base">{{ formatPrice(po.total_value) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Actions -->
      <div
        class="flex items-center gap-3 flex-wrap"
        v-if="['sent', 'confirmed', 'partially_received'].includes(po.status)"
      >
        <button
          @click="showReceiveModal = true"
          class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition"
        >
          <i class="fa-solid fa-box-archive mr-2"></i>{{ t('manager.purchaseorders.show.mark_as_received') }}
        </button>
      </div>
    </div>

    <!-- Receive Modal -->
    <div
      v-if="showReceiveModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showReceiveModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">{{ t('manager.purchaseorders.show.receive_a_delivery') }}</h3>
          <button @click="showReceiveModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-sm text-gray-500">
            {{ t('manager.purchaseorders.show.enter_the_quantities_you_actually_received') }}
          </p>
          <div v-for="item in po.items" :key="item.id" class="flex items-center gap-4">
            <span class="flex-1 text-sm text-gray-700">{{ item.product?.name ?? item.product_name }}</span>
            <span class="text-xs text-gray-400">zam: {{ item.quantity }}</span>
            <input
              v-model.number="receivedQty[item.id]"
              type="number"
              min="0"
              :max="item.quantity"
              class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-center"
              :placeholder="item.quantity"
            />
          </div>
          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button @click="showReceiveModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
              {{ t('common.cancel') }}
            </button>
            <button
              @click="submitReceive"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold"
            >
              {{ t('manager.purchaseorders.show.confirm_receipt') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  po: { type: Object, required: true },
})

const showReceiveModal = ref(false)
const receivedQty = reactive({})

const submitReceive = () => {
  router.post(
    route('tenant.manager.purchase-orders.receive', props.po.id),
    {
      items: Object.entries(receivedQty).map(([id, qty]) => ({ id, received_quantity: qty })),
    },
    {
      onSuccess: () => {
        showReceiveModal.value = false
      },
    },
  )
}

const statusLabel = (s) =>
  ({
    draft: t('manager.articles.form.draft'),
    sent: t('manager.orders.index.sent'),
    confirmed: t('manager.purchaseorders.index.confirmed'),
    partially_received: t('common.partly_received'),
    received: t('manager.purchaseorders.index.received'),
    cancelled: t('manager.orders.index.cancelled'),
  })[s] ?? s

const statusBadge = (s) =>
  ({
    draft: 'bg-gray-100 text-gray-600',
    sent: 'bg-blue-100 text-blue-700',
    confirmed: 'bg-blue-100 text-blue-700',
    partially_received: 'bg-yellow-100 text-yellow-700',
    received: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  })[s] ?? 'bg-gray-100 text-gray-600'

const formatPrice = (v) => new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => new Date(d).toLocaleDateString(locale.value)
</script>
