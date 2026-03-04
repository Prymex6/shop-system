<template>
  <LandlordLayout :title="t('landlord.support.show.ticket_a', { a: ticket.id })">
    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
          <div>
            <Link :href="route('landlord.support.index')" class="text-sm text-gray-500 hover:text-gray-700">
              {{ t('common.back_to_the_list') }}
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">#{{ ticket.id }} – {{ ticket.subject }}</h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ t('landlord.support.show.shop') }} <strong>{{ tenantName }}</strong>
            </p>
          </div>

          <!-- Status change -->
          <div class="flex items-center gap-2">
            <select
              v-model="newStatus"
              @change="updateStatus"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
            >
              <option value="open">{{ t('landlord.support.index.open') }}</option>
              <option value="in_progress">{{ t('landlord.support.index.in_progress') }}</option>
              <option value="resolved">{{ t('landlord.support.show.resolved') }}</option>
              <option value="closed">{{ t('common.closed') }}</option>
            </select>
          </div>
        </div>

        <!-- Messages -->
        <div class="space-y-4">
          <div v-for="msg in ticket.messages" :key="msg.id" :class="msg.author_type === 'admin' ? 'ml-8' : 'mr-8'">
            <div
              :class="
                msg.author_type === 'admin' ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-gray-200'
              "
              class="rounded-xl p-4"
            >
              <div class="flex justify-between items-center mb-2">
                <span class="font-semibold text-sm text-gray-800">{{ msg.author_name }}</span>
                <span class="text-xs text-gray-400">{{ formatDate(msg.created_at) }}</span>
              </div>
              <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ msg.message }}</p>
            </div>
          </div>
        </div>

        <!-- Reply form -->
        <form @submit.prevent="sendReply" class="bg-white border border-gray-200 rounded-xl p-4">
          <h3 class="font-semibold text-gray-800 mb-3">{{ t('landlord.support.show.reply') }}</h3>
          <textarea
            v-model="replyMessage"
            rows="4"
            :placeholder="t('common.write_a_reply')"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"
            required
          ></textarea>
          <div class="flex justify-end mt-3">
            <button
              type="submit"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm"
            >
              {{ t('landlord.support.show.send_the_reply') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  ticket: Object,
  tenantName: String,
})

const newStatus = ref(props.ticket.status)
const replyMessage = ref('')

function updateStatus() {
  router.patch(
    route('landlord.support.update-status', props.ticket.id),
    { status: newStatus.value },
    { preserveState: false },
  )
}

function sendReply() {
  router.post(
    route('landlord.support.reply', props.ticket.id),
    { message: replyMessage.value },
    {
      onSuccess: () => {
        replyMessage.value = ''
      },
    },
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
