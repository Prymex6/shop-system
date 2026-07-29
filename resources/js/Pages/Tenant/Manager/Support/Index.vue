<template>
  <ManagerLayout :title="t('layout.landlordlayout.support')">
    <div class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('layout.managerlayout.technical_support') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('common.report_a_problem_to_a', { a: $page.props.app_name }) }}</p>
        </div>
        <button
          type="button"
          @click="showForm = !showForm"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg"
        >
          {{ t('manager.support.index.new_ticket') }}
        </button>
      </div>

      <!-- New ticket form -->
      <div v-if="showForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ t('manager.support.index.new_ticket_2') }}</h2>
        <form @submit.prevent="submitTicket" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.support.index.subject') }}</label>
            <input
              v-model="form.subject"
              name="subject"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('landlord.support.index.priority')
            }}</label>
            <select
              v-model="form.priority"
              class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
            >
              <option value="low">{{ t('landlord.support.index.low') }}</option>
              <option value="normal">{{ t('landlord.support.index.normal') }}</option>
              <option value="high">{{ t('landlord.support.index.high') }}</option>
              <option value="urgent">{{ t('landlord.support.index.urgent') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.support.index.what_went_wrong')
            }}</label>
            <textarea
              v-model="form.message"
              name="message"
              rows="5"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 resize-none"
              required
            ></textarea>
          </div>
          <div class="flex gap-3">
            <button
              type="submit"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm"
            >
              {{ t('manager.support.index.submit_the_ticket') }}
            </button>
            <button
              type="button"
              @click="showForm = false"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm"
            >
              {{ t('common.cancel') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Tickets list -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="tickets.length === 0" class="p-8 text-center text-gray-400">
          <i class="fa-solid fa-headset text-3xl mb-2"></i>
          <p>{{ t('manager.support.index.no_tickets_yet_click_new_ticket') }}</p>
        </div>

        <div v-for="ticket in tickets" :key="ticket.id" class="border-b border-gray-100 last:border-0">
          <Link
            :href="route('tenant.manager.support.show', ticket.id)"
            class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-4">
              <span class="text-gray-400 text-sm">#{{ ticket.id }}</span>
              <div>
                <p class="font-medium text-gray-900 flex items-center gap-2">
                  {{ ticket.subject }}
                  <span
                    v-if="ticket.unread_by_admin"
                    class="inline-block w-2 h-2 rounded-full bg-blue-500"
                    :title="t('manager.support.index.waiting_for_the_admin_s_reply')"
                  ></span>
                </p>
                <p v-if="ticket.latest_message" class="text-sm text-gray-500 truncate max-w-xs">
                  {{ ticket.latest_message.message }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <span :class="priorityClass(ticket.priority)" class="px-2 py-1 rounded-full text-xs font-semibold">
                {{ priorityLabel(ticket.priority) }}
              </span>
              <span :class="statusClass(ticket.status)" class="px-2 py-1 rounded-full text-xs font-semibold">
                {{ statusLabel(ticket.status) }}
              </span>
              <span class="text-xs text-gray-400">{{ formatDate(ticket.created_at) }}</span>
            </div>
          </Link>
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

defineProps({
  tickets: { type: Array, default: () => [] },
})

const showForm = ref(false)
const form = reactive({ subject: '', priority: 'normal', message: '' })

function submitTicket() {
  router.post(route('tenant.manager.support.store'), form, {
    onSuccess: () => {
      showForm.value = false
      form.subject = ''
      form.message = ''
    },
  })
}

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-800',
      high: 'bg-orange-100 text-orange-800',
      normal: 'bg-blue-100 text-blue-800',
      low: 'bg-gray-100 text-gray-600',
    }[p] || ''
  )
}
function priorityLabel(p) {
  return (
    {
      urgent: t('landlord.support.index.urgent'),
      high: t('landlord.support.index.high'),
      normal: t('landlord.support.index.normal'),
      low: t('landlord.support.index.low'),
    }[p] || p
  )
}
function statusClass(s) {
  return (
    {
      open: 'bg-yellow-100 text-yellow-800',
      in_progress: 'bg-blue-100 text-blue-800',
      resolved: 'bg-green-100 text-green-800',
      closed: 'bg-gray-100 text-gray-600',
    }[s] || ''
  )
}
function statusLabel(s) {
  return (
    {
      open: t('landlord.support.index.open'),
      in_progress: t('landlord.support.index.in_progress'),
      resolved: t('landlord.support.show.resolved'),
      closed: t('common.closed'),
    }[s] || s
  )
}
function formatDate(d) {
  return new Date(d).toLocaleDateString(locale.value, { day: 'numeric', month: 'short' })
}
</script>
