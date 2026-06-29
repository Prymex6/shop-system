<template>
  <ManagerLayout :title="t('manager.refunds.index.returns')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.refunds.index.returns') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.refunds.index.return_requests_from_customers') }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 flex flex-wrap gap-3">
        <select
          v-model="filters.status"
          @change="applyFilters"
          class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ t('common.all_statuses') }}</option>
          <option value="pending">{{ t('common.pending') }}</option>
          <option value="approved">{{ t('manager.refunds.index.approved') }}</option>
          <option value="rejected">{{ t('manager.refunds.index.rejected') }}</option>
          <option value="completed">{{ t('common.completed') }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.order') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.customer') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.reason') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.customers.show.amount') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.status') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.date') }}
              </th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="refunds.data.length === 0">
              <td colspan="7" class="text-center py-12 text-gray-400">
                {{ t('manager.refunds.index.no_return_requests') }}
              </td>
            </tr>
            <tr v-for="refund in refunds.data" :key="refund.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-semibold text-blue-600">#{{ refund.order?.order_number ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-700">
                {{ refund.order?.customer_name ?? refund.order?.customer?.name ?? '—' }}
              </td>
              <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ refund.reason ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900">
                {{ formatPrice(refund.amount) }}
              </td>
              <td class="px-4 py-3 text-center">
                <span
                  class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="statusClass(refund.status)"
                >
                  {{ statusLabel(refund.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-gray-400 text-xs">
                {{ formatDate(refund.created_at) }}
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <Link
                    :href="route('tenant.manager.refunds.show', refund.id)"
                    class="text-xs text-blue-600 hover:text-blue-900 font-medium"
                    >{{ t('common.details') }}</Link
                  >

                  <template v-if="refund.status === 'pending'">
                    <button @click="approve(refund)" class="text-xs text-green-600 hover:text-green-800 font-medium">
                      {{ t('common.approve') }}
                    </button>
                    <button @click="reject(refund)" class="text-xs text-red-600 hover:text-red-800 font-medium">
                      {{ t('common.reject') }}
                    </button>
                  </template>

                  <button
                    v-if="refund.status === 'approved'"
                    @click="process(refund)"
                    class="text-xs bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 font-medium"
                  >
                    {{ t('manager.refunds.index.transfer_the_refund') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="refunds.last_page > 1" class="flex justify-center gap-2 mt-6">
        <Link
          v-for="link in refunds.links"
          :key="link.label"
          :href="link.url ?? '#'"
          :class="[
            'px-3 py-1.5 rounded-lg text-sm border',
            link.active
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const props = defineProps({
  refunds: Object,
  filters: Object,
})

const filters = ref({ status: props.filters?.status ?? '' })

function applyFilters() {
  router.get(route('tenant.manager.refunds.index'), filters.value, { preserveState: true, replace: true })
}

function approve(refund) {
  if (!confirm(t('common.approve_the_return_request'))) return
  router.patch(route('tenant.manager.refunds.approve', refund.id))
}

function reject(refund) {
  if (!confirm(t('common.reject_the_return_request'))) return
  router.patch(route('tenant.manager.refunds.reject', refund.id))
}

function process(refund) {
  if (!confirm(t('manager.refunds.index.process_a_refund_of_a_through', { a: formatPrice(refund.amount) }))) return
  router.post(route('tenant.manager.refunds.process', refund.id))
}

function formatPrice(val) {
  return Number(val).toLocaleString(locale.value, { minimumFractionDigits: 2 }) + ' ' + t('common.currency_pln')
}

function formatDate(date) {
  return date ? new Date(date).toLocaleDateString(locale.value) : '—'
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
