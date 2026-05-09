<template>
  <ManagerLayout :title="`Historia magazynu — ${product.name}`">
    <div class="space-y-6">
      <div>
        <Link
          :href="route('tenant.manager.inventory.index')"
          class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >{{ t('manager.inventory.movements.larr_warehouse') }}</Link
        >
        <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ product.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ t('manager.inventory.movements.current_stock') }}
          <span class="font-semibold text-gray-700">{{ product.stock_quantity }}</span>
        </p>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.date') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.fraud.index.type') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.movements.change') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.movements.stock_after') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.reason') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(m.created_at) }}</td>
              <td class="px-4 py-3 text-gray-700">{{ typeLabel(m.type) }}</td>
              <td
                class="px-4 py-3 text-right font-semibold"
                :class="m.quantity_change >= 0 ? 'text-green-600' : 'text-red-600'"
              >
                {{ m.quantity_change >= 0 ? '+' : '' }}{{ m.quantity_change }}
              </td>
              <td class="px-4 py-3 text-right text-gray-900">{{ m.quantity_after }}</td>
              <td class="px-4 py-3 text-gray-500">{{ m.reason || '—' }}</td>
            </tr>
            <tr v-if="!movements.data.length">
              <td colspan="5" class="text-center py-12 text-gray-400">
                {{ t('manager.inventory.movements.no_stock_movement_history') }}
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="movements.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm"
        >
          <span class="text-gray-500">Strona {{ movements.current_page }} z {{ movements.last_page }}</span>
          <div class="flex gap-2">
            <Link
              v-if="movements.prev_page_url"
              :href="movements.prev_page_url"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
              >{{ t('manager.customers.index.previous') }}</Link
            >
            <Link
              v-if="movements.next_page_url"
              :href="movements.next_page_url"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
              >{{ t('common.next') }}</Link
            >
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({
  product: { type: Object, required: true },
  movements: { type: Object, required: true },
})

const typeLabels = {
  sale: t('layout.managerlayout.sales'),
  restock: t('common.restock'),
  adjustment: t('common.manual_adjustment'),
  return: t('common.return'),
  rma: t('manager.inventory.movements.complaint'),
  purchase_order: t('common.purchase_order'),
}
const typeLabel = (type) => typeLabels[type] ?? type

const formatDate = (date) =>
  new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
</script>
