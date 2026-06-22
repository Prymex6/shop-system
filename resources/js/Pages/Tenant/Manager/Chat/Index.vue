<template>
  <ManagerLayout title="Live Chat">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Live Chat</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.chat.index.live_conversations_with_customers') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="totalUnread" class="bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-full">
            {{ totalUnread }} nowych
          </span>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div v-if="conversations.length">
          <div
            v-for="c in conversations"
            :key="c.id"
            @click="open(c)"
            class="flex items-center gap-4 px-5 py-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition"
          >
            <!-- Avatar -->
            <div
              class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-sm shrink-0"
            >
              {{ initials(c) }}
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="font-semibold text-gray-900 text-sm">
                  {{ c.guest_name || c.customer?.name || t('common.guest') }}
                </p>
                <span
                  v-if="c.unread_count"
                  class="bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center"
                >
                  {{ c.unread_count }}
                </span>
              </div>
              <p class="text-xs text-gray-500 truncate mt-0.5">
                {{ c.latest_message?.body || t('manager.chat.show.no_messages') }}
              </p>
            </div>
            <!-- Meta -->
            <div class="text-right shrink-0">
              <span
                :class="c.status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs font-semibold px-2 py-0.5 rounded-full"
              >
                {{ c.status === 'open' ? 'Otwarta' : t('common.closed_2') }}
              </span>
              <p class="text-xs text-gray-400 mt-1">{{ formatTime(c.last_message_at) }}</p>
            </div>
          </div>
        </div>

        <div v-else class="p-16 text-center text-gray-400">
          <i class="fa-solid fa-comments text-4xl mb-3"></i>
          <p>{{ t('manager.chat.index.no_active_conversations') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const page = usePage()
const props = defineProps({ conversations: { type: Array, default: () => [] } })

let echoChannel = null

onMounted(() => {
  const tenantId = page.props.tenant?.id
  if (window.Echo && tenantId) {
    echoChannel = window.Echo.private(`chat-manager.${tenantId}`).listen('.chat.activity', () =>
      router.reload({ only: ['conversations'] }),
    )
  }
})

onUnmounted(() => {
  if (echoChannel) {
    echoChannel.stopListening('.chat.activity')
    const tenantId = page.props.tenant?.id
    if (tenantId) window.Echo?.leave(`chat-manager.${tenantId}`)
  }
})

const totalUnread = computed(() => props.conversations.reduce((s, c) => s + (c.unread_count || 0), 0))

const initials = (c) => {
  const name = c.guest_name || c.customer?.name || 'G'
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatTime = (d) => {
  if (!d) return ''
  const date = new Date(d)
  const now = new Date()
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString(locale.value)
}

const open = (c) => router.visit(route('tenant.manager.chat.show', c.id))
</script>
