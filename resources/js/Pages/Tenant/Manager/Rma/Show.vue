<template>
  <ManagerLayout :title="`RMA: ${rma.rma_number}`">
    <div class="space-y-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm">
        <Link :href="route('tenant.manager.rma.index')" class="text-blue-600 hover:text-blue-900">RMA</Link>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700 font-medium">{{ rma.rma_number }}</span>
      </div>

      <!-- Header -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ rma.rma_number }}</h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ t('common.order_3') }}
              <Link
                :href="route('tenant.manager.orders.show', rma.order?.id)"
                class="text-blue-600 hover:underline font-medium"
                >#{{ rma.order?.order_number }}</Link
              >
            </p>
            <p class="text-sm text-gray-500">
              {{ t('common.customer_2') }}
              <span class="font-medium text-gray-700">{{ rma.order?.customer_name ?? '—' }}</span>
            </p>
          </div>
          <span :class="rmaBadge(rma.status)" class="px-3 py-1.5 rounded-full text-sm font-semibold shrink-0">
            {{ rmaStatusLabel(rma.status) }}
          </span>
        </div>

        <!-- Reason -->
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
            {{ t('common.reason_for_the_return') }}
          </p>
          <p class="text-sm text-gray-700">{{ rma.reason }}</p>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-bold text-gray-900">{{ t('common.products_to_return') }}</h2>
        </div>
        <div class="bg-white divide-y divide-gray-200">
          <div v-for="item in rma.items" :key="item.id" class="px-6 py-4 flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-900">{{ item.product?.name ?? item.name }}</p>
              <p v-if="item.condition_notes" class="text-xs text-gray-500 mt-0.5">Stan: {{ item.condition_notes }}</p>
            </div>
            <span class="text-sm font-semibold text-gray-700">{{ item.quantity }} szt.</span>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="font-bold text-gray-900 mb-4">{{ t('manager.rma.show.status_history') }}</h2>
        <div class="space-y-3">
          <div v-for="(event, idx) in timeline" :key="idx" class="flex items-start gap-3">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
              :class="event.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-400'"
            >
              <i :class="event.icon" class="text-sm"></i>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ event.label }}</p>
              <p v-if="event.date" class="text-xs text-gray-400">{{ formatDate(event.date) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3">
        <button
          v-if="rma.status === 'pending'"
          @click="updateStatus('approve')"
          class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
        >
          <i class="fa-solid fa-check mr-2"></i>{{ t('common.approve') }}
        </button>
        <button
          v-if="rma.status === 'pending'"
          @click="updateStatus('reject')"
          class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
        >
          <i class="fa-solid fa-xmark mr-2"></i>{{ t('common.reject') }}
        </button>
        <button
          v-if="rma.status === 'approved'"
          @click="updateStatus('received')"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
        >
          <i class="fa-solid fa-box mr-2"></i>{{ t('manager.purchaseorders.show.mark_as_received') }}
        </button>
        <button
          v-if="rma.status === 'received'"
          @click="updateStatus('refunded')"
          class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition"
        >
          <i class="fa-solid fa-money-bill-transfer mr-2"></i>{{ t('manager.rma.show.process_the_return') }}
        </button>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  rma: { type: Object, required: true },
})

const updateStatus = (action) => {
  router.patch(route('tenant.manager.rma.update', props.rma.id), { action }, { preserveScroll: true })
}

const rmaStatusLabel = (s) =>
  ({
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

const timeline = computed(() => {
  const steps = [
    { key: 'pending', label: t('common.request_submitted'), icon: 'fa-solid fa-file-alt', date: props.rma.created_at },
    {
      key: 'approved',
      label: t('manager.refunds.index.approved_2'),
      icon: 'fa-solid fa-check-circle',
      date: props.rma.approved_at,
    },
    {
      key: 'received',
      label: t('manager.rma.show.product_received'),
      icon: 'fa-solid fa-box-archive',
      date: props.rma.received_at,
    },
    {
      key: 'refunded',
      label: t('common.refund'),
      icon: 'fa-solid fa-money-bill-transfer',
      date: props.rma.refunded_at,
    },
  ]
  const statuses = ['pending', 'approved', 'received', 'refunded']
  const currentIdx = statuses.indexOf(props.rma.status)
  return steps.map((s, i) => ({ ...s, active: i <= currentIdx }))
})

const formatDate = (d) =>
  d
    ? new Date(d).toLocaleString(locale.value, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : null
</script>
