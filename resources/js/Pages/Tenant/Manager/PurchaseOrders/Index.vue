<template>
  <ManagerLayout :title="t('common.purchase_orders')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.purchaseorders.index.purchase_orders_po') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.purchaseorders.index.order_stock_from_suppliers') }}</p>
        </div>
        <Link
          :href="route('tenant.manager.purchase-orders.create')"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.purchaseorders.index.new_po') }}
        </Link>
      </div>

      <!-- Status filters -->
      <div class="flex gap-2 flex-wrap">
        <button
          v-for="s in ['', 'draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled']"
          :key="s"
          @click="filterStatus = s"
          :class="
            filterStatus === s
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-300'
          "
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
        >
          {{ statusLabel(s) || t('common.all') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.purchaseorders.index.po_no') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.purchaseorders.index.supplier') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.status') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.value') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.purchaseorders.index.expected_date') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="po in filteredOrders" :key="po.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-semibold text-gray-900">{{ po.po_number ?? `PO-${po.id}` }}</td>
              <td class="px-4 py-3 text-gray-700">{{ po.supplier?.company_name ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="statusBadge(po.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ statusLabel(po.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatPrice(po.total_value) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ po.expected_at ? formatDate(po.expected_at) : '—' }}</td>
              <td class="px-4 py-3 text-right">
                <Link
                  :href="route('tenant.manager.purchase-orders.show', po.id)"
                  class="text-blue-600 hover:text-blue-900 text-xs font-medium"
                >
                  {{ t('common.details') }}
                </Link>
              </td>
            </tr>
            <tr v-if="!filteredOrders.length">
              <td colspan="6" class="text-center py-12 text-gray-400">
                {{ t('manager.purchaseorders.index.no_purchase_orders') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  purchaseOrders: { type: Object, required: true },
})

const filterStatus = ref('')

const filteredOrders = computed(() => {
  const all = props.purchaseOrders.data ?? []
  if (!filterStatus.value) return all
  return all.filter((po) => po.status === filterStatus.value)
})

const statusLabel = (s) =>
  ({
    '': t('common.all'),
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
