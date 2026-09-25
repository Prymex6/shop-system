import './bootstrap'
import axios from 'axios'

// Handle CSRF token expiry (419) globally — reload to get a fresh token
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 419) {
      window.location.reload()
    }
    return Promise.reject(error)
  },
)

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import { ZiggyVue } from 'ziggy-js'
import { createI18nFor } from './i18n'

// Purge the service worker's caches on logout (staff /logout, customer
// /wyloguj) so a previous user's cached pages (orders, account, etc.) can't
// be served to the next person on a shared/public device while offline or
// on a flaky connection.
router.on('before', (event) => {
  const path = event.detail.visit.url.pathname
  if (path === '/logout' || path === '/wyloguj') {
    if ('caches' in window) {
      caches.keys().then((keys) => keys.forEach((key) => caches.delete(key)))
    }
  }
})

let appName = import.meta.env.VITE_APP_NAME || 'Laravel'

// The shop decides which language it is served in, so the dictionary is
// known before the first component renders and is read straight off the
// initial Inertia payload rather than waiting for a round trip.
const initialLocale = JSON.parse(document.getElementById('app')?.dataset.page ?? '{}')?.props?.locale

const i18n = await createI18nFor(initialLocale)

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  // Reload on CSRF expiry (419) so Inertia gets a fresh token
  onError: (error) => {
    if (error?.response?.status === 419) {
      window.location.reload()
    }
  },
  resolve: (name) => {
    // Not eager: each page becomes its own chunk, loaded on demand. With
    // eager:true every visitor — including a TikTok visitor who only ever
    // sees the shop — downloaded all 121 pages' JS, including the entire
    // admin/manager panel, on every single page load.
    const pages = import.meta.glob('./Pages/**/*.vue')
    const page = pages[`./Pages/${name}.vue`]
    if (!page) {
      throw new Error(`Page not found: ${name}. Make sure the file exists at ./Pages/${name}.vue`)
    }
    return page()
  },
  setup({ el, App, props, plugin }) {
    const shopName = props.initialPage?.props?.tenant?.name
    if (shopName) {
      appName = shopName
    }

    const app = createApp({ render: () => h(App, props) })

    app.use(plugin)
    app.use(i18n)
    app.use(createPinia())
    app.use(ZiggyVue)

    app.mount(el)

    return app
  },
  progress: {
    color: '#4B5563',
  },
})

// Service worker registration lives in app.blade.php (registers /sw.js).
// vite-plugin-pwa's own auto-generated service worker is intentionally NOT
// registered here — having two service workers both trying to control the
// same '/' scope is undefined/fragile, and only the hand-written /sw.js
// carries the push-notification handlers this app actually relies on.
