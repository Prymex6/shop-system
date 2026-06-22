// Push notification handler — imported by the Workbox SW via importScripts
// This file is intentionally separate so it survives Vite rebuilds

self.addEventListener('push', (event) => {
  if (!event.data) return

  let data
  try {
    data = event.data.json()
  } catch (e) {
    data = { title: 'Nowe powiadomienie', body: event.data.text() }
  }

  event.waitUntil(
    self.registration.showNotification(data.title || 'Sklep', {
      body: data.body || '',
      icon: '/pwa-192x192.png',
      badge: '/pwa-192x192.png',
      tag: data.tag || 'shop-notification',
      requireInteraction: false,
      data: { url: data.data?.url || '/manager' },
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url || '/'
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) return client.focus()
      }
      if (clients.openWindow) return clients.openWindow(url)
    }),
  )
})
