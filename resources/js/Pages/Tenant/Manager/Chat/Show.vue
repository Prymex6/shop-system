<template>
  <ManagerLayout :title="`Chat: ${conversation.guest_name || conversation.customer?.name || t('common.guest')}`">
    <div class="space-y-4 max-w-3xl">
      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.chat.index')" class="text-gray-400 hover:text-gray-700">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div class="flex-1">
          <h1 class="text-2xl font-bold text-gray-900">
            {{ conversation.guest_name || conversation.customer?.name || t('common.guest') }}
          </h1>
          <p class="text-sm text-gray-500">{{ conversation.guest_email }}</p>
        </div>
        <span
          :class="conversation.status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
          class="text-xs font-semibold px-3 py-1 rounded-full"
        >
          {{ conversation.status === 'open' ? 'Otwarta' : t('common.closed_2') }}
        </span>
        <button
          v-if="conversation.status === 'open'"
          @click="closeConv"
          class="text-sm text-gray-500 hover:text-red-600 border border-gray-300 px-3 py-1.5 rounded-lg hover:border-red-300 transition"
        >
          {{ t('common.close') }}
        </button>
      </div>

      <!-- Messages -->
      <div ref="messagesEl" class="bg-white shadow rounded-2xl overflow-hidden flex flex-col" style="height: 500px">
        <div class="flex-1 overflow-y-auto p-5 space-y-3" ref="scrollEl">
          <div
            v-for="msg in localMessages"
            :key="msg.id"
            :class="msg.sender === 'staff' ? 'flex justify-end' : 'flex justify-start'"
          >
            <div
              :class="[
                'max-w-xs lg:max-w-md px-4 py-2.5 rounded-2xl text-sm',
                msg.sender === 'staff'
                  ? 'bg-blue-600 text-white rounded-br-sm'
                  : 'bg-gray-100 text-gray-900 rounded-bl-sm',
              ]"
            >
              <p>{{ msg.body }}</p>
              <p class="text-xs mt-1 opacity-60">{{ formatTime(msg.created_at) }}</p>
            </div>
          </div>
          <div v-if="!localMessages.length" class="text-center text-gray-400 py-10 text-sm">
            {{ t('manager.chat.show.no_messages') }}
          </div>
        </div>

        <!-- Input -->
        <div v-if="conversation.status === 'open'" class="border-t border-gray-100 p-3 flex gap-2">
          <input
            v-model="replyText"
            @keydown.enter.prevent="send"
            type="text"
            :placeholder="t('manager.chat.show.write_a_reply_enter_to_send')"
            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <button
            @click="send"
            :disabled="!replyText.trim() || sending"
            class="bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 disabled:opacity-50 text-sm font-semibold"
          >
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
        <div v-else class="border-t border-gray-100 p-3 text-center text-sm text-gray-400">
          {{ t('manager.chat.show.conversation_closed') }}
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const page = usePage()

const props = defineProps({
  conversation: { type: Object, required: true },
  messages: { type: Array, default: () => [] },
})

const localMessages = ref([...props.messages])
const replyText = ref('')
const sending = ref(false)
const scrollEl = ref(null)
let echoChannel = null
let pollInterval = null

const scrollBottom = () =>
  nextTick(() => {
    if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  })

const mergeMessages = (incoming) => {
  let added = false
  for (const msg of incoming) {
    if (!localMessages.value.some((m) => m.id === msg.id)) {
      localMessages.value.push(msg)
      added = true
    }
  }
  if (added) scrollBottom()
}

// Fallback for when the WebSocket connection is down — ChatManagerController::poll
// existed but was never called from anywhere, so a Reverb outage left the
// manager's chat view silently stale with no way to know new messages had
// arrived, only Echo. Mirrors ManagerLayout.vue's own connection-state-driven
// start/stopPolling pattern for orders.
const startPolling = () => {
  if (pollInterval) return
  pollInterval = setInterval(async () => {
    try {
      const { data } = await axios.get(route('tenant.manager.chat.poll', props.conversation.id))
      mergeMessages(data)
    } catch {
      // A failed poll leaves the thread as it is and tries again.
    }
  }, 10000)
}
const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

onMounted(() => {
  scrollBottom()
  const tenantId = page.props.tenant?.id
  if (window.Echo && tenantId) {
    echoChannel = window.Echo.private(`chat.${tenantId}.${props.conversation.id}`).listen('.message.sent', (e) => {
      if (e.sender === 'customer') {
        const exists = localMessages.value.some((m) => m.id === e.id)
        if (!exists) {
          localMessages.value.push(e)
          scrollBottom()
        }
      }
    })
    window.Echo.connector?.pusher?.connection?.bind('connected', stopPolling)
    window.Echo.connector?.pusher?.connection?.bind('unavailable', startPolling)
    window.Echo.connector?.pusher?.connection?.bind('disconnected', startPolling)
  } else {
    // No Reverb key configured at all — Echo never initializes, so polling
    // is the only way this view ever sees new messages.
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
  if (echoChannel) {
    echoChannel.stopListening('.message.sent')
    const tenantId = page.props.tenant?.id
    if (tenantId) window.Echo?.leave(`chat.${tenantId}.${props.conversation.id}`)
  }
})

const send = async () => {
  if (!replyText.value.trim() || sending.value) return
  sending.value = true
  try {
    const { data } = await axios.post(route('tenant.manager.chat.reply', props.conversation.id), {
      body: replyText.value,
    })
    localMessages.value.push(data)
    replyText.value = ''
    scrollBottom()
  } finally {
    sending.value = false
  }
}

const closeConv = () => {
  if (!confirm(t('common.close_this_conversation'))) return
  router.patch(route('tenant.manager.chat.close', props.conversation.id))
}

const formatTime = (d) => new Date(d).toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' })
</script>
