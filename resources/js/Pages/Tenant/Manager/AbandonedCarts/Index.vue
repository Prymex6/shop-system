<template>
  <ManagerLayout :title="t('manager.abandonedcarts.index.abandoned_carts')">
    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
        <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ t('manager.abandonedcarts.index.all') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ stats.reminder_sent }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ t('manager.abandonedcarts.index.reminder_sent') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
        <div class="text-2xl font-bold text-green-600">{{ stats.converted }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ t('manager.abandonedcarts.index.ended_in_a_purchase') }}</div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">
          {{ t('manager.abandonedcarts.index.abandoned_carts_not_yet_handled') }}
        </h2>
        <span class="text-sm text-gray-500">{{ t('common.a_records_count', { a: carts.total }) }}</span>
      </div>

      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
          <tr>
            <th class="px-6 py-3 text-left">{{ t('landlord.shopsearch.email') }}</th>
            <th class="px-6 py-3 text-left">{{ t('common.products') }}</th>
            <th class="px-6 py-3 text-left">{{ t('common.value') }}</th>
            <th class="px-6 py-3 text-left">{{ t('manager.abandonedcarts.index.reminder') }}</th>
            <th class="px-6 py-3 text-left">{{ t('manager.abandonedcarts.index.abandoned') }}</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="carts.data.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
              {{ t('manager.abandonedcarts.index.no_abandoned_carts') }}
            </td>
          </tr>
          <tr v-for="cart in carts.data" :key="cart.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">
              {{ cart.email || '—' }}
            </td>
            <td class="px-6 py-4 text-gray-600">{{ cart.items_count }} szt.</td>
            <td class="px-6 py-4 text-gray-900 font-medium">
              {{ formatPrice(cart.total) }}
            </td>
            <td class="px-6 py-4">
              <span v-if="cart.reminder_sent_at" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                {{ t('manager.abandonedcarts.index.sent') }}
              </span>
              <span v-else class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                {{ t('manager.abandonedcarts.index.not_sent') }}
              </span>
            </td>
            <td class="px-6 py-4 text-gray-500 text-xs">{{ formatDate(cart.created_at) }}</td>
            <td class="px-6 py-4 text-right">
              <button @click="remove(cart.id)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                {{ t('common.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="carts.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-center">
        <Link
          v-for="link in carts.links"
          :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="px-3 py-1 rounded text-sm border"
          :class="
            link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 border-gray-300 hover:bg-gray-50'
          "
        />
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  carts: Object,
  stats: Object,
})

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleString(locale.value, { dateStyle: 'short', timeStyle: 'short' })
}

function remove(id) {
  if (!confirm(t('common.delete_this_entry'))) return
  router.delete(route('tenant.manager.abandoned-carts.destroy', id), { preserveScroll: true })
}
</script>
