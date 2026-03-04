<template>
  <LandlordLayout :title="t('landlord.plans.index.plans')">
    <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <div></div>
          <Link
            :href="route('landlord.plans.create')"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg"
          >
            {{ t('landlord.plans.index.add_plan') }}
          </Link>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.price') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('landlord.plans.index.max_orders_month') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('layout.landlordlayout.shops') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="plan in plans" :key="plan.id">
                <td class="px-6 py-4 font-medium text-gray-900">{{ plan.name }}</td>
                <td class="px-6 py-4 text-gray-700">
                  {{ plan.price ? plan.price + ' PLN/rok' : '—' }}
                </td>
                <td class="px-6 py-4 text-gray-700">
                  {{ plan.max_orders_per_month ?? t('landlord.plans.form.unlimited') }}
                </td>
                <td class="px-6 py-4 text-gray-700">{{ plan.tenants_count }}</td>
                <td class="px-6 py-4">
                  <span
                    :class="plan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                    class="px-2 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ plan.is_active ? t('common.active') : t('common.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                  <Link
                    :href="route('landlord.plans.edit', plan.id)"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                    >{{ t('common.edit') }}</Link
                  >
                  <Link
                    :href="route('landlord.plans.destroy', plan.id)"
                    method="delete"
                    as="button"
                    class="text-red-600 hover:text-red-800 text-sm"
                    @click.prevent="confirmDelete(plan)"
                    >{{ t('common.delete') }}</Link
                  >
                </td>
              </tr>
              <tr v-if="plans.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                  {{ t('landlord.plans.index.no_plans') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  plans: { type: Array, default: () => [] },
})

function confirmDelete(plan) {
  if (confirm(t('landlord.plans.index.delete_the_plan_a', { a: plan.name }))) {
    router.delete(route('landlord.plans.destroy', plan.id))
  }
}
</script>
