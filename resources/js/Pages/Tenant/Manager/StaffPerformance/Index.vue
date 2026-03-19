<template>
  <ManagerLayout :title="t('manager.staffperformance.index.staff_performance')">
    <div class="space-y-6">
      <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.staffperformance.index.staff_performance') }}</h1>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('common.date_from') }}</label>
          <input
            v-model="filters.date_from"
            type="date"
            @change="applyFilters"
            class="px-3 py-2 border border-gray-200 rounded-lg text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">{{ t('common.date_to') }}</label>
          <input
            v-model="filters.date_to"
            type="date"
            @change="applyFilters"
            class="px-3 py-2 border border-gray-200 rounded-lg text-sm"
          />
        </div>
        <button
          @click="resetFilters"
          class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-500 hover:bg-gray-50"
        >
          {{ t('manager.staffperformance.index.reset') }}
        </button>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white shadow rounded-lg p-5 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.staffperformance.index.orders_today') }}
          </p>
          <p class="text-3xl font-bold text-gray-900">{{ summary.orders_today ?? 0 }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.staffperformance.index.avg_handling_time') }}
          </p>
          <p class="text-3xl font-bold text-gray-900">
            {{ summary.avg_processing_time ?? '—' }} <span class="text-base font-normal text-gray-400">min</span>
          </p>
        </div>
        <div class="bg-white shadow rounded-lg p-5 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.staffperformance.index.active_staff') }}
          </p>
          <p class="text-3xl font-bold text-gray-900">{{ staffStats.length }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
            {{ t('manager.staffperformance.index.reports_submitted') }}
          </p>
          <p class="text-3xl font-bold text-gray-900">{{ summary.reports_total ?? 0 }}</p>
        </div>
      </div>

      <!-- Staff table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('pages.landing.staff_member') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.rolepermissions.role') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.staffperformance.index.orders_processed') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.staffperformance.index.orders_fulfilled') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.staffperformance.index.reports_submitted') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.staffperformance.index.avg_handling_time') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="staff in staffStats" :key="staff.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm"
                  >
                    {{ staff.name.charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-medium text-gray-900">{{ staff.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">{{
                  staff.role
                }}</span>
              </td>
              <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ staff.orders_processed ?? 0 }}</td>
              <td class="px-4 py-3 text-center font-semibold text-green-700">{{ staff.orders_fulfilled ?? 0 }}</td>
              <td class="px-4 py-3 text-center text-gray-600">{{ staff.reports_count ?? 0 }}</td>
              <td class="px-4 py-3 text-center text-gray-600">
                {{ staff.avg_processing_time ? staff.avg_processing_time + ' min' : '—' }}
              </td>
            </tr>
            <tr v-if="!staffStats.length">
              <td colspan="6" class="text-center py-12 text-gray-400">
                {{ t('manager.staffperformance.index.no_data_for_the_chosen_period') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  staffStats: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
})

const applyFilters = () => {
  router.get(
    route('tenant.manager.staff.performance'),
    {
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
    },
    { preserveState: true, replace: true },
  )
}

const resetFilters = () => {
  filters.date_from = ''
  filters.date_to = ''
  applyFilters()
}
</script>
