<template>
  <Head :title="title ? `${title} — ${appName} Admin` : `${appName} Admin`" />

  <div class="min-h-screen bg-gray-100">
    <FlashMessage />

    <!-- Support notification toast -->
    <div
      v-if="supportAlert"
      class="fixed top-4 right-4 z-50 bg-indigo-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 max-w-sm"
    >
      <i class="fa-solid fa-headset text-lg"></i>
      <span class="text-sm font-medium">{{ supportAlert }}</span>
      <button @click="supportAlert = null" class="ml-auto text-indigo-200 hover:text-white">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex-shrink-0 flex items-center">
            <Link :href="route('landlord.dashboard')" class="text-xl font-bold text-indigo-600">
              <img src="/images/logo.png" alt="Logo" class="h-7 w-auto inline-block mr-1 align-middle" />
              {{ appName }} Admin
            </Link>
          </div>

          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">{{ auth?.name }}</span>
            <Link
              :href="route('landlord.logout')"
              method="post"
              as="button"
              class="text-red-600 hover:text-red-700 text-sm font-medium"
            >
              {{ t('common.sign_out') }}
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex">
      <!-- Sidebar Navigation -->
      <aside class="w-64 bg-white shadow-sm min-h-screen">
        <nav class="mt-5 px-4">
          <div class="space-y-1">
            <Link
              :href="route('landlord.dashboard')"
              :class="[
                isActive('dashboard')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-chart-line mr-3 w-5 text-center"></i>
              Dashboard
            </Link>

            <Link
              :href="route('landlord.tenants.index')"
              :class="[
                isActive('tenants')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-store mr-3 w-5 text-center"></i>
              {{ t('layout.landlordlayout.shops') }}
            </Link>

            <Link
              :href="route('landlord.modifications.index')"
              :class="[
                isActive('modifications')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-screwdriver-wrench mr-3 w-5 text-center"></i>
              {{ t('layout.landlordlayout.modifications') }}
            </Link>

            <Link
              :href="route('landlord.support.index')"
              :class="[
                isActive('support')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-headset mr-3 w-5 text-center"></i>
              {{ t('layout.landlordlayout.support') }}
              <span
                v-if="newSupportCount > 0"
                class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center"
              >
                {{ newSupportCount }}
              </span>
            </Link>

            <Link
              :href="route('landlord.contacts.index')"
              :class="[
                isActive('contacts')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-envelope mr-3 w-5 text-center"></i>
              {{ t('layout.landlordlayout.contacts') }}
            </Link>

            <Link
              :href="route('landlord.shop-search.index')"
              :class="[
                isActive('shop-search')
                  ? 'bg-blue-50 border-blue-500 text-blue-700'
                  : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors',
              ]"
            >
              <i class="fa-solid fa-magnifying-glass-location mr-3 w-5 text-center"></i>
              {{ t('layout.landlordlayout.search_shops') }}
            </Link>
          </div>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-8">
        <h1 v-if="title" class="text-2xl font-bold text-gray-900 mb-6">{{ title }}</h1>
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import FlashMessage from '@/Components/FlashMessage.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  title: String,
})

const page = usePage()
const auth = page.props.auth?.user
const appName = page.props.app_name ?? 'Storelo'
const supportAlert = ref(null)
const newSupportCount = ref(0)
let echoSupport = null

const isActive = (section) => {
  const url = page.url
  if (section === 'dashboard') return url === '/admin' || url === '/admin/' || url.includes('/admin/dashboard')
  return url.includes(`/admin/${section}`)
}

onMounted(() => {
  window.Pusher = Pusher
  if (!window.Echo) {
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT,
      wssPort: import.meta.env.VITE_REVERB_PORT,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/admin/broadcasting/auth',
      auth: {
        headers: {
          'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
        },
      },
    })
  }

  echoSupport = window.Echo.private('support-admin').listen('.support.message', (e) => {
    const label =
      e.author_type === 'tenant'
        ? t('layout.landlordlayout.new_ticket_a', { a: e.subject })
        : t('layout.landlordlayout.customer_replied_a', { a: e.subject })
    supportAlert.value = label
    setTimeout(() => {
      supportAlert.value = null
    }, 7000)
    if (page.url.includes('/landlord/support')) {
      router.reload()
    } else {
      newSupportCount.value++
    }
  })
})

watch(
  () => page.url,
  (url) => {
    if (url.includes('/landlord/support')) {
      newSupportCount.value = 0
    }
  },
)

onUnmounted(() => {
  if (echoSupport) window.Echo?.leave('support-admin')
})
</script>
