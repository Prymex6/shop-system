<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
    <!-- Chat window -->
    <Transition name="slide-up">
      <div
        v-if="open"
        class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-80 sm:w-96 flex flex-col overflow-hidden"
        style="height: 480px; max-height: 80vh"
      >
        <!-- Header -->
        <div class="theme-primary-bg text-white px-4 py-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-headset text-sm"></i>
            </div>
            <div>
              <p class="font-semibold text-sm">{{ t('components.chatwidget.live_help') }}</p>
              <p class="text-xs opacity-80">
                {{ conversationId ? t('common.connected') : t('common.we_usually_reply_within_a_few') }}
              </p>
            </div>
          </div>
          <button @click="open = false" class="text-white/80 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Start form (no conversation yet) -->
        <div v-if="!conversationId" class="flex-1 flex flex-col justify-center px-5 py-6">
          <p class="text-sm text-gray-600 mb-4 text-center">{{ t('components.chatwidget.tell_us_who_you_are_to') }}</p>
          <div class="space-y-3">
            <input
              v-model="form.name"
              type="text"
              :placeholder="t('components.chatwidget.your_name')"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
            />
            <input
              v-model="form.email"
              type="email"
              :placeholder="t('components.chatwidget.email_optional')"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
            />
            <input
              v-model="form.website"
              type="text"
              tabindex="-1"
              autocomplete="off"
              style="position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0"
              aria-hidden="true"
            />
            <button
              @click="startChat"
              :disabled="!form.name.trim() || starting"
              class="w-full theme-primary-bg text-white py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 disabled:opacity-50"
            >
              {{ starting ? t('common.connecting') : 'Rozpocznij czat' }}
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div v-else class="flex-1 overflow-y-auto p-4 space-y-2" ref="scrollEl">
          <div v-if="!messages.length" class="text-center text-gray-400 py-8 text-sm">
            <i class="fa-solid fa-comment-dots text-2xl mb-2 block"></i>
            {{ t('components.chatwidget.write_the_first_message') }}
          </div>
          <div
            v-for="msg in messages"
            :key="msg.id"
            :class="msg.sender === 'customer' ? 'flex justify-end' : 'flex justify-start'"
          >
            <div
              :class="[
                'max-w-[75%] px-3 py-2 rounded-2xl text-sm',
                msg.sender === 'customer'
                  ? 'theme-primary-bg text-white rounded-br-sm'
                  : 'bg-gray-100 text-gray-900 rounded-bl-sm',
              ]"
            >
              {{ msg.body }}
            </div>
          </div>
        </div>

        <!-- Input -->
        <div v-if="conversationId" class="border-t border-gray-100 p-3 flex gap-2">
          <input
            v-model="newMessage"
            @keydown.enter.prevent="sendMessage"
            type="text"
            :placeholder="t('components.chatwidget.write_a_message')"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
          />
          <button
            @click="sendMessage"
            :disabled="!newMessage.trim()"
            class="theme-primary-bg text-white w-9 h-9 rounded-xl flex items-center justify-center hover:opacity-90 disabled:opacity-50 shrink-0"
          >
            <i class="fa-solid fa-paper-plane text-xs"></i>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Toggle button -->
    <button
      @click="open = !open"
      class="theme-primary-bg text-white w-14 h-14 rounded-full shadow-xl flex items-center justify-center hover:opacity-90 transition relative"
    >
      <i :class="open ? 'fa-solid fa-xmark text-xl' : 'fa-solid fa-comments text-xl'"></i>
      <span
        v-if="unreadCount"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center"
      >
        {{ unreadCount }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const open = ref(false)
const starting = ref(false)
const conversationId = ref(null)
const messages = ref([])
const newMessage = ref('')
const unreadCount = ref(0)
const scrollEl = ref(null)
const form = ref({ name: '', email: '', website: '' })
let echoChannel = null

watch(open, (isOpen) => {
  if (isOpen) unreadCount.value = 0
})

const scrollBottom = () =>
  nextTick(() => {
    if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  })

onMounted(() => {
  const saved = sessionStorage.getItem('chat_conv_id')
  if (saved) {
    conversationId.value = parseInt(saved)
    loadMessages()
    subscribeToChannel(conversationId.value)
  }
})

onUnmounted(() => leaveChannel())

const subscribeToChannel = (id) => {
  if (!window.Echo) return
  leaveChannel()
  echoChannel = window.Echo.channel(`chat.${id}`).listen('.message.sent', (e) => {
    if (e.sender === 'staff') {
      const exists = messages.value.some((m) => m.id === e.id)
      if (!exists) {
        messages.value.push(e)
        if (!open.value) unreadCount.value++
        scrollBottom()
      }
    }
  })
}

const leaveChannel = () => {
  if (echoChannel) {
    echoChannel.stopListening('.message.sent')
    echoChannel = null
  }
}

const loadMessages = async () => {
  if (!conversationId.value) return
  try {
    const { data } = await axios.get(route('tenant.chat.poll', conversationId.value))
    messages.value = data
    scrollBottom()
  } catch {
    // A poll that fails is not worth telling the visitor about; the next
    // one is ten seconds away.
  }
}

const startChat = async () => {
  if (!form.value.name.trim()) return
  starting.value = true
  try {
    const { data } = await axios.post(route('tenant.chat.start'), {
      name: form.value.name,
      email: form.value.email || form.value.name + '@guest.local',
      website: form.value.website,
    })
    conversationId.value = data.conversation_id
    sessionStorage.setItem('chat_conv_id', data.conversation_id)
    subscribeToChannel(data.conversation_id)
  } finally {
    starting.value = false
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || !conversationId.value) return
  const body = newMessage.value
  newMessage.value = ''
  try {
    const { data } = await axios.post(route('tenant.chat.send', conversationId.value), { body })
    messages.value.push(data)
    scrollBottom()
  } catch {
    newMessage.value = body
  }
}
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.97);
}
</style>
