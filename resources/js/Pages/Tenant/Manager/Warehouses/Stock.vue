<template>
  <ManagerLayout :title="`Stan: ${warehouse.name}`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <Link :href="route('tenant.manager.warehouses.index')" class="text-blue-600 hover:text-blue-900 text-sm">
              {{ t('manager.warehouses.stock.warehouses') }}
            </Link>
          </div>
          <h1 class="text-3xl font-bold text-gray-900">{{ warehouse.name }}</h1>
          <p v-if="warehouse.address" class="text-sm text-gray-500 mt-1">{{ warehouse.address }}</p>
        </div>
      </div>

      <!-- Search -->
      <div class="bg-white shadow rounded-lg p-4">
        <input
          v-model="search"
          type="text"
          :placeholder="t('manager.warehouses.stock.search_products')"
          class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Stock table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.product') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.stock') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.warehouses.stock.reservations') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.warehouses.stock.available') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.adjustment') }}
              </th>
              <th
                v-if="otherWarehouses.length"
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                {{ t('manager.warehouses.stock.move') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ item.product?.name ?? item.name }}</td>
              <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ item.product?.sku ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="stockClass(item.quantity)" class="font-semibold">
                  {{ item.quantity ?? 0 }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-gray-500">{{ item.reserved ?? 0 }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="stockClass((item.quantity ?? 0) - (item.reserved ?? 0))" class="font-semibold">
                  {{ (item.quantity ?? 0) - (item.reserved ?? 0) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <input
                    v-model.number="adjustments[item.id]"
                    type="number"
                    class="w-20 px-2 py-1 border border-gray-300 rounded text-sm text-center"
                    placeholder="0"
                  />
                  <button
                    @click="applyAdjustment(item)"
                    :disabled="!adjustments[item.id]"
                    class="px-2 py-1 bg-blue-600 text-white text-xs rounded font-medium hover:bg-blue-700 disabled:opacity-40"
                  >
                    {{ t('manager.warehouses.stock.adjust') }}
                  </button>
                </div>
              </td>
              <td v-if="otherWarehouses.length" class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <input
                    v-model.number="transfers[item.id].quantity"
                    type="number"
                    min="1"
                    class="w-16 px-2 py-1 border border-gray-300 rounded text-sm text-center"
                    placeholder="0"
                  />
                  <select
                    v-model.number="transfers[item.id].to_warehouse_id"
                    class="px-1.5 py-1 border border-gray-300 rounded text-xs max-w-[8rem]"
                  >
                    <option v-for="wh in otherWarehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                  </select>
                  <button
                    @click="applyTransfer(item)"
                    :disabled="!transfers[item.id].quantity"
                    class="px-2 py-1 bg-indigo-600 text-white text-xs rounded font-medium hover:bg-indigo-700 disabled:opacity-40"
                  >
                    {{ t('manager.warehouses.stock.move') }}
                  </button>
                </div>
                <p v-if="transferErrors[item.id]" class="text-red-600 text-xs mt-1" role="alert">
                  {{ transferErrors[item.id] }}
                </p>
              </td>
            </tr>
            <tr v-if="!filteredItems.length">
              <td colspan="7" class="text-center py-12 text-gray-400">{{ t('common.no_products') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  warehouse: { type: Object, required: true },
  stockItems: { type: Array, default: () => [] },
  otherWarehouses: { type: Array, default: () => [] },
})

const search = ref('')
const adjustments = ref({})
const transferErrors = ref({})
const transfers = reactive(
  Object.fromEntries(
    props.stockItems.map((item) => [
      item.id,
      { quantity: null, to_warehouse_id: props.otherWarehouses[0]?.id ?? null },
    ]),
  ),
)

const filteredItems = computed(() => {
  if (!search.value) return props.stockItems
  const q = search.value.toLowerCase()
  return props.stockItems.filter((i) => (i.product?.name ?? i.name ?? '').toLowerCase().includes(q))
})

const stockClass = (qty) => {
  if (qty <= 0) return 'text-red-600'
  if (qty <= 5) return 'text-orange-500'
  return 'text-green-700'
}

const applyAdjustment = (item) => {
  const delta = adjustments.value[item.id]
  if (!delta) return
  router.post(
    route('tenant.manager.warehouses.stock.adjust', props.warehouse.id),
    {
      product_id: item.product_id,
      quantity: delta,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        adjustments.value[item.id] = null
      },
    },
  )
}

const applyTransfer = (item) => {
  const form = transfers[item.id]
  if (!form.quantity || !form.to_warehouse_id) return
  transferErrors.value[item.id] = ''
  router.post(
    route('tenant.manager.warehouses.stock.transfer', props.warehouse.id),
    {
      product_id: item.product_id,
      variant_id: item.variant_id ?? null,
      to_warehouse_id: form.to_warehouse_id,
      quantity: form.quantity,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        form.quantity = null
      },
      onError: (errors) => {
        transferErrors.value[item.id] =
          errors.quantity || errors.to_warehouse_id || t('common.the_stock_could_not_be_moved')
      },
    },
  )
}
</script>
