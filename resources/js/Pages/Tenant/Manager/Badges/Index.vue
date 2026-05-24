<template>
  <ManagerLayout :title="t('manager.badges.index.badges_gamification')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('client.account.badges') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.badges.index.reward_customers_for_shopping_with_you') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.badges.index.new_badge') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-4 py-3 font-semibold text-gray-600 w-16">{{ t('manager.badges.index.icon') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.description') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.badges.index.condition') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.badges.index.threshold') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="badge in badges.data" :key="badge.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-2xl">
                  {{ badge.icon ?? '🏅' }}
                </div>
              </td>
              <td class="px-4 py-3 font-semibold text-gray-900">{{ badge.name }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs max-w-48">{{ badge.description ?? '—' }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-xs rounded-full font-medium">
                  {{ conditionLabel(badge.condition_type) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center font-bold text-blue-600">{{ badge.condition_value }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(badge)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(badge)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!badges.data?.length">
              <td colspan="6" class="text-center py-12 text-gray-400">{{ t('manager.badges.index.no_badges') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_badge') : t('common.new_badge') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div class="grid grid-cols-4 gap-3 items-end">
            <div class="col-span-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.badges.index.icon') }}</label>
              <input
                v-model="form.icon"
                type="text"
                maxlength="4"
                placeholder="🏅"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-2xl text-center"
              />
            </div>
            <div class="col-span-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.description') }}</label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            ></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.badges.index.condition_type')
            }}</label>
            <select v-model="form.condition_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option value="orders_count">{{ t('common.number_of_orders') }}</option>
              <option value="total_spent">{{ t('manager.badges.index.total_spent_pln') }}</option>
              <option value="review_count">{{ t('manager.badges.index.number_of_reviews') }}</option>
              <option value="referral_count">{{ t('manager.badges.index.people_referred') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.badges.index.threshold_value')
            }}</label>
            <input
              v-model.number="form.condition_value"
              type="number"
              min="1"
              required
              :placeholder="conditionPlaceholder(form.condition_type)"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
            <p class="text-xs text-gray-400 mt-1">{{ conditionHint(form.condition_type) }}</p>
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ editing ? t('common.save') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  badges: { type: Object, required: true },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({ icon: '🏅', name: '', description: '', condition_type: 'orders_count', condition_value: 1 })

const openModal = (b = null) => {
  editing.value = b
  if (b) {
    form.icon = b.icon ?? '🏅'
    form.name = b.name ?? ''
    form.description = b.description ?? ''
    form.condition_type = b.condition_type ?? 'orders_count'
    form.condition_value = b.condition_value ?? 1
  } else {
    form.reset()
    form.icon = '🏅'
    form.condition_type = 'orders_count'
    form.condition_value = 1
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.patch(route('tenant.manager.badges.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.badges.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (b) => {
  if (!confirm(t('manager.badges.index.delete_the_badge_a', { a: b.name }))) return
  router.delete(route('tenant.manager.badges.destroy', b.id))
}

const conditionLabel = (type) =>
  ({
    orders_count: t('common.number_of_orders'),
    total_spent: t('common.total_value'),
    review_count: t('manager.badges.index.number_of_reviews'),
    referral_count: t('manager.badges.index.people_referred'),
  })[type] ?? type

const conditionPlaceholder = (type) =>
  ({
    orders_count: 'np. 10',
    total_spent: 'np. 500',
    review_count: 'np. 5',
    referral_count: 'np. 3',
  })[type] ?? ''

const conditionHint = (type) =>
  ({
    orders_count: t('common.a_badge_for_placing_x_orders'),
    total_spent: t('common.a_badge_for_spending_x_pln'),
    review_count: t('manager.badges.index.a_badge_for_writing_x_reviews'),
    referral_count: t('manager.badges.index.a_badge_for_referring_x_friends'),
  })[type] ?? ''
</script>
