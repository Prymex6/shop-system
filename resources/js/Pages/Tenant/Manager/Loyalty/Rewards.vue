<template>
  <Head :title="t('manager.loyalty.rewards.loyalty_rewards')" />
  <ManagerLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.loyalty.rewards.loyalty_rewards') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.loyalty.rewards.rewards_that_can_be_redeemed_for') }}</p>
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
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
          >
            {{ t('manager.loyalty.rewards.add_reward') }}
          </button>
        </div>
      </div>

      <!-- Flash success -->
      <div
        v-if="$page.props.flash?.success"
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- Rewards list -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.reward') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.fraud.index.type') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.customerdetail.cost_pts') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.loyalty.rewards.used') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.status') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="reward in rewards" :key="reward.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ reward.name }}</div>
                <div v-if="reward.description" class="text-xs text-gray-500">{{ reward.description }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full" :class="typeClass(reward.type)">
                  {{ typeLabel(reward.type) }}
                </span>
                <div class="text-xs text-gray-500 mt-0.5">
                  <template v-if="reward.type === 'free_product'">
                    {{ rewardValueLabel(reward) }}
                  </template>
                  <template v-else>
                    {{ t('common.value_a', { a: rewardValueLabel(reward) }) }}
                  </template>
                </div>
              </td>
              <td class="px-6 py-4 font-semibold text-blue-600">{{ reward.cost_points }} pkt</td>
              <td class="px-6 py-4 text-gray-600 text-sm">
                {{ reward.used_count }}
                <span v-if="reward.max_uses" class="text-gray-400"> / {{ reward.max_uses }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  :class="reward.is_active ? 'text-green-600 bg-green-50' : 'text-gray-500 bg-gray-100'"
                  class="px-2 py-1 rounded text-xs font-medium"
                >
                  {{ reward.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openModal(reward)" class="text-sm text-blue-600 hover:text-blue-700">
                  {{ t('common.edit') }}
                </button>
                <button @click="deleteReward(reward)" class="text-sm text-red-500 hover:text-red-600">
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
            <tr v-if="rewards.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                {{ t('manager.loyalty.rewards.no_rewards_yet_click_add_reward') }}
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
            {{ modal.reward ? t('common.edit_reward') : t('common.add_reward') }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.loyalty.rewards.reward_name')
              }}</label>
              <input
                v-model="form.name"
                name="name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.loyalty.rewards.description_optional')
              }}</label>
              <input
                v-model="form.description"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.rewards.cost_points')
                }}</label>
                <input
                  v-model.number="form.cost_points"
                  name="cost_points"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.rewards.reward_type')
                }}</label>
                <select v-model="form.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                  <option value="fixed_discount">{{ t('manager.loyalty.rewards.fixed_discount_pln') }}</option>
                  <option value="percent_discount">{{ t('manager.loyalty.rewards.percentage_discount') }}</option>
                  <option value="free_delivery">{{ t('common.free_delivery') }}</option>
                  <option value="free_product">{{ t('manager.loyalty.rewards.free_product') }}</option>
                </select>
              </div>
            </div>

            <div v-if="form.type === 'free_product'" class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.rewards.free_product')
                }}</label>
                <select
                  v-model.number="form.product_id"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                >
                  <option :value="null" disabled>{{ t('common.choose_a_product') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <p v-if="form.errors.product_id" class="mt-1 text-xs text-red-600">{{ form.errors.product_id }}</p>
              </div>
              <div v-if="selectedProductVariants.length > 0">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.rewards.size_variant')
                }}</label>
                <select
                  v-model.number="form.variant_id"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                >
                  <option :value="null" disabled>{{ t('manager.loyalty.rewards.choose_a_size') }}</option>
                  <option v-for="v in selectedProductVariants" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>
              </div>
            </div>

            <div v-else-if="form.type !== 'free_delivery'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('common.value') }}
                <span class="font-normal text-gray-400">({{ form.type === 'percent_discount' ? '%' : 'PLN' }})</span>
              </label>
              <input
                v-model.number="form.value"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.loyalty.rewards.max_uses_empty_no_limit')
                }}</label>
                <input
                  v-model.number="form.max_uses"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  placeholder="bez limitu"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.order_2') }}</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
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
              <span class="text-sm text-gray-700">{{ t('manager.loyalty.rewards.reward_active') }}</span>
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
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
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
import { reactive, watch, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({ rewards: Array, products: Array })

const modal = reactive({ open: false, reward: null })

const form = useForm({
  name: '',
  description: '',
  cost_points: 100,
  type: 'fixed_discount',
  value: 0,
  product_id: null,
  variant_id: null,
  is_active: true,
  valid_from: null,
  valid_until: null,
  max_uses: null,
  sort_order: 0,
})

const openModal = (reward = null) => {
  modal.reward = reward
  modal.open = true
  if (reward) {
    form.name = reward.name
    form.description = reward.description || ''
    form.cost_points = reward.cost_points
    form.type = reward.type
    form.value = reward.value
    form.product_id = reward.product_id
    form.variant_id = reward.variant_id
    form.is_active = reward.is_active
    form.valid_from = reward.valid_from ? reward.valid_from.slice(0, 16) : null
    form.valid_until = reward.valid_until ? reward.valid_until.slice(0, 16) : null
    form.max_uses = reward.max_uses
    form.sort_order = reward.sort_order
  } else {
    form.reset()
    form.is_active = true
    form.cost_points = 100
    form.type = 'fixed_discount'
    form.sort_order = 0
  }
}

const submitForm = () => {
  if (modal.reward) {
    form.put(route('tenant.manager.loyalty.rewards.update', modal.reward.id), {
      onSuccess: () => {
        modal.open = false
      },
    })
  } else {
    form.post(route('tenant.manager.loyalty.rewards.store'), {
      onSuccess: () => {
        modal.open = false
      },
    })
  }
}

const selectedProductVariants = computed(() => props.products?.find((p) => p.id === form.product_id)?.variants ?? [])

watch(
  () => form.type,
  (type) => {
    if (type !== 'free_product') {
      form.product_id = null
      form.variant_id = null
    }
    if (type === 'free_product') form.value = 0
  },
)

watch(
  () => form.product_id,
  () => {
    form.variant_id = null
  },
)

const deleteReward = (reward) => {
  if (!confirm(t('manager.loyalty.rewards.delete_the_reward_a', { a: reward.name }))) return
  router.delete(route('tenant.manager.loyalty.rewards.destroy', reward.id))
}

const typeLabel = (type) =>
  ({
    fixed_discount: t('common.fixed_discount'),
    percent_discount: t('common.discount_2'),
    free_delivery: t('common.free_delivery'),
    free_product: t('manager.loyalty.rewards.free_product'),
  })[type] || type

const typeClass = (type) =>
  ({
    fixed_discount: 'bg-green-100 text-green-700',
    percent_discount: 'bg-blue-100 text-blue-700',
    free_delivery: 'bg-orange-100 text-orange-700',
    free_product: 'bg-purple-100 text-purple-700',
  })[type] || 'bg-gray-100 text-gray-600'

const rewardValueLabel = (reward) => {
  if (reward.type === 'free_delivery') return 'gratis'
  if (reward.type === 'percent_discount') return `${reward.value}%`
  if (reward.type === 'free_product') {
    const p = props.products?.find((x) => x.id === reward.product_id)
    if (!p) return `produkt #${reward.product_id}`
    const v = p.variants?.find((x) => x.id === reward.variant_id)
    return v ? `${p.name} (${v.name})` : p.name
  }
  return `${reward.value} PLN`
}
</script>
