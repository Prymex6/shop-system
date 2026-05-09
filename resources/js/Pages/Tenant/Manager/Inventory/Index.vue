<template>
  <ManagerLayout :title="t('common.warehouse')">
    <div class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.warehouse') }}</h1>
        <a
          :href="route('tenant.manager.inventory.export')"
          class="border border-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition"
        >
          {{ t('manager.inventory.index.export_csv') }}
        </a>
      </div>

      <!-- Filters -->
      <div class="flex gap-3 mb-5">
        <input
          v-model="localFilters.search"
          type="text"
          :placeholder="t('common.search_products')"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm flex-1"
          @input="applyFilters"
        />
        <select
          v-model="localFilters.filter"
          @change="applyFilters"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm"
        >
          <option value="">{{ t('common.all') }}</option>
          <option value="low">{{ t('manager.inventory.index.low_stock') }}</option>
          <option value="out">{{ t('common.none') }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.product') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.category') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.stock') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.alert_threshold') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ product.name }}</td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ product.sku ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-500">{{ product.category?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  class="font-bold"
                  :class="{
                    'text-red-600': product.stock_quantity <= 0,
                    'text-orange-600':
                      product.stock_quantity > 0 && product.stock_quantity <= product.low_stock_threshold,
                    'text-green-700': product.stock_quantity > product.low_stock_threshold,
                  }"
                  >{{ product.stock_quantity }}</span
                >
              </td>
              <td class="px-4 py-3 text-center text-gray-500">{{ product.low_stock_threshold }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="openAdjust(product)"
                  class="text-sm text-blue-600 hover:text-blue-900 font-medium px-2 py-1"
                >
                  {{ t('manager.inventory.index.adjustment') }}
                </button>
                <Link
                  :href="route('tenant.manager.inventory.movements', product.id)"
                  class="text-sm text-gray-500 hover:text-gray-700 font-medium px-2 py-1"
                  >{{ t('manager.inventory.index.history') }}</Link
                >
              </td>
            </tr>
            <tr v-if="!products.data.length">
              <td colspan="6" class="text-center py-12 text-gray-400">
                {{ t('manager.inventory.index.no_products_with_stock_tracking') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Adjust modal -->
      <div
        v-if="adjusting"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        @click.self="adjusting = null"
      >
        <div class="bg-white shadow rounded-lg w-full max-w-sm p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-1">{{ t('manager.inventory.index.stock_adjustment') }}</h2>
          <p class="text-sm text-gray-500 mb-5">
            {{ adjusting.name }} — aktualnie: <strong>{{ adjusting.stock_quantity }} szt.</strong>
          </p>
          <form @submit.prevent="submitAdjust" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.inventory.index.change_or')
              }}</label>
              <input
                v-model="adjustForm.quantity_change"
                type="number"
                required
                class="w-full border border-gray-200 rounded-md px-3 py-2"
                :placeholder="t('manager.inventory.index.10_or_5')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.fraud.index.type') }}</label>
              <select v-model="adjustForm.type" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                <option value="adjustment">{{ t('manager.inventory.index.adjustment') }}</option>
                <option value="import">{{ t('common.delivery') }}</option>
                <option value="damage">{{ t('manager.inventory.index.damage') }}</option>
                <option value="return">{{ t('common.return') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.reason_optional') }}</label>
              <input
                v-model="adjustForm.reason"
                type="text"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
              />
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="adjusting = null" class="px-4 py-2 text-sm text-gray-600 font-medium">
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition"
              >
                {{ t('common.save') }}
              </button>
            </div>
          </form>
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

const { t } = useI18n()

const props = defineProps({
  products: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const localFilters = reactive({ ...props.filters })
const adjusting = ref(null)
const adjustForm = reactive({ quantity_change: '', type: 'adjustment', reason: '' })

function applyFilters() {
  router.get(route('tenant.manager.inventory.index'), localFilters, { preserveState: true, replace: true })
}

function openAdjust(product) {
  adjusting.value = product
  Object.assign(adjustForm, { quantity_change: '', type: 'adjustment', reason: '' })
}

function submitAdjust() {
  router.patch(route('tenant.manager.inventory.adjust', adjusting.value.id), adjustForm, {
    onSuccess: () => {
      adjusting.value = null
    },
    preserveScroll: true,
  })
}
</script>
