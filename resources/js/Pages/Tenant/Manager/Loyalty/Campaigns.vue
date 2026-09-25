<template>
  <Head :title="t('manager.loyalty.campaigns.loyalty_campaigns')" />
  <ManagerLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.loyalty.campaigns.loyalty_campaigns') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.loyalty.campaigns.points_multipliers_on_chosen_days_or') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Link
            :href="route('tenant.manager.loyalty.index')"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50"
          >
            {{ t('common.back') }}
          </Link>
          <button
            @click="openModal()"
            class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600"
          >
            {{ t('manager.loyalty.campaigns.add_campaign') }}
          </button>
        </div>
      </div>

      <div
        v-if="$page.props.flash?.success"
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"
      >
        {{ $page.props.flash.success }}
      </div>

      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.campaigns.campaign') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('common.multiplier') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.campaigns.range') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.campaigns.days_of_the_week') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ campaign.name }}</div>
                <div v-if="campaign.valid_from || campaign.valid_until" class="text-xs text-gray-400 mt-0.5">
                  {{ campaign.valid_from ? fmtDate(campaign.valid_from) : '...' }}
                  →
                  {{ campaign.valid_until ? fmtDate(campaign.valid_until) : 'bezterminowo' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-lg font-bold text-amber-600">×{{ campaign.multiplier }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ appliesLabel(campaign) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{
                  campaign.day_of_week !== null
                    ? dayName(campaign.day_of_week)
                    : t('manager.loyalty.campaigns.every_day')
                }}
              </td>
              <td class="px-6 py-4">
                <span
                  :class="campaign.is_active ? 'text-green-600 bg-green-50' : 'text-gray-500 bg-gray-100'"
                  class="px-2 py-1 rounded text-xs font-medium"
                >
                  {{ campaign.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openModal(campaign)" class="text-sm text-blue-600 hover:text-blue-700">
                  {{ t('common.edit') }}
                </button>
                <button @click="deleteCampaign(campaign)" class="text-sm text-red-500 hover:text-red-600">
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
            <tr v-if="campaigns.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                {{ t('manager.loyalty.campaigns.no_campaigns_yet_click_add_campaign') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-screen overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">
            {{ modal.campaign ? t('common.edit_campaign') : t('common.add_campaign') }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.campaign_name') }}</label>
              <input
                v-model="form.name"
                name="name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.points_multiplier') }}</label>
                <input
                  v-model.number="form.multiplier"
                  name="multiplier"
                  type="number"
                  min="1"
                  max="10"
                  step="0.1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
                <p class="text-xs text-gray-400 mt-0.5">{{ t('manager.loyalty.campaigns.e_g_2_0_double_points') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.campaigns.applies_to')
                }}</label>
                <select v-model="form.applies_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                  <option value="all">{{ t('manager.loyalty.campaigns.products_in_total') }}</option>
                  <option value="category">{{ t('manager.loyalty.campaigns.categories') }}</option>
                  <option value="product">{{ t('manager.loyalty.campaigns.product') }}</option>
                </select>
              </div>
            </div>

            <div v-if="form.applies_to === 'category'">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.category') }}</label>
              <select
                v-model.number="form.target_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              >
                <option :value="null" disabled>{{ t('manager.loyalty.campaigns.choose_a_category') }}</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div v-else-if="form.applies_to === 'product'">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.product') }}</label>
              <select
                v-model.number="form.target_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              >
                <option :value="null" disabled>{{ t('common.choose_a_product') }}</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.loyalty.campaigns.day_of_the_week_empty_every')
              }}</label>
              <select v-model="form.day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option :value="null">{{ t('manager.loyalty.campaigns.every_day') }}</option>
                <option :value="0">{{ t('manager.loyalty.campaigns.sunday') }}</option>
                <option :value="1">{{ t('manager.loyalty.campaigns.monday') }}</option>
                <option :value="2">{{ t('manager.loyalty.campaigns.tuesday') }}</option>
                <option :value="3">{{ t('manager.loyalty.campaigns.wednesday') }}</option>
                <option :value="4">{{ t('manager.loyalty.campaigns.thursday') }}</option>
                <option :value="5">{{ t('manager.loyalty.campaigns.friday') }}</option>
                <option :value="6">{{ t('manager.loyalty.campaigns.saturday') }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.valid_from') }}</label>
                <input
                  v-model="form.valid_from"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.valid_until') }}</label>
                <input
                  v-model="form.valid_until"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.is_active" class="h-4 w-4 text-blue-600 rounded" />
              <span class="text-sm text-gray-700">{{ t('manager.loyalty.campaigns.campaign_active') }}</span>
            </label>

            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="modal.open = false"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="flex-1 px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 disabled:opacity-50"
              >
                {{ form.processing ? 'Zapisywanie...' : t('common.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({ campaigns: Array, products: Array, categories: Array })

const modal = reactive({ open: false, campaign: null })

const form = useForm({
  name: '',
  multiplier: 2.0,
  applies_to: 'all',
  target_id: null,
  day_of_week: null,
  valid_from: null,
  valid_until: null,
  is_active: true,
})

const openModal = (campaign = null) => {
  modal.campaign = campaign
  modal.open = true
  if (campaign) {
    form.name = campaign.name
    form.multiplier = campaign.multiplier
    form.applies_to = campaign.applies_to
    form.target_id = campaign.target_id
    form.day_of_week = campaign.day_of_week
    form.valid_from = campaign.valid_from ? campaign.valid_from.slice(0, 16) : null
    form.valid_until = campaign.valid_until ? campaign.valid_until.slice(0, 16) : null
    form.is_active = campaign.is_active
  } else {
    form.reset()
    form.multiplier = 2.0
    form.applies_to = 'all'
    form.is_active = true
  }
}

const submitForm = () => {
  if (modal.campaign) {
    form.put(route('tenant.manager.loyalty.campaigns.update', modal.campaign.id), {
      onSuccess: () => {
        modal.open = false
      },
    })
  } else {
    form.post(route('tenant.manager.loyalty.campaigns.store'), {
      onSuccess: () => {
        modal.open = false
      },
    })
  }
}

watch(
  () => form.applies_to,
  () => {
    form.target_id = null
  },
)

const deleteCampaign = (campaign) => {
  if (!confirm(t('manager.loyalty.campaigns.delete_the_campaign_a', { a: campaign.name }))) return
  router.delete(route('tenant.manager.loyalty.campaigns.destroy', campaign.id))
}

const dayNames = [
  t('manager.loyalty.campaigns.sunday'),
  t('manager.loyalty.campaigns.monday'),
  t('manager.loyalty.campaigns.tuesday'),
  t('manager.loyalty.campaigns.wednesday'),
  t('manager.loyalty.campaigns.thursday'),
  t('manager.loyalty.campaigns.friday'),
  t('manager.loyalty.campaigns.saturday'),
]
const dayName = (d) => dayNames[d] || d

const appliesLabel = (c) => {
  if (c.applies_to === 'all') return t('common.all_products')
  if (c.applies_to === 'category') {
    const cat = props.categories?.find((x) => x.id === c.target_id)
    return cat
      ? t('manager.loyalty.campaigns.category_a', { a: cat.name })
      : t('manager.loyalty.campaigns.category_number', { a: c.target_id })
  }
  const prod = props.products?.find((x) => x.id === c.target_id)
  return prod
    ? t('manager.loyalty.campaigns.product_a', { a: prod.name })
    : t('manager.loyalty.campaigns.product_number', { a: c.target_id })
}

const fmtDate = (d) => (d ? d.slice(0, 10) : '')
</script>
