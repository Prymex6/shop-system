<template>
  <ManagerLayout :title="`Zwrot — #${refund.order?.order_number ?? refund.id}`">
    <div class="space-y-6 max-w-3xl">
      <div class="flex items-center justify-between">
        <div>
          <Link
            :href="route('tenant.manager.refunds.index')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            >{{ t('manager.refunds.show.larr_returns') }}</Link
          >
          <h1 class="text-3xl font-bold text-gray-900 mt-1">Zwrot #{{ refund.order?.order_number ?? refund.id }}</h1>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold" :class="statusClass(refund.status)">
          {{ statusLabel(refund.status) }}
        </span>
      </div>

      <div class="bg-white shadow rounded-lg p-6 grid grid-cols-2 gap-6">
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ t('common.customer') }}</p>
          <p class="text-sm text-gray-900 font-medium">
            {{ refund.order?.customer_name ?? refund.order?.customer?.name ?? '—' }}
          </p>
          <p class="text-xs text-gray-500">{{ refund.order?.customer_email ?? refund.order?.customer?.email ?? '' }}</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.refunds.show.refund_amount') }}
          </p>
          <p class="text-lg text-gray-900 font-bold">{{ formatPrice(refund.amount) }}</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.refunds.show.reported_on') }}
          </p>
          <p class="text-sm text-gray-700">{{ formatDate(refund.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
            {{ t('common.payment_method') }}
          </p>
          <p class="text-sm text-gray-700">{{ refund.order?.payment_method ?? '—' }}</p>
        </div>
        <div class="col-span-2">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ t('common.reason') }}</p>
          <p class="text-sm text-gray-700">{{ refund.reason ?? '—' }}</p>
        </div>
        <div v-if="refund.notes" class="col-span-2">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.purchaseorders.create.notes') }}
          </p>
          <p class="text-sm text-gray-700">{{ refund.notes }}</p>
        </div>
      </div>

      <!-- Order items -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-bold text-gray-900">{{ t('manager.refunds.show.products_from_the_order') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.product') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.quantity') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.price') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in refund.order?.items ?? []" :key="item.id">
              <td class="px-4 py-3 text-gray-900">{{ item.product?.name ?? item.name }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ item.quantity }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatPrice(item.price) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3">
        <template v-if="refund.status === 'pending'">
          <button
            @click="approve"
            class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
          >
            <i class="fa-solid fa-check mr-2"></i>{{ t('common.approve') }}
          </button>
          <button
            @click="reject"
            class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
          >
            <i class="fa-solid fa-xmark mr-2"></i>{{ t('common.reject') }}
          </button>
        </template>
        <button
          v-if="refund.status === 'approved'"
          @click="process"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
        >
          <i class="fa-solid fa-money-bill-transfer mr-2"></i>{{ t('manager.refunds.index.transfer_the_refund') }}
        </button>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const props = defineProps({
  refund: { type: Object, required: true },
})

function approve() {
  if (!confirm(t('common.approve_the_return_request'))) return
  router.patch(route('tenant.manager.refunds.approve', props.refund.id))
}

function reject() {
  if (!confirm(t('common.reject_the_return_request'))) return
  router.patch(route('tenant.manager.refunds.reject', props.refund.id))
}

function process() {
  if (!confirm(t('manager.refunds.index.process_a_refund_of_a_through', { a: formatPrice(props.refund.amount) })))
    return
  router.post(route('tenant.manager.refunds.process', props.refund.id))
}

function formatPrice(val) {
  return Number(val).toLocaleString(locale.value, { minimumFractionDigits: 2 }) + ' ' + t('common.currency_pln')
}

function formatDate(date) {
  return date
    ? new Date(date).toLocaleString(locale.value, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '—'
}

function statusLabel(status) {
  return (
    {
      pending: t('common.pending_2'),
      approved: t('manager.refunds.index.approved_2'),
      rejected: t('manager.refunds.index.rejected_2'),
      completed: t('common.finished'),
    }[status] ?? status
  )
}

function statusClass(status) {
  return (
    {
      pending: 'bg-yellow-100 text-yellow-700',
      approved: 'bg-blue-100 text-blue-700',
      rejected: 'bg-red-100 text-red-700',
      completed: 'bg-green-100 text-green-700',
    }[status] ?? 'bg-gray-100 text-gray-600'
  )
}
</script>
