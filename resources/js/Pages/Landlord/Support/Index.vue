<template>
  <LandlordLayout :title="t('landlord.support.index.support_tickets')">
    <div class="py-12">
      <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="flex gap-2 mb-6">
          <button
            v-for="s in statuses"
            :key="s.value"
            @click="filterStatus(s.value)"
            :class="
              filters.status === s.value
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            "
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          >
            {{ s.label }}
          </button>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('pages.landing.subject') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.shop') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('landlord.support.index.priority') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.date') }}</th>
                <th class="px-6 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-gray-500 text-sm">#{{ ticket.id }}</td>
                <td class="px-6 py-4 font-medium text-gray-900">{{ ticket.subject }}</td>
                <td class="px-6 py-4 text-gray-700 text-sm">{{ tenants[ticket.tenant_id] ?? ticket.tenant_id }}</td>
                <td class="px-6 py-4">
                  <span
                    :class="priorityClass(ticket.priority)"
                    class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
                  >
                    {{ priorityLabel(ticket.priority) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="statusClass(ticket.status)"
                    class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
                  >
                    {{ statusLabel(ticket.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500 text-sm">{{ formatDate(ticket.created_at) }}</td>
                <td class="px-6 py-4 text-right">
                  <Link
                    :href="route('landlord.support.show', ticket.id)"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                  >
                    {{ t('common.open') }}
                  </Link>
                </td>
              </tr>
              <tr v-if="tickets.data.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                  {{ t('landlord.support.index.no_tickets') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.last_page > 1" class="mt-4 flex justify-center gap-2">
          <Link
            v-for="page in tickets.last_page"
            :key="page"
            :href="tickets.links.find((l) => l.label == page)?.url"
            :class="
              tickets.current_page === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'
            "
            class="px-3 py-1 rounded text-sm"
            >{{ page }}</Link
          >
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  tickets: Object,
  tenants: Object,
  filters: Object,
})

const statuses = [
  { value: '', label: t('common.all') },
  { value: 'open', label: t('landlord.support.index.open') },
  { value: 'in_progress', label: t('landlord.support.index.in_progress') },
  { value: 'resolved', label: t('landlord.support.show.resolved') },
  { value: 'closed', label: t('common.closed') },
]

function filterStatus(status) {
  router.get(route('landlord.support.index'), { status: status || undefined }, { preserveState: true })
}

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-800',
      high: 'bg-orange-100 text-orange-800',
      normal: 'bg-blue-100 text-blue-800',
      low: 'bg-gray-100 text-gray-600',
    }[p] || ''
  )
}
function priorityLabel(p) {
  return (
    {
      urgent: t('landlord.support.index.urgent'),
      high: t('landlord.support.index.high'),
      normal: t('landlord.support.index.normal'),
      low: t('landlord.support.index.low'),
    }[p] || p
  )
}
function statusClass(s) {
  return (
    {
      open: 'bg-yellow-100 text-yellow-800',
      in_progress: 'bg-blue-100 text-blue-800',
      resolved: 'bg-green-100 text-green-800',
      closed: 'bg-gray-100 text-gray-600',
    }[s] || ''
  )
}
function statusLabel(s) {
  return (
    {
      open: t('landlord.support.index.open'),
      in_progress: t('landlord.support.index.in_progress'),
      resolved: t('landlord.support.show.resolved'),
      closed: t('common.closed'),
    }[s] || s
  )
}
function formatDate(d) {
  return new Date(d).toLocaleDateString(locale.value, { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>
