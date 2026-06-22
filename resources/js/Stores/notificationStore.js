import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  let pollTimer = null

  const unread = computed(() => notifications.value.filter((n) => !n.read_at))
  const unreadCount = computed(() => unread.value.length)

  async function fetch() {
    try {
      const res = await window.axios.get('/manager/notifications')
      notifications.value = res.data?.data ?? res.data ?? []
    } catch (e) {
      // Silently fail — endpoint may not exist yet
      console.warn('[NotificationStore] fetch failed:', e?.response?.status)
    }
  }

  function markRead(id) {
    const n = notifications.value.find((n) => n.id === id)
    if (n) n.read_at = new Date().toISOString()
    window.axios.patch(`/manager/notifications/${id}/read`).catch(() => {})
  }

  function markAllRead() {
    notifications.value.forEach((n) => {
      n.read_at = n.read_at ?? new Date().toISOString()
    })
    window.axios.post('/manager/notifications/read-all').catch(() => {})
  }

  function init() {
    fetch()
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = setInterval(fetch, 60_000)
  }

  function destroy() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  return {
    notifications,
    unread,
    unreadCount,
    fetch,
    markRead,
    markAllRead,
    init,
    destroy,
  }
})
