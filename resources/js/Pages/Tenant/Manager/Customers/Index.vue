<template>
  <Head :title="t('common.customers')" />
  <ManagerLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('common.customers') }}</h1>
          <p class="text-gray-500 text-sm mt-1">{{ t('common.a_registered_customers', { a: customers.total }) }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="showImportModal = true"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium flex items-center gap-2"
          >
            <i class="fa-solid fa-file-csv"></i>
            {{ t('manager.customers.index.import_csv') }}
          </button>
          <a
            :href="route('tenant.manager.customers.export')"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium flex items-center gap-2"
          >
            <i class="fa-solid fa-file-csv"></i>
            {{ t('manager.auditlog.index.export_csv') }}
          </a>
        </div>
      </div>

      <!-- Search -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="flex gap-3">
          <input
            v-model="search"
            @keyup.enter="applySearch"
            type="text"
            :placeholder="t('manager.customers.index.search_by_name_email_or_phone')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          />
          <button
            @click="applySearch"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
          >
            {{ t('common.search') }}
          </button>
          <button
            v-if="filters.search"
            @click="clearSearch"
            class="px-4 py-2 border border-gray-300 rounded-lg text-sm"
          >
            {{ t('common.clear') }}
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                {{ t('common.customer') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('common.phone') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('common.city') }}</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">
                {{ t('common.orders_2') }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">
                {{ t('common.points') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                {{ t('manager.customers.index.joined') }}
              </th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ customer.name }}</div>
                <div class="text-gray-500 text-xs">{{ customer.email }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ customer.phone || '–' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ customer.delivery_city || '–' }}</td>
              <td class="px-4 py-3 text-center">
                <span class="font-semibold text-gray-900">{{ customer.orders_count }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="tierClass(customer.loyalty_tier)">
                  {{ customer.loyalty_points ?? 0 }} pkt
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(customer.created_at) }}</td>
              <td class="px-4 py-3">
                <Link
                  :href="route('tenant.manager.customers.show', customer.id)"
                  class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                >
                  {{ t('common.details') }}
                </Link>
              </td>
            </tr>
            <tr v-if="!customers.data.length">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                {{ t('manager.customers.index.no_customers') }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="customers.last_page > 1"
          class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm"
        >
          <span class="text-gray-500">Strona {{ customers.current_page }} z {{ customers.last_page }}</span>
          <div class="flex gap-2">
            <Link
              v-if="customers.prev_page_url"
              :href="customers.prev_page_url"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
              >{{ t('manager.customers.index.previous') }}</Link
            >
            <Link
              v-if="customers.next_page_url"
              :href="customers.next_page_url"
              class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
              >{{ t('common.next') }}</Link
            >
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>

  <!-- Import CSV Modal -->
  <div
    v-if="showImportModal"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    @click.self="showImportModal = false"
  >
    <div class="bg-white shadow rounded-lg w-full max-w-md p-6 space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">{{ t('manager.customers.index.import_customers_from_csv') }}</h3>
        <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
          &times;
        </button>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
        {{ t('manager.customers.index.csv_file') }}
        <code class="font-mono text-xs">name, email, phone, delivery_address</code>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{
          t('manager.productimportmodal.csv_file')
        }}</label>
        <input
          type="file"
          accept=".csv"
          @change="importFile = $event.target.files[0]"
          class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2"
        />
      </div>
      <div class="flex justify-end gap-3">
        <button
          @click="showImportModal = false"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="handleImport"
          :disabled="!importFile || importing"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50"
        >
          {{ importing ? t('common.importing') : 'Importuj' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const showImportModal = ref(false)
const importFile = ref(null)
const importing = ref(false)

const handleImport = async () => {
  if (!importFile.value) return
  importing.value = true
  const formData = new FormData()
  formData.append('file', importFile.value)
  try {
    await window.axios.post(route('tenant.manager.customers.import'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    showImportModal.value = false
    importFile.value = null
    router.reload({ only: ['customers'] })
  } catch (e) {
    alert(t('common.import_error') + (e?.response?.data?.message ?? t('common.unknown_error')))
  } finally {
    importing.value = false
  }
}

const props = defineProps({
  customers: Object,
  filters: Object,
})

const search = ref(props.filters?.search || '')

const applySearch = () => {
  router.get(route('tenant.manager.customers.index'), { search: search.value }, { preserveState: true, replace: true })
}
const clearSearch = () => {
  search.value = ''
  router.get(route('tenant.manager.customers.index'), {}, { preserveState: true, replace: true })
}

const tierClass = (tier) =>
  ({
    bronze: 'bg-orange-100 text-orange-700',
    silver: 'bg-gray-100 text-gray-700',
    gold: 'bg-yellow-100 text-yellow-700',
    platinum: 'bg-purple-100 text-purple-700',
  })[tier] || 'bg-gray-100 text-gray-600'

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(locale.value) : '–')
</script>
