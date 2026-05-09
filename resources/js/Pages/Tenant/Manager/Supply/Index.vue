<template>
  <ManagerLayout :title="t('common.delivery_returns')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.delivery_returns') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.supply.index.warehouses_suppliers_and_returns') }}</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link
          :href="route('tenant.manager.warehouses.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-stone-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-building text-stone-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.supply.index.warehouses') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.supply.index.warehouse_locations') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.suppliers.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-lime-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-handshake text-lime-700 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.suppliers.index.suppliers') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.supply.index.supplier_directory') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.purchase-orders.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-file-invoice text-blue-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">{{ t('common.purchase_orders') }}</div>
              <div class="text-sm text-gray-500">{{ t('manager.supply.index.orders_to_suppliers') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.rma.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-rotate-left text-red-500 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.supply.index.rma_returns') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.supply.index.returns_and_complaints') }}</div>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
