<template>
  <LandlordLayout title="Dashboard">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard
            :title="t('common.all_shops')"
            icon='<i class="fa-solid fa-store text-blue-600"></i>'
            :value="stats.total_tenants"
            color="blue"
          />
          <StatCard
            :title="t('components.quickcontrols.active')"
            icon='<i class="fa-solid fa-circle-check text-green-600"></i>'
            :value="stats.active_tenants"
            color="green"
          />
          <StatCard
            :title="t('landlord.dashboard.trial')"
            icon='<i class="fa-solid fa-clock text-yellow-600"></i>'
            :value="stats.trial_tenants"
            color="yellow"
          />
          <StatCard
            :title="t('landlord.dashboard.monthly_revenue')"
            icon='<i class="fa-solid fa-coins text-purple-600"></i>'
            :value="`${stats.total_revenue} PLN`"
            color="purple"
          />
        </div>

        <!-- Recent Tenants -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('landlord.dashboard.newest_shops') }}</h2>
          </div>
          <div class="divide-y divide-gray-200">
            <div v-for="tenant in stats.recent_tenants" :key="tenant.id" class="px-6 py-4 hover:bg-gray-50 transition">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-sm font-medium text-gray-900">{{ tenant.name }}</h3>
                  <p class="text-sm text-gray-500">{{ tenant.plan?.name }}</p>
                </div>
                <span
                  :class="{
                    'bg-green-100 text-green-800': tenant.status === 'active',
                    'bg-yellow-100 text-yellow-800': tenant.status === 'trial',
                    'bg-red-100 text-red-800': tenant.status === 'suspended',
                  }"
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ tenant.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import StatCard from '@/Components/Landlord/StatCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  stats: Object,
})
</script>
