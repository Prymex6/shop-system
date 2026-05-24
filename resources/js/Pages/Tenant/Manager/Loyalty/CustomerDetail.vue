<template>
  <ManagerLayout :title="t('manager.loyalty.customerdetail.loyalty_a', { a: customer.name })">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <Link
            :href="route('tenant.manager.loyalty.index')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            >{{ t('manager.loyalty.customerdetail.larr_loyalty_programme') }}</Link
          >
          <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ customer.name }}</h1>
          <p class="text-sm text-gray-500">{{ customer.email }}</p>
        </div>
        <button
          @click="showPointsModal = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.loyalty.customerdetail.add_subtract_points') }}
        </button>
      </div>

      <!-- Tier summary -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="w-4 h-4 rounded-full" :style="{ backgroundColor: tierInfo.color }"></span>
            <span class="text-lg font-bold text-gray-900">{{ tierInfo.name }}</span>
          </div>
          <span class="text-2xl font-bold text-gray-900">{{ customer.loyalty_points ?? 0 }} pkt</span>
        </div>
        <div v-if="nextTier" class="text-sm text-gray-500">
          {{ t('manager.loyalty.customerdetail.missing') }}
          <strong class="text-gray-700">{{ pointsToNext }} pkt</strong>
          {{ t('manager.loyalty.customerdetail.to_tier') }}
          <strong :style="{ color: nextTier.color }">{{ nextTier.name }}</strong>
        </div>
        <div v-else class="text-sm text-gray-500">
          {{ t('manager.loyalty.customerdetail.the_highest_tier_there_is') }}
        </div>
      </div>

      <!-- Points history -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-bold text-gray-900">{{ t('common.points_history') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.date') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.fraud.index.type') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.description') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.points') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="p in pointsHistory" :key="p.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(p.created_at) }}</td>
              <td class="px-4 py-3 text-gray-700">{{ typeLabel(p.type) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ p.description || '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="p.points >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ p.points >= 0 ? '+' : '' }}{{ p.points }}
              </td>
            </tr>
            <tr v-if="!pointsHistory.length">
              <td colspan="4" class="text-center py-10 text-gray-400">{{ t('common.no_points_history') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Redemptions -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-bold text-gray-900">{{ t('manager.loyalty.customerdetail.rewards_redeemed') }}</h2>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.date') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.reward') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.loyalty.customerdetail.cost_pts') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.loyalty.customerdetail.discount_value') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="r in redemptions" :key="r.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(r.created_at) }}</td>
              <td class="px-4 py-3 text-gray-700">{{ r.reward?.name ?? r.description ?? '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ r.points_spent }}</td>
              <td class="px-4 py-3 text-right text-gray-900">
                {{ r.discount_value ? formatPrice(r.discount_value) : '—' }}
              </td>
            </tr>
            <tr v-if="!redemptions.length">
              <td colspan="4" class="text-center py-10 text-gray-400">
                {{ t('manager.loyalty.customerdetail.no_rewards_redeemed') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/remove points modal -->
    <div
      v-if="showPointsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showPointsModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-md p-6 space-y-4">
        <h3 class="font-bold text-lg text-gray-900">{{ t('manager.loyalty.customerdetail.add_subtract_points') }}</h3>
        <p class="text-sm text-gray-500">
          {{ t('manager.loyalty.customerdetail.currently') }} <strong>{{ customer.loyalty_points ?? 0 }} pkt</strong>
        </p>
        <form @submit.prevent="submitPoints" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.loyalty.customerdetail.points_negative_to_subtract')
            }}</label>
            <input
              v-model.number="pointsForm.points"
              type="number"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
            <p v-if="pointsForm.errors.points" class="text-red-600 text-sm mt-1">{{ pointsForm.errors.points }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.loyalty.customerdetail.reason')
            }}</label>
            <input
              v-model="pointsForm.description"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
            <p v-if="pointsForm.errors.description" class="text-red-600 text-sm mt-1">
              {{ pointsForm.errors.description }}
            </p>
          </div>
          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showPointsModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="pointsForm.processing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  customer: { type: Object, required: true },
  pointsHistory: { type: Array, default: () => [] },
  redemptions: { type: Array, default: () => [] },
  tiers: { type: Object, default: () => ({}) },
  tierInfo: { type: Object, required: true },
  nextTier: { type: Object, default: null },
  pointsToNext: { type: Number, default: null },
})

const showPointsModal = ref(false)
const pointsForm = useForm({ points: null, description: '' })

const submitPoints = () => {
  pointsForm.post(route('tenant.manager.loyalty.add-points', props.customer.id), {
    onSuccess: () => {
      showPointsModal.value = false
      pointsForm.reset()
    },
  })
}

const typeLabels = {
  earned: t('manager.loyalty.customerdetail.awarded'),
  bonus_first_order: t('common.bonus_first_order'),
  bonus_monthly: t('client.account.monthly_bonus'),
  bonus_referral: t('manager.loyalty.customerdetail.referral_bonus'),
  redeemed: t('manager.loyalty.customerdetail.used'),
  expired: t('common.expired_5'),
  manual: t('common.manual_adjustment_2'),
  revoked: t('common.reverted'),
}
const typeLabel = (type) => typeLabels[type] ?? type

const formatDate = (date) =>
  new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
const formatPrice = (val) =>
  new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
</script>
