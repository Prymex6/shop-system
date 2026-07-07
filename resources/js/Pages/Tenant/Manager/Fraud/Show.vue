<template>
  <ManagerLayout :title="t('manager.fraud.show.suspicious_order')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <Link :href="route('tenant.manager.fraud.index')" class="text-blue-600 hover:text-blue-900 text-sm">
            {{ t('manager.fraud.show.larr_fraud_detection') }}
          </Link>
          <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ t('manager.fraud.show.suspicious_order') }}</h1>
          <p class="text-sm font-mono text-gray-500 mt-0.5">#{{ order.id }}</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="release"
            class="px-4 py-2 bg-green-100 text-green-700 hover:bg-green-200 rounded-xl font-semibold text-sm transition"
          >
            {{ t('manager.fraud.index.release') }}
          </button>
          <button
            @click="block"
            class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-xl font-semibold text-sm transition"
          >
            {{ t('manager.fraud.show.block') }}
          </button>
        </div>
      </div>

      <!-- Customer & order info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer details -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-base font-semibold text-gray-900 mb-4">
            {{ t('manager.manualordermodal.customer_details') }}
          </h2>
          <dl class="space-y-3">
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('common.full_name') }}</dt>
              <dd class="font-medium text-gray-900">{{ order.customer?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('landlord.shopsearch.email') }}</dt>
              <dd class="font-medium text-gray-900">{{ order.customer?.email ?? '—' }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manager.fraud.show.order_ip') }}</dt>
              <dd class="font-mono text-gray-900">{{ order.customer?.ip ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Fraud flags summary -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-base font-semibold text-gray-900 mb-4">
            {{ t('manager.fraud.show.security_flags') }}
            <span
              v-if="order.fraudFlags?.length"
              class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold"
            >
              {{ order.fraudFlags.length }}
            </span>
          </h2>
          <ul v-if="order.fraudFlags?.length" class="space-y-2">
            <li
              v-for="flag in order.fraudFlags"
              :key="flag.id ?? flag.flag_type"
              class="flex items-start gap-3 p-3 bg-red-50 rounded-lg"
            >
              <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
              <div>
                <p class="text-sm font-semibold text-red-700">{{ flagLabel(flag.flag_type) }}</p>
                <p v-if="flag.details" class="text-xs text-red-500 mt-0.5">{{ flag.details }}</p>
                <p v-if="flag.score != null" class="text-xs text-gray-500 mt-0.5">
                  {{ t('manager.fraud.show.score') }}
                  <span class="font-semibold" :class="scoreClass(flag.score)">{{ flag.score }}</span>
                </p>
              </div>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-400 text-center py-4">{{ t('manager.fraud.show.no_security_flags') }}</p>
        </div>
      </div>

      <!-- Order items -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('common.order_items') }}</h2>
        <div v-if="order.items?.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.product') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.quantity') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.unit_price') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.total') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ item.name ?? item.product?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ item.quantity }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ formatPrice(item.price) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                  {{ formatPrice((item.price ?? 0) * (item.quantity ?? 1)) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-gray-400 text-center py-6">{{ t('manager.fraud.show.no_order_items') }}</p>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  order: {
    type: Object,
    required: true,
    // shape: { id, items, customer, fraudFlags }
  },
})

function release() {
  if (!confirm(t('manager.fraud.index.release_order_a', { a: props.order.id }))) return
  router.post(route('tenant.manager.fraud.release', props.order.id), {}, { preserveScroll: true })
}

function block() {
  if (!confirm(t('manager.fraud.show.block_order_a', { a: props.order.id }))) return
  router.post(route('tenant.manager.fraud.block', props.order.id), {}, { preserveScroll: true })
}

function flagLabel(type) {
  const labels = {
    email_blocklisted: t('manager.fraud.index.email_on_the_blocklist'),
    ip_blocklisted: t('manager.fraud.index.ip_on_the_blocklist'),
    ip_high_frequency: t('common.several_orders_from_one_ip'),
    email_high_frequency: t('common.several_orders_from_one_email_address'),
    high_value_no_history: t('common.a_large_order_from_someone_with'),
    card_bin_blocklisted: t('manager.fraud.show.card_on_the_blocklist'),
    multiple_failed_payments: t('common.repeated_failed_payments'),
  }
  return labels[type] ?? type
}

function scoreClass(score) {
  if (score >= 80) return 'text-red-700'
  if (score >= 40) return 'text-orange-600'
  return 'text-green-700'
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}
</script>
