<template>
  <ManagerLayout title="Fraud Detection">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.fraud.index.security_fraud_detection') }}</h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ t('manager.fraud.index.manage_suspicious_orders_and_the_blocklist') }}
        </p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex space-x-8">
          <button
            @click="tab = 'orders'"
            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
            :class="
              tab === 'orders' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'
            "
          >
            {{ t('manager.fraud.index.suspicious_orders') }}
            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">{{ flaggedOrders.total }}</span>
          </button>
          <button
            @click="tab = 'blocklist'"
            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
            :class="
              tab === 'blocklist'
                ? 'border-red-500 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            "
          >
            Blocklist
            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">{{ blocklist.length }}</span>
          </button>
        </nav>
      </div>

      <!-- Flagged Orders Tab -->
      <div v-if="tab === 'orders'">
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('common.order') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('common.customer') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('manager.fraud.index.flags') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('common.status') }}
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    {{ t('common.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="order in flaggedOrders.data" :key="order.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Link
                      :href="`/manager/orders/${order.id}`"
                      class="text-sm font-semibold text-blue-600 hover:underline"
                    >
                      #{{ order.order_number }}
                    </Link>
                    <div class="text-xs text-gray-500">{{ formatCurrency(order.total, order.currency) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ order.customer_name }}</div>
                    <div class="text-xs text-gray-500">{{ order.customer_email }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold"
                      :class="scoreClass(order.fraud_flags?.[0]?.score ?? 0)"
                    >
                      {{ order.fraud_flags?.[0]?.score ?? 0 }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="flag in order.fraud_flags"
                        :key="flag.id"
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
                      >
                        {{ flagLabel(flag.flag_type) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full font-medium" :class="statusClass(order.status)">
                      {{ order.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-2">
                      <button
                        @click="releaseOrder(order)"
                        class="px-3 py-1.5 bg-green-100 text-green-700 rounded text-xs font-medium hover:bg-green-200 transition"
                        :disabled="order.status !== 'pending'"
                      >
                        {{ t('manager.fraud.index.release') }}
                      </button>
                      <button
                        @click="blockOrder(order)"
                        class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200 transition"
                      >
                        {{ t('manager.fraud.index.block') }}
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="flaggedOrders.data?.length === 0">
                  <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                    {{ t('manager.fraud.index.no_suspicious_orders') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Blocklist Tab -->
      <div v-if="tab === 'blocklist'" class="space-y-4">
        <div class="flex justify-end">
          <button
            @click="showAddModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition flex items-center gap-2"
          >
            <i class="fa-solid fa-plus"></i>
            {{ t('manager.fraud.index.add_entry') }}
          </button>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('manager.fraud.index.type') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.value') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.reason') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.date') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="entry in blocklist" :key="entry.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 py-1 text-xs rounded font-medium"
                    :class="{
                      'bg-blue-100 text-blue-700': entry.type === 'email',
                      'bg-orange-100 text-orange-700': entry.type === 'ip',
                      'bg-purple-100 text-purple-700': entry.type === 'card_bin',
                    }"
                    >{{ entry.type }}</span
                  >
                </td>
                <td class="px-6 py-4 font-mono text-sm">{{ entry.value }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ entry.reason ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ formatDate(entry.created_at) }}</td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click="deleteBlocklistEntry(entry)"
                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
              <tr v-if="blocklist.length === 0">
                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                  {{ t('manager.fraud.index.the_blocklist_is_empty') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add to Blocklist Modal -->
    <Teleport to="body">
      <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('manager.fraud.index.add_to_the_blocklist') }}</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.fraud.index.type') }}</label>
              <select v-model="newEntry.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="email">{{ t('common.email') }}</option>
                <option value="ip">IP</option>
                <option value="card_bin">Card BIN</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.value') }}</label>
              <input
                v-model="newEntry.value"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                :placeholder="t('manager.fraud.index.e_g_user_example_com')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.reason_optional') }}</label>
              <input v-model="newEntry.reason" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="showAddModal = false"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              {{ t('common.cancel') }}
            </button>
            <button @click="addBlocklistEntry" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              {{ t('manager.fraud.index.add_to_the_blocklist') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  flaggedOrders: Object,
  blocklist: Array,
})

const tab = ref('orders')
const showAddModal = ref(false)
const newEntry = reactive({ type: 'email', value: '', reason: '' })

const scoreClass = (score) => {
  if (score >= 80) return 'bg-red-100 text-red-800'
  if (score >= 40) return 'bg-orange-100 text-orange-800'
  return 'bg-green-100 text-green-800'
}

const flagLabel = (type) => {
  const labels = {
    email_blocklisted: t('manager.fraud.index.email_on_the_blocklist'),
    ip_blocklisted: t('manager.fraud.index.ip_on_the_blocklist'),
    ip_high_frequency: t('common.several_orders_from_one_ip'),
    email_high_frequency: t('common.several_orders_from_one_email_address'),
    high_value_no_history: t('common.a_large_order_from_someone_with'),
  }
  return labels[type] ?? type
}

const statusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800',
    completed: 'bg-green-100 text-green-800',
  }
  return classes[status] ?? 'bg-gray-100 text-gray-700'
}

const formatCurrency = (amount, currency = 'PLN') =>
  new Intl.NumberFormat(locale.value, { style: 'currency', currency: currency ?? 'PLN' }).format(amount)

const formatDate = (date) => (date ? new Date(date).toLocaleDateString(locale.value) : '—')

const releaseOrder = (order) => {
  if (!confirm(t('manager.fraud.index.release_order_a', { a: order.order_number }))) return
  router.post(`/manager/fraud/${order.id}/release`, {}, { preserveScroll: true })
}

const blockOrder = (order) => {
  if (!confirm(t('manager.fraud.index.block_order_a_and_add_the', { a: order.order_number }))) return
  router.post(`/manager/fraud/${order.id}/block`, {}, { preserveScroll: true })
}

const addBlocklistEntry = async () => {
  try {
    await window.axios.post('/manager/fraud/blocklist', newEntry)
    showAddModal.value = false
    newEntry.value = ''
    newEntry.reason = ''
    router.reload({ only: ['blocklist'] })
  } catch (e) {
    alert(t('common.error') + (e?.response?.data?.message ?? t('common.unknown_error')))
  }
}

const deleteBlocklistEntry = async (entry) => {
  if (!confirm(t('manager.fraud.index.remove_a_b_from_the_blocklist', { a: entry.type, b: entry.value }))) return
  try {
    await window.axios.delete(`/manager/fraud/blocklist/${entry.id}`)
    router.reload({ only: ['blocklist'] })
  } catch (e) {
    alert(t('common.it_could_not_be_deleted'))
  }
}
</script>
