<template>
  <Head :title="t('manager.loyalty.index.loyalty_programme')" />
  <ManagerLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.loyalty.index.loyalty_programme') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.loyalty.index.manage_customers_points_and_tiers') }}</p>
        </div>
        <div class="flex gap-2">
          <Link
            :href="route('tenant.manager.loyalty.rewards.index')"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors"
          >
            {{ t('manager.loyalty.index.rewards') }}
          </Link>
          <Link
            :href="route('tenant.manager.loyalty.campaigns.index')"
            class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors"
          >
            {{ t('manager.loyalty.index.campaigns') }}
          </Link>
        </div>
      </div>

      <!-- Tiers Info -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div
          v-for="(tier, key) in tiers"
          :key="key"
          class="bg-white rounded-lg p-4 text-center shadow-sm border border-gray-100"
        >
          <div class="text-lg font-bold mb-1" :style="{ color: tier.color }">{{ tier.name }}</div>
          <div class="text-xs text-gray-500">od {{ tier.min }} pkt</div>
          <div class="text-xs text-gray-400 mt-1">×{{ tier.multiplier }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-center">
        <input
          v-model="search"
          type="text"
          :placeholder="t('manager.loyalty.index.search_by_name_or_email')"
          class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          @keydown.enter="applyFilters"
        />
        <select
          v-model="tierFilter"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          @change="applyFilters"
        >
          <option value="">{{ t('manager.loyalty.index.all_tiers') }}</option>
          <option v-for="(tier, key) in tiers" :key="key" :value="key">{{ tier.name }}</option>
        </select>
        <button @click="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
          {{ t('common.search') }}
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('common.customer') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.index.tier') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.index.balance_total') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('common.orders_2') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ customer.name }}</div>
                <div class="text-sm text-gray-500">{{ customer.email }}</div>
                <div v-if="customer.phone" class="text-sm text-gray-400">{{ customer.phone }}</div>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :style="{
                    backgroundColor: getTierColor(customer.loyalty_tier) + '20',
                    color: getTierColor(customer.loyalty_tier),
                  }"
                >
                  {{ getTierName(customer.loyalty_tier) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="text-lg font-bold text-gray-900">{{ customer.loyalty_points ?? 0 }}</span>
                <span class="text-gray-400 text-sm"> pkt</span>
                <div class="text-xs text-gray-400 mt-0.5">
                  {{ t('common.total_a_pts', { a: customer.loyalty_points_earned_total ?? 0 }) }}
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">
                {{ customer.orders_count }}
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <Link
                  :href="route('tenant.manager.loyalty.customer-detail', customer.id)"
                  class="text-sm text-gray-600 hover:text-gray-800 font-medium"
                >
                  {{ t('manager.inventory.index.history') }}
                </Link>
                <button
                  @click="openPointsModal(customer)"
                  class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                >
                  {{ t('common.points') }}
                </button>
              </td>
            </tr>
            <tr v-if="customers.data.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                {{ t('manager.loyalty.index.no_customers_match_this_search') }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="customers.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex justify-center gap-1">
          <template v-for="link in customers.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              v-html="link.label"
              :class="[
                'px-3 py-1 text-sm rounded',
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
              ]"
            />
            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
          </template>
        </div>
      </div>
    </div>

    <!-- Add points dialog -->
    <div v-if="pointsModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-1">{{ t('manager.loyalty.index.manage_points') }}</h3>
          <p class="text-sm text-gray-500 mb-4">
            {{ t('common.customer_2') }} <strong>{{ pointsModal.customer?.name }}</strong> (aktualnie:
            {{ pointsModal.customer?.loyalty_points ?? 0 }} pkt)
          </p>

          <form @submit.prevent="submitPoints" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('manager.loyalty.index.points_to_add_to_subtract') }}
              </label>
              <input
                v-model.number="pointsForm.points"
                type="number"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                :placeholder="t('manager.loyalty.index.e_g_50_or_20')"
              />
              <p v-if="pointsForm.errors.points" class="mt-1 text-sm text-red-600">{{ pointsForm.errors.points }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.reason') }}</label>
              <input
                v-model="pointsForm.description"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                :placeholder="t('manager.loyalty.index.e_g_points_correction_birthday_bonus')"
              />
              <p v-if="pointsForm.errors.description" class="mt-1 text-sm text-red-600">
                {{ pointsForm.errors.description }}
              </p>
            </div>
            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="pointsModal.open = false"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="pointsForm.processing"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
              >
                {{ pointsForm.processing ? 'Zapisywanie...' : t('common.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  customers: Object,
  tiers: Object,
  filters: Object,
})

const search = ref(props.filters?.search || '')
const tierFilter = ref(props.filters?.tier || '')

const applyFilters = () => {
  router.get(
    route('tenant.manager.loyalty.index'),
    {
      search: search.value || undefined,
      tier: tierFilter.value || undefined,
    },
    { preserveState: true, replace: true },
  )
}

const getTierName = (tier) => props.tiers[tier]?.name || t('common.bronze')
const getTierColor = (tier) => props.tiers[tier]?.color || '#cd7f32'

const pointsModal = reactive({ open: false, customer: null })
const pointsForm = useForm({ points: null, description: '' })

const openPointsModal = (customer) => {
  pointsModal.customer = customer
  pointsModal.open = true
  pointsForm.reset()
}

const submitPoints = () => {
  pointsForm.post(route('tenant.manager.loyalty.add-points', pointsModal.customer.id), {
    onSuccess: () => {
      pointsModal.open = false
    },
  })
}
</script>
