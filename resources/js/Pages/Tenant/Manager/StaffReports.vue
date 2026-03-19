<template>
  <ManagerLayout :title="t('common.staff_reports')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('common.staff_reports') }}</h1>
          <p v-if="localNewCount > 0" class="text-sm text-orange-600 mt-1">
            {{ t('common.a_new_reports_to_read', { a: localNewCount }) }}
          </p>
        </div>
        <button
          v-if="localNewCount > 0"
          @click="markAllRead"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
          <i class="fa-solid fa-check-double mr-2"></i>
          {{ t('common.mark_all_as_read') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('manager.rolepermissions.role') }}</label>
            <select
              v-model="filters.role"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            >
              <option value="">{{ t('manager.staffreports.all_roles') }}</option>
              <option value="fulfillment">{{ t('manager.rolepermissions.staff_fulfilment') }}</option>
              <option value="warehouse">{{ t('pages.landing.warehouse_staff') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.status') }}</label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            >
              <option value="">{{ t('common.all') }}</option>
              <option value="new">{{ t('manager.staffreports.new') }}</option>
              <option value="read">{{ t('manager.staffreports.read') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.date_from') }}</label>
            <input
              v-model="filters.date_from"
              @change="applyFilters"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.date_to') }}</label>
            <input
              v-model="filters.date_to"
              @change="applyFilters"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Reports List -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="localReports.length === 0" class="text-center py-12 text-gray-400">
          <i class="fa-solid fa-inbox text-4xl mb-3"></i>
          <p class="text-lg">{{ t('manager.staffreports.no_reports') }}</p>
        </div>

        <div v-else class="bg-white divide-y divide-gray-200">
          <div
            v-for="report in localReports"
            :key="report.id"
            :class="report.status === 'new' ? 'bg-orange-50' : 'bg-white'"
            class="p-5 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-1">
                  <span class="font-semibold text-gray-900">{{ report.title }}</span>
                  <span
                    :class="report.status === 'new' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'"
                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                  >
                    {{ report.status === 'new' ? t('common.new_3') : 'Przeczytany' }}
                  </span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                  <span class="font-medium text-gray-700">{{ report.staff_user?.name }}</span>
                  <span>·</span>
                  <span>{{ roleLabel(report.role) }}</span>
                  <span>·</span>
                  <span>{{ formatDate(report.created_at) }}</span>
                </div>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ report.message }}</p>
              </div>
              <button
                v-if="report.status === 'new'"
                @click="markRead(report)"
                class="ml-4 shrink-0 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors"
              >
                {{ t('manager.staffreports.mark_as_read') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1" class="px-5 py-4 border-t border-gray-100 flex justify-between items-center">
          <p class="text-sm text-gray-500">Strona {{ reports.current_page }} z {{ reports.last_page }}</p>
          <div class="flex gap-2">
            <Link
              v-if="reports.prev_page_url"
              :href="reports.prev_page_url"
              class="px-3 py-1.5 border border-gray-300 text-sm rounded hover:bg-gray-50"
            >
              {{ t('manager.customers.index.previous') }}
            </Link>
            <Link
              v-if="reports.next_page_url"
              :href="reports.next_page_url"
              class="px-3 py-1.5 border border-gray-300 text-sm rounded hover:bg-gray-50"
            >
              {{ t('common.next') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const page = usePage()

const props = defineProps({
  reports: Object,
  newCount: Number,
  filters: Object,
})

const localReports = ref([...props.reports.data])
const localNewCount = ref(props.newCount || 0)

const filters = ref({
  role: props.filters?.role || '',
  status: props.filters?.status || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
})

const applyFilters = () => {
  router.get(route('tenant.manager.staff-reports.index'), filters.value, {
    preserveState: true,
    replace: true,
  })
}

const markRead = (report) => {
  router.patch(
    route('tenant.manager.staff-reports.mark-read', report.id),
    {},
    {
      preserveState: true,
      onSuccess: () => {
        const r = localReports.value.find((r) => r.id === report.id)
        if (r) {
          r.status = 'read'
          localNewCount.value = Math.max(0, localNewCount.value - 1)
        }
      },
    },
  )
}

const markAllRead = () => {
  router.post(
    route('tenant.manager.staff-reports.mark-all-read'),
    {},
    {
      preserveState: true,
      onSuccess: () => {
        localReports.value.forEach((r) => {
          r.status = 'read'
        })
        localNewCount.value = 0
      },
    },
  )
}

const tenantId = page.props.tenant?.id

onMounted(() => {
  if (window.Echo && tenantId) {
    window.Echo.private(`staff-reports.${tenantId}`).listen('.staff-report.created', (e) => {
      if (e.report) {
        localReports.value.unshift(e.report)
        localNewCount.value++
      }
    })
  }
})

onUnmounted(() => {
  if (tenantId) window.Echo?.leave(`staff-reports.${tenantId}`)
})

const roleLabel = (role) => {
  const labels = {
    manager: 'Manager',
    fulfillment: t('manager.rolepermissions.staff_fulfilment'),
    warehouse: t('pages.landing.warehouse_staff'),
  }
  return labels[role] || role
}

const formatDate = (date) => {
  return new Date(date).toLocaleString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
