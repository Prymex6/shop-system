<template>
  <ManagerLayout :title="t('client.checkout.gift_card')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <Link :href="route('tenant.manager.gift-cards.index')" class="text-blue-600 hover:text-blue-900 text-sm">
            {{ t('manager.giftcards.show.larr_gift_cards') }}
          </Link>
          <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ t('client.checkout.gift_card') }}</h1>
          <p class="text-sm text-gray-500 font-mono uppercase mt-0.5">{{ giftCard.code }}</p>
        </div>
        <button
          @click="toggleActive"
          :class="
            giftCard.is_active
              ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
              : 'bg-green-100 text-green-700 hover:bg-green-200'
          "
          class="px-4 py-2 rounded-md font-semibold text-sm transition"
        >
          {{ giftCard.is_active ? t('landlord.tenants.index.deactivate') : t('landlord.tenants.index.activate') }}
        </button>
      </div>

      <!-- Details card -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('manager.giftcards.show.card_details') }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Code -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
              {{ t('manager.giftcards.index.code') }}
            </p>
            <p class="text-xl font-bold font-mono text-gray-900 uppercase tracking-widest">{{ giftCard.code }}</p>
          </div>

          <!-- Initial value -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
              {{ t('manager.giftcards.show.initial_value') }}
            </p>
            <p class="text-xl font-bold text-gray-900">{{ formatPrice(giftCard.value) }}</p>
          </div>

          <!-- Remaining balance -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
              {{ t('manager.giftcards.show.remaining_balance') }}
            </p>
            <p class="text-xl font-bold" :class="giftCard.balance <= 0 ? 'text-red-600' : 'text-green-700'">
              {{ formatPrice(giftCard.balance) }}
            </p>
          </div>

          <!-- Status -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ t('common.status') }}</p>
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold" :class="statusClass">
              {{ statusLabel }}
            </span>
          </div>

          <!-- Expiry -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
              {{ t('manager.giftcards.show.expiry_date') }}
            </p>
            <p class="text-base font-medium text-gray-900">
              {{ giftCard.expires_at ? formatDate(giftCard.expires_at) : t('landlord.tenants.create.indefinitely') }}
            </p>
            <p v-if="giftCard.expires_at && isExpired" class="text-xs text-red-500 mt-0.5">
              {{ t('manager.giftcards.show.the_card_has_expired') }}
            </p>
          </div>

          <!-- Customer -->
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
              {{ t('manager.giftcards.show.assigned_customer') }}
            </p>
            <p class="text-base font-medium text-gray-900">
              {{ giftCard.customer?.name ?? '—' }}
            </p>
            <p v-if="giftCard.customer?.email" class="text-xs text-gray-500 mt-0.5">{{ giftCard.customer.email }}</p>
          </div>
        </div>
      </div>

      <!-- Usage progress -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ t('manager.giftcards.show.usage') }}</h2>
        <div class="flex items-center gap-4">
          <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
            <div
              class="h-3 rounded-full transition-all"
              :class="usagePercent >= 100 ? 'bg-red-500' : 'bg-green-500'"
              :style="{ width: usagePercent + '%' }"
            ></div>
          </div>
          <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">
            {{ formatPrice((giftCard.value ?? 0) - (giftCard.balance ?? 0)) }} wydane z
            {{ formatPrice(giftCard.value) }}
          </span>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  giftCard: {
    type: Object,
    required: true,
    // shape: { id, code, value, balance, is_active, expires_at, customer }
  },
})

const isExpired = computed(() => props.giftCard.expires_at && new Date(props.giftCard.expires_at) < new Date())

const statusClass = computed(() => {
  if (!props.giftCard.is_active) return 'bg-gray-100 text-gray-700'
  if (isExpired.value) return 'bg-red-100 text-red-800'
  if ((props.giftCard.balance ?? 0) <= 0) return 'bg-orange-100 text-orange-800'
  return 'bg-green-100 text-green-800'
})

const statusLabel = computed(() => {
  if (!props.giftCard.is_active) return 'Nieaktywna'
  if (isExpired.value) return t('common.expired_4')
  if ((props.giftCard.balance ?? 0) <= 0) return 'Wykorzystana'
  return 'Aktywna'
})

const usagePercent = computed(() => {
  const total = props.giftCard.value ?? 0
  if (!total) return 0
  const used = total - (props.giftCard.balance ?? 0)
  return Math.min(100, Math.round((used / total) * 100))
})

function toggleActive() {
  router.patch(
    route('tenant.manager.gift-cards.toggle', props.giftCard.id),
    {},
    {
      preserveScroll: true,
    },
  )
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value)
}
</script>
