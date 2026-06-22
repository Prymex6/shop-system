const CACHE_NAME = 'shop-app-v1'
const STATIC_ASSETS = ['/manifest.json', '/offline.html']

// Install – cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)))
  self.skipWaiting()
})

// Activate – clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))),
  )
  self.clients.claim()
})

// Fetch – network first, fallback to cache
self.addEventListener('fetch', (event) => {
  // Skip non-GET and non-http requests
  if (event.request.method !== 'GET') return
  if (!event.request.url.startsWith('http')) return

  // Skip API / admin / checkout / account routes – always network, never cached.
  // Route names are Polish in this app (checkout = /kasa, payment = /platnosc,
  // customer account/order-history/downloads = /moje-konto) — the earlier
  // English names here never matched anything real and left these pages
  // cacheable, which matters on a shared/public device.
  const url = new URL(event.request.url)
  if (
    url.pathname.startsWith('/manager') ||
    url.pathname.startsWith('/staff') ||
    url.pathname.startsWith('/kasa') ||
    url.pathname.startsWith('/platnosc') ||
    url.pathname.startsWith('/moje-konto') ||
    url.pathname.startsWith('/api')
  ) {
    return
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Cache successful GET responses for static assets
        if (response.ok && response.type === 'basic') {
          const clone = response.clone()
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone))
        }
        return response
      })
      .catch(async () => {
        // Try cache first, then serve offline page for navigation requests
        const cached = await caches.match(event.request)
        if (cached) return cached
        if (event.request.mode === 'navigate') {
          return caches.match('/offline.html')
        }
        return new Response('', { status: 503, statusText: 'Service Unavailable' })
      }),
  )
})

// Push notifications
self.addEventListener('push', (event) => {
  if (!event.data) return
  const data = event.data.json()
  event.waitUntil(
    self.registration.showNotification(data.title || 'Sklep', {
      body: data.body || '',
      icon: '/pwa-192x192.png',
      badge: '/pwa-192x192.png',
      tag: data.tag || 'shop-notification',
      data: { url: data.url || '/' },
    }),
  )
})

// Notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url || '/'
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) return client.focus()
      }
      if (clients.openWindow) return clients.openWindow(url)
    }),
  )
})
