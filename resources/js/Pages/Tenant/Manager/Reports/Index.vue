<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, ArcElement)

const props = defineProps({
  period: String,
  startDate: String,
  endDate: String,
  salesData: Object,
  revenueChart: Object,
  topProducts: Array,
  orderStats: Object,
  peakHours: Object,
  paymentMethods: Array,
})

const selectedPeriod = ref(props.period)
const customStartDate = ref(props.startDate)
const customEndDate = ref(props.endDate)

// Chart configurations
const revenueChartData = computed(() => ({
  labels: props.revenueChart.labels,
  datasets: [
    {
      label: t('common.revenue_pln'),
      data: props.revenueChart.revenue,
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.3,
      yAxisID: 'y',
    },
    {
      label: t('common.number_of_orders'),
      data: props.revenueChart.orders,
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.3,
      yAxisID: 'y1',
    },
  ],
}))

const revenueChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  scales: {
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      title: {
        display: true,
        text: t('common.revenue_pln'),
      },
    },
    y1: {
      type: 'linear',
      display: true,
      position: 'right',
      title: {
        display: true,
        text: t('common.number_of_orders'),
      },
      grid: {
        drawOnChartArea: false,
      },
    },
  },
  plugins: {
    legend: {
      position: 'top',
    },
  },
}

const peakHoursChartData = computed(() => ({
  labels: props.peakHours.hours,
  datasets: [
    {
      label: t('common.orders'),
      data: props.peakHours.orders,
      backgroundColor: 'rgba(59, 130, 246, 0.8)',
    },
  ],
}))

const peakHoursChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: t('common.number_of_orders'),
      },
    },
  },
}

const orderTypeChartData = computed(() => {
  return {
    labels: Object.keys(props.orderStats.by_type),
    datasets: [
      {
        data: Object.values(props.orderStats.by_type),
        backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)'],
      },
    ],
  }
})

const orderTypeChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
}

const formatPrice = (price) => {
  return new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: 'PLN',
  }).format(price)
}

const changePeriod = (period) => {
  selectedPeriod.value = period

  if (period !== 'custom') {
    router.get(route('tenant.manager.reports.index'), { period })
  }
}

const applyCustomDateRange = () => {
  router.get(route('tenant.manager.reports.index'), {
    period: 'custom',
    start_date: customStartDate.value,
    end_date: customEndDate.value,
  })
}

const exportCsv = () => {
  const params = new URLSearchParams({
    period: selectedPeriod.value,
    start_date: customStartDate.value,
    end_date: customEndDate.value,
  })

  window.location.href = route('tenant.manager.reports.export-csv') + '?' + params.toString()
}

const getPaymentMethodLabel = (method) => {
  const labels = {
    przelewy24: 'Przelewy24',
    payu: 'PayU',
    tpay: 'Tpay',
    stripe: 'Stripe',
    bank_transfer: t('manager.manualordermodal.bank_transfer'),
    cash_on_delivery: t('common.cash_on_delivery'),
  }
  return labels[method] || method
}
</script>

<template>
  <ManagerLayout>
    <Head :title="t('manager.reports.index.reports_and_analytics')" />

    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.reports.index.reports_and_analytics') }}</h1>
        <p class="mt-2 text-gray-600">{{ t('manager.reports.index.an_overview_of_sales_and_statistics') }}</p>
      </div>

      <!-- Period Selector -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex gap-2">
            <button
              v-for="period in ['day', 'week', 'month', 'year']"
              :key="period"
              @click="changePeriod(period)"
              class="px-4 py-2 rounded-lg font-medium transition"
              :class="{
                'bg-blue-600 text-white': selectedPeriod === period,
                'bg-gray-100 text-gray-700 hover:bg-gray-200': selectedPeriod !== period,
              }"
            >
              {{
                {
                  day: 'Dzisiaj',
                  week: '7 dni',
                  month: '30 dni',
                  year: 'Rok',
                }[period]
              }}
            </button>
          </div>

          <div class="flex-1"></div>

          <!-- Custom Date Range -->
          <div class="flex items-center gap-2">
            <input v-model="customStartDate" type="date" class="px-3 py-2 border border-gray-300 rounded-lg" />
            <span class="text-gray-500">-</span>
            <input v-model="customEndDate" type="date" class="px-3 py-2 border border-gray-300 rounded-lg" />
            <button
              @click="applyCustomDateRange"
              class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
            >
              Zastosuj
            </button>
          </div>

          <!-- Export Button -->
          <button @click="exportCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            <i class="fa-solid fa-file-csv mr-1"></i> Eksportuj CSV
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('manager.reports.index.total_revenue') }}</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatPrice(salesData.total_revenue) }}
              </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-coins text-2xl text-blue-600"></i>
            </div>
          </div>
          <p v-if="salesData.total_discount > 0" class="text-xs text-gray-500 mt-2">
            Rabaty: -{{ formatPrice(salesData.total_discount) }}
          </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('common.number_of_orders') }}</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ salesData.total_orders }}
              </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-clipboard-list text-2xl text-green-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('manager.reports.index.average_value') }}</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatPrice(salesData.average_order_value) }}
              </p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-chart-bar text-2xl text-yellow-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('manager.reports.index.discounts_applied') }}</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatPrice(salesData.total_discount) }}
              </p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-ticket text-2xl text-red-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue Chart -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">{{ t('manager.reports.index.revenue_and_orders') }}</h2>
        <div class="h-80">
          <Line :data="revenueChartData" :options="revenueChartOptions" />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Peak Hours -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4">Godziny szczytu</h2>
          <div class="h-64">
            <Bar :data="peakHoursChartData" :options="peakHoursChartOptions" />
          </div>
        </div>

        <!-- Order Types -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4">{{ t('common.fulfilment_status') }}</h2>
          <div class="h-64 flex items-center justify-center">
            <Doughnut :data="orderTypeChartData" :options="orderTypeChartOptions" />
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">{{ t('manager.reports.index.best_selling_products') }}</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.quantity') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ t('manager.reports.index.revenue') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(product, index) in topProducts" :key="index">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ product.name }}
                  <span v-if="product.variant_name" class="block text-xs text-gray-400 font-normal">{{
                    product.variant_name
                  }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  {{ product.quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                  {{ formatPrice(product.revenue) }}
                </td>
              </tr>
              <tr v-if="topProducts.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                  {{ t('manager.reports.index.no_data_for_the_chosen_period') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payment Methods -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">{{ t('manager.reports.index.payment_methods') }}</h2>
        <div class="space-y-4">
          <div
            v-for="method in paymentMethods"
            :key="method.method"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900">{{ getPaymentMethodLabel(method.method) }}</p>
              <p class="text-sm text-gray-600">{{ t('common.a_orders_count', { a: method.count }) }}</p>
            </div>
            <p class="text-lg font-bold text-blue-600">{{ formatPrice(method.revenue) }}</p>
          </div>
          <div v-if="paymentMethods.length === 0" class="text-center text-gray-500 py-8">
            {{ t('manager.reports.index.no_data_for_the_chosen_period') }}
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>
