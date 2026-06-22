<template>
  <div class="relative" ref="containerRef">
    <!-- Bell button -->
    <button
      type="button"
      @click="toggleDropdown"
      class="relative p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
      :aria-label="t('manager.notificationcenter.notifications')"
    >
      <i class="fa-solid fa-bell text-lg"></i>
      <span
        v-if="store.unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center px-0.5 leading-none"
      >
        {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown-fade">
      <div
        v-if="open"
        class="absolute right-0 top-full mt-2 w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <h3 class="text-sm font-bold text-gray-900">{{ t('manager.notificationcenter.notifications') }}</h3>
          <button
            v-if="store.unreadCount > 0"
            type="button"
            @click="markAll"
            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
          >
            {{ t('common.mark_all_as_read') }}
          </button>
        </div>

        <!-- List -->
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
          <div v-if="!store.notifications.length" class="px-4 py-8 text-center text-gray-400">
            <i class="fa-solid fa-bell-slash text-2xl mb-2 block opacity-40"></i>
            <p class="text-sm">{{ t('manager.notificationcenter.no_notifications') }}</p>
          </div>

          <a
            v-for="n in store.notifications"
            :key="n.id"
            :href="notificationLink(n)"
            @click.prevent="handleClick(n)"
            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
            :class="!n.read_at ? 'bg-indigo-50/40' : ''"
          >
            <!-- Icon -->
            <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm" :class="iconBg(n.type)">
              <i :class="iconClass(n.type)"></i>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900 leading-snug" :class="!n.read_at ? 'font-semibold' : 'font-normal'">
                {{ notificationText(n) }}
              </p>
              <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(n.created_at) }}</p>
            </div>

            <!-- Unread dot -->
            <div v-if="!n.read_at" class="shrink-0 w-2 h-2 rounded-full bg-indigo-500 mt-1.5"></div>
          </a>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-center">
          <button type="button" @click="store.fetch()" class="text-xs text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-rotate-right mr-1"></i>{{ t('common.refresh') }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/Stores/notificationStore'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'

const { t } = useI18n()
const { relative } = useFormatting()

const store = useNotificationStore()
const open = ref(false)
const containerRef = ref(null)

const toggleDropdown = () => {
  open.value = !open.value
}

const markAll = () => {
  store.markAllRead()
}

const handleClick = (n) => {
  if (!n.read_at) store.markRead(n.id)
  const link = notificationLink(n)
  if (link && link !== '#') router.visit(link)
  open.value = false
}

const notificationLink = (n) => {
  const links = {
    new_order: `/manager/orders`,
    low_stock: `/manager/inventory`,
    new_review: `/manager/reviews`,
    new_support_ticket: `/manager/support`,
    new_rma: `/manager/rma`,
  }
  return links[n.type] ?? '#'
}

const notificationText = (n) => {
  if (n.message) return n.message
  const texts = {
    new_order: t('manager.notificationcenter.new_order_a', { a: n.data?.order_number ?? '' }),
    low_stock: `Niski stan magazynowy: ${n.data?.product_name ?? 'produkt'}`,
    new_review: `Nowa recenzja produktu ${n.data?.product_name ?? ''}`,
    new_support_ticket: `Nowy ticket wsparcia: ${n.data?.subject ?? ''}`,
    new_rma: `Nowy wniosek zwrotu RMA #${n.data?.rma_number ?? ''}`,
  }
  return texts[n.type] ?? t('common.new_notification')
}

const iconClass = (type) => {
  const icons = {
    new_order: 'fa-solid fa-box text-blue-600',
    low_stock: 'fa-solid fa-triangle-exclamation text-orange-500',
    new_review: 'fa-solid fa-star text-yellow-500',
    new_support_ticket: 'fa-solid fa-headset text-purple-600',
    new_rma: 'fa-solid fa-rotate-left text-red-500',
  }
  return icons[type] ?? 'fa-solid fa-bell text-gray-500'
}

const iconBg = (type) => {
  const bgs = {
    new_order: 'bg-blue-100',
    low_stock: 'bg-orange-100',
    new_review: 'bg-yellow-100',
    new_support_ticket: 'bg-purple-100',
    new_rma: 'bg-red-100',
  }
  return bgs[type] ?? 'bg-gray-100'
}

const timeAgo = (date) => {
  if (!date) return ''
  return relative.value(date)
}

// Close on outside click
const handleOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutside)
  store.init()
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutside)
  store.destroy()
})
</script>

<style scoped>
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
