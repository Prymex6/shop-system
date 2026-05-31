<template>
  <ManagerLayout :title="t('manager.sales.index.sales_promotions')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.sales.index.sales_promotions') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.sales.index.sales_and_promotion_tools') }}</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link
          :href="route('tenant.manager.promotions.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-bullhorn text-orange-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.promotions.index.promotions_and_banners') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.sales.index.manage_promotions_and_the_banner') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.flash-sales.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-bolt text-yellow-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">Flash Sales</div>
              <div class="text-sm text-gray-500">{{ t('manager.sales.index.timed_sales') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.volume-discounts.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-sliders text-cyan-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('common.volume_discounts') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.sales.index.discounts_on_larger_orders') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.abandoned-carts.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-cart-arrow-down text-gray-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.abandonedcarts.index.abandoned_carts') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.sales.index.win_back_the_purchases_that_never') }}</div>
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
