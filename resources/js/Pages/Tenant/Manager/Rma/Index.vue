<template>
  <ManagerLayout :title="t('manager.rma.index.rma_returns')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.rma.index.rma_returns_and_complaints') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('manager.rma.index.manage_customers_return_requests') }}</p>
      </div>

      <!-- Status filters -->
      <div class="flex gap-2 flex-wrap">
        <button
          v-for="s in ['', 'pending', 'approved', 'rejected', 'received', 'refunded']"
          :key="s"
          @click="filterStatus = s"
          :class="
            filterStatus === s
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-300'
          "
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
        >
          {{ rmaStatusLabel(s) || t('common.all') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.rma.index.rma_no') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.order') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.customer') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.rma.index.placed_on') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="rma in filteredRma" :key="rma.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-semibold text-gray-900">{{ rma.rma_number }}</td>
              <td class="px-4 py-3">
                <Link
                  :href="route('tenant.manager.orders.show', rma.order?.id)"
                  class="text-blue-600 hover:underline text-xs"
                >
                  #{{ rma.order?.order_number ?? '—' }}
                </Link>
              </td>
              <td class="px-4 py-3 text-gray-700">{{ rma.order?.customer_name ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="rmaBadge(rma.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ rmaStatusLabel(rma.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(rma.created_at) }}</td>
              <td class="px-4 py-3 text-right">
                <Link
                  :href="route('tenant.manager.rma.show', rma.id)"
                  class="text-blue-600 hover:text-blue-900 text-xs font-medium"
                >
                  {{ t('common.details') }}
                </Link>
              </td>
            </tr>
            <tr v-if="!filteredRma.length">
              <td colspan="6" class="text-center py-12 text-gray-400">{{ t('manager.rma.index.no_rma_requests') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="rmaList.last_page > 1" class="flex justify-center gap-2">
        <Link
          v-for="link in rmaList.links"
          :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="px-3 py-2 rounded-lg text-sm border transition"
          :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:border-blue-300'"
        />
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  rmaList: { type: Object, required: true },
})

const filterStatus = ref('')

const filteredRma = computed(() => {
  const all = props.rmaList.data ?? []
  if (!filterStatus.value) return all
  return all.filter((r) => r.status === filterStatus.value)
})

const rmaStatusLabel = (s) =>
  ({
    '': t('common.all'),
    pending: t('manager.orders.index.pending'),
    approved: t('manager.refunds.index.approved_2'),
    rejected: t('manager.refunds.index.rejected_2'),
    received: t('manager.purchaseorders.show.received'),
    refunded: t('common.refunded'),
  })[s] ?? s

const rmaBadge = (s) =>
  ({
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    rejected: 'bg-red-100 text-red-700',
    received: 'bg-blue-100 text-blue-700',
    refunded: 'bg-green-100 text-green-700',
  })[s] ?? 'bg-gray-100 text-gray-600'

const formatDate = (d) =>
  new Date(d).toLocaleDateString(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric' })
</script>
