<template>
  <ManagerLayout title="Flash Sales">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Flash Sales</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.flashsales.index.timed_promotions_with_a_countdown') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.flashsales.index.new_flash_sale') }}
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.collections.index.period') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.discount') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.products_2') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.orders_2') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.status') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.flashsales.index.countdown') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="fs in flashSales.data" :key="fs.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold text-gray-900">{{ fs.name }}</td>
              <td class="px-4 py-3 text-xs text-gray-500">
                <div>{{ formatDateTime(fs.starts_at) }}</div>
                <div>→ {{ formatDateTime(fs.ends_at) }}</div>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="font-bold text-blue-600">
                  {{
                    fs.discount_type === 'percentage'
                      ? fs.discount_value + '%'
                      : fs.discount_value + ' ' + t('common.currency_pln')
                  }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-gray-700">{{ fs.products_count ?? 0 }}</td>
              <td class="px-4 py-3 text-center text-gray-700">{{ fs.orders_count ?? 0 }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="statusClass(fs)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ statusLabel(fs) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <CountdownTimer
                  v-if="isActive(fs)"
                  :ends-at="fs.ends_at"
                  class="text-xs font-mono text-red-600 font-bold"
                />
                <span v-else class="text-gray-300 text-xs">—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openProducts(fs)" class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                    {{ t('common.products') }}
                  </button>
                  <button @click="openModal(fs)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(fs)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!flashSales.data?.length">
              <td colspan="8" class="text-center py-12 text-gray-400">
                {{ t('manager.flashsales.index.no_flash_sales') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_flash_sale') : t('common.new_flash_sale') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
            <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.flashsales.index.discount_type')
              }}</label>
              <select v-model="form.discount_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="percentage">{{ t('manager.flashsales.index.percentage') }}</option>
                <option value="fixed">{{ t('common.amount_pln') }}</option>
              </select>
              <p v-if="form.errors.discount_type" class="text-red-600 text-sm mt-1">{{ form.errors.discount_type }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.flashsales.index.discount_value')
              }}</label>
              <input
                v-model="form.discount_value"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
              <p v-if="form.errors.discount_value" class="text-red-600 text-sm mt-1">
                {{ form.errors.discount_value }}
              </p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.flashsales.index.start_date')
              }}</label>
              <input
                v-model="form.starts_at"
                type="datetime-local"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
              <p v-if="form.errors.starts_at" class="text-red-600 text-sm mt-1">{{ form.errors.starts_at }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.flashsales.index.end_date')
              }}</label>
              <input
                v-model="form.ends_at"
                type="datetime-local"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
              <p v-if="form.errors.ends_at" class="text-red-600 text-sm mt-1">{{ form.errors.ends_at }}</p>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.flashsales.index.max_orders_optional')
            }}</label>
            <input
              v-model="form.max_orders"
              type="number"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
            <p v-if="form.errors.max_orders" class="text-red-600 text-sm mt-1">{{ form.errors.max_orders }}</p>
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ editing ? t('common.save_changes') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Products Modal -->
    <div
      v-if="showProductsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showProductsModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">Produkty: {{ currentSale?.name }}</h3>
          <button @click="showProductsModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-sm text-gray-500">{{ t('manager.flashsales.index.tick_the_products_this_flash_sale') }}</p>
          <div class="max-h-64 overflow-y-auto space-y-2 border border-gray-200 rounded-lg p-3">
            <label
              v-for="p in allProducts"
              :key="p.id"
              class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded"
            >
              <input type="checkbox" :value="p.id" v-model="selectedProductIds" class="h-4 w-4 text-blue-600 rounded" />
              <span class="text-sm text-gray-700">{{ p.name }}</span>
            </label>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showProductsModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
              {{ t('common.cancel') }}
            </button>
            <button
              @click="saveProducts"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700"
            >
              {{ t('common.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import CountdownTimer from '@/Components/Client/CountdownTimer.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  flashSales: { type: Object, required: true },
  allProducts: { type: Array, default: () => [] },
})

const showModal = ref(false)
const showProductsModal = ref(false)
const editing = ref(null)
const currentSale = ref(null)
const selectedProductIds = ref([])

const form = useForm({
  name: '',
  discount_type: 'percentage',
  discount_value: '',
  starts_at: '',
  ends_at: '',
  max_orders: '',
})

const openModal = (fs = null) => {
  editing.value = fs
  if (fs) {
    form.name = fs.name ?? ''
    form.discount_type = fs.discount_type ?? 'percentage'
    form.discount_value = fs.discount_value ?? ''
    form.starts_at = fs.starts_at?.substring(0, 16) ?? ''
    form.ends_at = fs.ends_at?.substring(0, 16) ?? ''
    form.max_orders = fs.max_orders ?? ''
  } else {
    form.reset()
    form.discount_type = 'percentage'
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.flash-sales.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.flash-sales.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (fs) => {
  if (!confirm(t('manager.flashsales.index.delete_the_flash_sale_a', { a: fs.name }))) return
  router.delete(route('tenant.manager.flash-sales.destroy', fs.id))
}

const openProducts = (fs) => {
  currentSale.value = fs
  selectedProductIds.value = (fs.products ?? []).map((p) => p.id)
  showProductsModal.value = true
}

const saveProducts = () => {
  router.patch(
    route('tenant.manager.flash-sales.products', currentSale.value.id),
    {
      product_ids: selectedProductIds.value,
    },
    {
      onSuccess: () => {
        showProductsModal.value = false
      },
    },
  )
}

const isActive = (fs) => {
  const now = Date.now()
  return new Date(fs.starts_at) <= now && new Date(fs.ends_at) >= now
}

const statusClass = (fs) => {
  if (isActive(fs)) return 'bg-green-100 text-green-700'
  if (new Date(fs.ends_at) < Date.now()) return 'bg-gray-100 text-gray-500'
  return 'bg-yellow-100 text-yellow-700'
}

const statusLabel = (fs) => {
  if (isActive(fs)) return t('common.active')
  if (new Date(fs.ends_at) < Date.now()) return t('common.finished')
  return 'Zaplanowany'
}

const formatDateTime = (d) =>
  d
    ? new Date(d).toLocaleString(locale.value, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '—'
</script>
