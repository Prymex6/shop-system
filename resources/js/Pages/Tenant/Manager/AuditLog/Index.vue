<template>
  <ManagerLayout :title="t('manager.auditlog.index.audit_log')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.auditlog.index.audit_log') }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ t('manager.auditlog.index.every_action_taken_in_the_system') }}</p>
        </div>
        <a
          :href="route('tenant.manager.audit-log.export')"
          class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm"
        >
          {{ t('manager.auditlog.index.export_csv') }}
        </a>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.auditlog.index.user_type')
            }}</label>
            <select
              v-model="filters.user_type"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">{{ t('manager.auditlog.index.everyone') }}</option>
              <option value="manager">Manager</option>
              <option value="staff">{{ t('pages.landing.staff_member') }}</option>
              <option value="customer">{{ t('common.customer') }}</option>
              <option value="system">System</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.auditlog.index.action') }}</label>
            <select
              v-model="filters.action"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">{{ t('common.all') }}</option>
              <option value="created">{{ t('landlord.tenants.index.created') }}</option>
              <option value="updated">{{ t('manager.auditlog.index.updated') }}</option>
              <option value="deleted">{{ t('manager.auditlog.index.deleted') }}</option>
              <option value="login">{{ t('common.sign_in_2') }}</option>
              <option value="logout">{{ t('manager.auditlog.index.sign_out') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.date_from') }}</label>
            <input
              v-model="filters.date_from"
              type="date"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.date_to') }}</label>
            <input
              v-model="filters.date_to"
              type="date"
              @change="applyFilters"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.date_time') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.user') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.action') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.object') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.old_values') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.auditlog.index.new_values') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!logs.data?.length">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.auditlog.index.no_entries') }}
                </td>
              </tr>
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="text-xs">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                    >
                      {{ log.user_type ?? '—' }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-0.5">{{ log.user_name ?? log.user_id ?? '—' }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="actionClass(log.action)"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">
                  <div>{{ log.auditable_type }}</div>
                  <div class="text-gray-400">#{{ log.auditable_id }}</div>
                </td>
                <td class="px-4 py-3 text-xs">
                  <button
                    v-if="log.old_values && Object.keys(log.old_values ?? {}).length"
                    @click="showValues(log.old_values)"
                    class="text-blue-600 hover:underline"
                  >
                    {{ t('manager.auditlog.index.show') }}
                  </button>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-xs">
                  <button
                    v-if="log.new_values && Object.keys(log.new_values ?? {}).length"
                    @click="showValues(log.new_values)"
                    class="text-blue-600 hover:underline"
                  >
                    {{ t('manager.auditlog.index.show') }}
                  </button>
                  <span v-else class="text-gray-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.data?.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ t('common.showing') }} <span class="font-medium">{{ logs.from }}</span> {{ t('common.to') }}
              <span class="font-medium">{{ logs.to }}</span> z <span class="font-medium">{{ logs.total }}</span>
            </div>
            <div class="flex space-x-2">
              <template v-for="link in logs.links" :key="link.label">
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

    <!-- Values Modal -->
    <div
      v-if="selectedValues"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="selectedValues = null"
    >
      <div class="bg-white rounded-lg max-w-lg w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-900">{{ t('manager.auditlog.index.values') }}</h3>
            <button @click="selectedValues = null" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
          </div>
          <pre class="bg-gray-50 p-4 rounded-lg text-xs overflow-auto">{{
            JSON.stringify(selectedValues, null, 2)
          }}</pre>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const selectedValues = ref(null)

const filters = reactive({
  user_type: props.filters.user_type || '',
  action: props.filters.action || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
})

function applyFilters() {
  router.get(route('tenant.manager.audit-log.index'), filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

function showValues(values) {
  selectedValues.value = values
}

function formatDate(date) {
  return new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function actionClass(action) {
  const map = {
    created: 'bg-green-100 text-green-800',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
    login: 'bg-blue-100 text-blue-900',
    logout: 'bg-gray-100 text-gray-700',
  }
  return map[action] ?? 'bg-gray-100 text-gray-700'
}
</script>
