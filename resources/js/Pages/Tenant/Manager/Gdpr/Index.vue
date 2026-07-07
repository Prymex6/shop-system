<template>
  <ManagerLayout :title="t('manager.gdpr.index.gdpr_data_deletion_requests')">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.gdpr.index.gdpr_data_deletion_requests') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.gdpr.index.customers_who_have_asked_for_their') }}</p>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.customer') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.shopsearch.email') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.gdpr.index.request_date') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!customers.length">
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.gdpr.index.no_gdpr_requests') }}
                </td>
              </tr>
              <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-semibold text-gray-900">{{ customer.name }}</div>
                  <div class="text-xs text-gray-400">ID: {{ customer.id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ customer.email }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ formatDate(customer.delete_requested_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <button
                    @click="anonymize(customer)"
                    class="bg-red-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-700 transition"
                  >
                    {{ t('manager.gdpr.index.anonymise') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Info Box -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <strong>{{ t('manager.gdpr.index.anonymisation') }}</strong>
        {{ t('manager.gdpr.index.replaces_the_customer_s_personal_data') }}
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  customers: { type: Array, default: () => [] },
})

function anonymize(customer) {
  if (!confirm(t('manager.gdpr.index.anonymise_the_data_of_a_b', { a: customer.name, b: customer.email }))) return
  router.post(
    route('tenant.manager.gdpr.anonymize', customer.id),
    {},
    {
      preserveScroll: true,
    },
  )
}

function formatDate(date) {
  return new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
