<template>
  <ManagerLayout :title="t('manager.support.show.ticket')">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <Link :href="route('tenant.manager.support.index')" class="text-sm text-gray-500 hover:text-gray-700">
          {{ t('manager.support.show.back_to_tickets') }}
        </Link>
        <div class="flex items-center gap-3 mt-2">
          <h1 class="text-3xl font-bold text-gray-900">#{{ ticket.id }} – {{ ticket.subject }}</h1>
          <span :class="statusClass(ticket.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
            {{ statusLabel(ticket.status) }}
          </span>
        </div>
      </div>

      <!-- Messages -->
      <div class="space-y-4">
        <div v-for="msg in ticket.messages" :key="msg.id" :class="msg.author_type === 'admin' ? 'ml-6' : 'mr-6'">
          <div
            :class="
              msg.author_type === 'admin' ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200'
            "
            class="rounded-xl p-4"
          >
            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-sm text-gray-800">{{ msg.author_name }}</span>
                <span
                  v-if="msg.author_type === 'admin'"
                  class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"
                  >Wsparcie {{ $page.props.app_name }}</span
                >
              </div>
              <span class="text-xs text-gray-400">{{ formatDate(msg.created_at) }}</span>
            </div>
            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ msg.message }}</p>
          </div>
        </div>
      </div>

      <!-- Reply form (only if ticket not closed) -->
      <form
        v-if="ticket.status !== 'closed'"
        @submit.prevent="sendReply"
        class="bg-white border border-gray-200 rounded-md p-5"
      >
        <h3 class="font-semibold text-gray-800 mb-3">{{ t('manager.support.show.add_message') }}</h3>
        <textarea
          v-model="replyMessage"
          rows="4"
          :placeholder="t('manager.support.show.write_a_message')"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"
          required
        ></textarea>
        <div class="flex justify-end mt-3">
          <button
            type="submit"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm"
          >
            {{ t('common.send') }}
          </button>
        </div>
      </form>

      <div v-else class="bg-gray-50 border border-gray-200 rounded-md p-4 text-center text-gray-500 text-sm">
        {{ t('manager.support.show.the_ticket_has_been_closed') }}
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  ticket: Object,
})

const replyMessage = ref('')

function sendReply() {
  router.post(
    route('tenant.manager.support.reply', props.ticket.id),
    { message: replyMessage.value },
    {
      onSuccess: () => {
        replyMessage.value = ''
      },
    },
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
  return new Date(d).toLocaleString(locale.value, {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
