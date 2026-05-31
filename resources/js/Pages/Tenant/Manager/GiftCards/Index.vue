<template>
  <ManagerLayout :title="t('pages.landing.gift_cards')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('pages.landing.gift_cards') }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ t('manager.giftcards.index.manage_the_shop_s_gift_cards') }}</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('manager.giftcards.index.add_cards') }}
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.giftcards.index.code') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.giftcards.index.initial_value') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.giftcards.index.current_value') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.giftcards.index.expires') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!giftCards.data?.length">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.giftcards.index.no_gift_cards') }}
                </td>
              </tr>
              <tr v-for="card in giftCards.data" :key="card.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="font-mono font-semibold text-gray-900 uppercase">{{ card.code }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatPrice(card.initial_value) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatPrice(card.current_value) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="cardStatusClass(card)"
                  >
                    {{ cardStatusLabel(card) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ card.expires_at ? formatDate(card.expires_at) : t('landlord.tenants.create.indefinitely') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                  <button @click="toggleCard(card)" class="text-yellow-600 hover:text-yellow-900">
                    {{ card.is_active ? t('landlord.tenants.index.deactivate') : t('landlord.tenants.index.activate') }}
                  </button>
                  <button @click="deleteCard(card)" class="text-red-600 hover:text-red-900">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="giftCards.data?.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ t('common.showing') }} <span class="font-medium">{{ giftCards.from }}</span> {{ t('common.to') }}
              <span class="font-medium">{{ giftCards.to }}</span> z
              <span class="font-medium">{{ giftCards.total }}</span> {{ t('common.results') }}
            </div>
            <div class="flex space-x-2">
              <template v-for="link in giftCards.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  ]"
                  v-html="link.label"
                />
                <span
                  v-else
                  class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md opacity-50 cursor-not-allowed bg-white text-gray-700"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6">
          <h2 class="text-xl font-bold mb-6">{{ t('manager.giftcards.index.add_gift_cards') }}</h2>

          <form @submit.prevent="submitCreate" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.giftcards.index.how_many_cards_to_generate')
              }}</label>
              <input
                v-model="createForm.count"
                type="number"
                min="1"
                max="100"
                required
                class="w-full px-3 py-2 border rounded-lg"
                placeholder="np. 10"
              />
              <p v-if="createForm.errors.count" class="text-red-600 text-sm mt-1">{{ createForm.errors.count }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.giftcards.index.initial_value_pln')
              }}</label>
              <input
                v-model="createForm.value"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full px-3 py-2 border rounded-lg"
                placeholder="np. 100.00"
              />
              <p v-if="createForm.errors.value" class="text-red-600 text-sm mt-1">{{ createForm.errors.value }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.giftcards.index.expiry_date_optional')
              }}</label>
              <input v-model="createForm.expires_at" type="date" class="w-full px-3 py-2 border rounded-lg" />
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="createForm.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ createForm.processing ? 'Generowanie...' : t('common.generate_cards') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  giftCards: { type: Object, required: true },
})

const showCreateModal = ref(false)

const createForm = useForm({
  count: 1,
  value: '',
  expires_at: '',
})

function submitCreate() {
  createForm.post(route('tenant.manager.gift-cards.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

function toggleCard(card) {
  router.patch(
    route('tenant.manager.gift-cards.toggle', card.id),
    {},
    {
      preserveScroll: true,
    },
  )
}

function deleteCard(card) {
  if (!confirm(t('manager.giftcards.index.delete_the_card_a', { a: card.code }))) return
  router.delete(route('tenant.manager.gift-cards.destroy', card.id))
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value)
}

function cardStatusClass(card) {
  if (!card.is_active) return 'bg-gray-100 text-gray-700'
  if (card.expires_at && new Date(card.expires_at) < new Date()) return 'bg-red-100 text-red-800'
  if (card.current_value <= 0) return 'bg-orange-100 text-orange-800'
  return 'bg-green-100 text-green-800'
}

function cardStatusLabel(card) {
  if (!card.is_active) return 'Nieaktywna'
  if (card.expires_at && new Date(card.expires_at) < new Date()) return t('common.expired_4')
  if (card.current_value <= 0) return 'Wykorzystana'
  return 'Aktywna'
}
</script>
