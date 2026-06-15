<template>
  <StaffLayout panelTitle="Moje raporty">
    <div class="space-y-6">
      <!-- Send Report Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          <i class="fa-solid fa-paper-plane mr-2 text-blue-600"></i>
          {{ t('staff.report.send_the_report_to_the_manager') }}
        </h2>

        <form @submit.prevent="submitReport" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('pages.landing.subject') }}</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :placeholder="t('staff.report.a_short_description_of_the_subject')"
              maxlength="200"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('staff.report.report_content') }}</label>
            <textarea
              v-model="form.message"
              rows="5"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :placeholder="t('staff.report.describe_the_situation_problem_or_note')"
              maxlength="2000"
              required
            ></textarea>
            <p class="text-xs text-gray-400 mt-1">{{ form.message.length }}/2000</p>
          </div>
          <button
            type="submit"
            :disabled="processing"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-lg transition-colors"
          >
            <i class="fa-solid fa-paper-plane mr-2"></i>
            {{ processing ? t('common.sending_2') : t('common.send_the_report') }}
          </button>
        </form>
      </div>

      <!-- My Reports History -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          <i class="fa-solid fa-clock-rotate-left mr-2 text-gray-500"></i>
          {{ t('staff.report.report_history') }}
        </h2>

        <div v-if="reports.length === 0" class="text-center py-8 text-gray-400">
          <i class="fa-solid fa-inbox text-3xl mb-2"></i>
          <p>{{ t('staff.report.no_reports_sent') }}</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="report in reports" :key="report.id" class="border border-gray-100 rounded-lg p-4">
            <div class="flex items-start justify-between">
              <div>
                <p class="font-medium text-gray-900">{{ report.title }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ report.message }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ formatDate(report.created_at) }}</p>
              </div>
              <span
                :class="report.status === 'new' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'"
                class="text-xs font-medium px-2 py-1 rounded-full ml-4 shrink-0"
              >
                {{ report.status === 'new' ? t('manager.orders.index.pending') : 'Przeczytany' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({
  reports: Array,
})

const form = ref({ title: '', message: '' })
const processing = ref(false)

const submitReport = () => {
  processing.value = true
  router.post(route('tenant.staff.reports.store'), form.value, {
    onSuccess: () => {
      form.value = { title: '', message: '' }
    },
    onFinish: () => {
      processing.value = false
    },
  })
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
