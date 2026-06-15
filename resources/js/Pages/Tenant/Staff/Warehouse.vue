<template>
  <StaffLayout :title="t('common.warehouse')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.warehouse') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('staff.warehouse.stock_overview') }}</p>
      </div>

      <!-- Low stock alert -->
      <div v-if="lowStock.length" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-3">
          <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
          <h2 class="font-semibold text-amber-800">Niski stan magazynowy ({{ lowStock.length }})</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <div
            v-for="p in lowStock"
            :key="p.id"
            class="bg-white border border-amber-100 rounded-lg px-3 py-2 flex items-center justify-between"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ p.name }}</p>
              <p class="text-xs text-gray-400">{{ p.sku }}</p>
            </div>
            <span class="text-sm font-bold text-amber-600">{{ t('common.a_units', { a: p.stock_quantity }) }}</span>
          </div>
        </div>
      </div>

      <!-- Warehouses -->
      <div v-if="warehouses.length" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div v-for="wh in warehouses" :key="wh.id" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="font-bold text-gray-900 text-lg">{{ wh.name }}</h3>
              <p class="text-sm text-gray-500">{{ wh.city }}</p>
            </div>
            <span
              :class="wh.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
              class="text-xs font-semibold px-2 py-1 rounded-full"
            >
              {{ wh.is_active ? t('common.active') : t('common.inactive') }}
            </span>
          </div>

          <div v-if="wh.stock?.length" class="space-y-1 mt-3">
            <div
              v-for="s in wh.stock"
              :key="s.id"
              class="flex items-center justify-between text-sm py-1 border-b border-gray-50 last:border-0"
            >
              <span class="text-gray-700">{{ s.product?.name }}</span>
              <span class="font-semibold text-gray-900">{{ s.quantity }} szt.</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 mt-3">{{ t('staff.warehouse.no_items_in_the_warehouse') }}</p>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl border border-gray-200 p-10 text-center text-gray-400">
        <i class="fa-solid fa-warehouse text-4xl mb-3"></i>
        <p>{{ t('staff.warehouse.no_warehouses_set_up') }}</p>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  warehouses: { type: Array, default: () => [] },
  lowStock: { type: Array, default: () => [] },
})
</script>
