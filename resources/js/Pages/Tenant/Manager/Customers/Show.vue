<template>
  <Head :title="t('common.customer_2') + customer.name" />
  <ManagerLayout>
    <div class="space-y-6">
      <div class="flex items-center gap-4 mb-6">
        <Link :href="route('tenant.manager.customers.index')" class="text-gray-500 hover:text-gray-700">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <h1 class="text-3xl font-bold text-gray-900">{{ customer.name }}</h1>
      </div>

      <!-- Customer info -->
      <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-6 space-y-3">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('client.checkout.contact_details') }}</h2>
          <div class="flex items-center gap-2 text-sm">
            <i class="fa-solid fa-envelope w-4 text-gray-400"></i>
            <span>{{ customer.email }}</span>
          </div>
          <div v-if="customer.phone" class="flex items-center gap-2 text-sm">
            <i class="fa-solid fa-phone w-4 text-gray-400"></i>
            <a :href="'tel:' + customer.phone" class="text-blue-600">{{ customer.phone }}</a>
          </div>
          <div v-if="customer.delivery_address" class="flex items-start gap-2 text-sm">
            <i class="fa-solid fa-location-dot w-4 text-gray-400 mt-0.5"></i>
            <span
              >{{ customer.delivery_address }}, {{ customer.delivery_postal_code }} {{ customer.delivery_city }}</span
            >
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-500">
            <i class="fa-solid fa-calendar w-4 text-gray-400"></i>
            <span>{{ t('common.joined_a', { a: formatDate(customer.created_at) }) }}</span>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('common.loyalty_programme') }}</h2>
          <div class="text-center">
            <div class="text-4xl font-bold text-gray-900">{{ customer.loyalty_points ?? 0 }}</div>
            <div class="text-gray-500 text-sm mt-1">{{ t('manager.customers.show.points') }}</div>
            <span
              class="mt-3 inline-block px-3 py-1 rounded-full text-sm font-medium"
              :class="tierClass(customer.loyalty_tier)"
            >
              Poziom: {{ tierLabel(customer.loyalty_tier) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('manager.customers.show.statistics') }}</h2>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ t('manager.customers.show.orders_in_total') }}</span>
              <span class="font-semibold">{{ customer.orders?.length ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ t('manager.customers.show.total_paid') }}</span>
              <span class="font-semibold text-green-600"
                >{{ formatPrice(totalSpent) }} {{ t('common.currency_pln') }}</span
              >
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ t('manager.customers.show.loyalty_points') }}</span>
              <span class="font-semibold">{{ customer.loyalty_points ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ t('manager.customers.show.points_earned_in_total') }}</span>
              <span class="font-semibold">{{ customer.loyalty_points_earned_total ?? 0 }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="font-semibold text-gray-900 mb-4">{{ t('manager.customers.show.favourite_products') }}</h2>
          <div v-if="favoriteProducts?.length > 0" class="space-y-2">
            <div
              v-for="(prod, i) in favoriteProducts"
              :key="prod.name"
              class="flex justify-between items-center text-sm"
            >
              <span class="flex items-center gap-2">
                <span
                  class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center"
                  >{{ i + 1 }}</span
                >
                {{ prod.name }}
              </span>
              <span class="text-gray-500">{{ prod.total_qty }}x</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">{{ t('common.no_orders') }}</p>
        </div>
      </div>

      <!-- Orders -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="font-semibold text-gray-900">{{ t('manager.customers.show.order_history_last_20') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                {{ t('manager.customers.show.number') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('common.date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                {{ t('common.status') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">
                {{ t('manager.customers.show.amount') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in customer.orders" :key="order.id">
              <td class="px-4 py-3 font-medium text-blue-600">{{ order.order_number }}</td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(order.created_at) }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(order.status)">{{
                  statusLabel(order.status)
                }}</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold">
                {{ formatPrice(order.total) }} {{ t('common.currency_pln') }}
              </td>
            </tr>
            <tr v-if="!customer.orders?.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ t('common.no_orders') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({
  customer: Object,
  totalSpent: { type: Number, default: 0 },
  favoriteProducts: { type: Array, default: () => [] },
})

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(locale.value) : '–')
const formatPrice = (p) =>
  Number(p || 0)
    .toFixed(2)
    .replace('.', ',')

const tierClass = (tier) =>
  ({
    bronze: 'bg-orange-100 text-orange-700',
    silver: 'bg-gray-100 text-gray-700',
    gold: 'bg-yellow-100 text-yellow-700',
    platinum: 'bg-purple-100 text-purple-700',
  })[tier] || 'bg-gray-100 text-gray-600'

const tierLabel = (tier) =>
  ({
    bronze: t('common.bronze_2'),
    silver: t('manager.customers.show.silver'),
    gold: t('common.gold'),
    platinum: t('manager.customers.show.platinum'),
  })[tier] || tier

const statusLabel = (s) =>
  ({
    pending: t('manager.orders.index.placed'),
    awaiting_payment: t('manager.orders.index.awaiting_payment'),
    paid: t('manager.orders.index.paid_2'),
    completed: t('common.completed'),
    cancelled: t('manager.orders.index.cancelled'),
    refunded: t('manager.orders.index.refunded_2'),
  })[s] || s

const statusClass = (status) =>
  ({
    pending: 'bg-gray-100 text-gray-600',
    awaiting_payment: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    refunded: 'bg-purple-100 text-purple-700',
  })[status] || 'bg-gray-100 text-gray-600'
</script>
