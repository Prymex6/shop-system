import { defineStore } from 'pinia'
import axios from 'axios'

let trackingTimeout = null

function getSessionId() {
  let id = localStorage.getItem('cart_session_id')
  if (!id) {
    id = crypto.randomUUID?.() ?? Date.now() + '-' + Math.random().toString(36).slice(2)
    localStorage.setItem('cart_session_id', id)
  }
  return id
}

export const useCartTrackingStore = defineStore('cartTracking', {
  state: () => ({
    lastTracked: null,
  }),

  actions: {
    /**
     * Track cart changes. Throttled to once per 30 seconds.
     * @param {Object} payload - { email, items }
     */
    track(payload) {
      const now = Date.now()

      // Throttle: only once per 30 seconds
      if (this.lastTracked && now - this.lastTracked < 30000) {
        // Schedule a final call after the throttle window
        if (trackingTimeout) clearTimeout(trackingTimeout)
        trackingTimeout = setTimeout(
          () => {
            this._sendTrack(payload)
          },
          30000 - (now - this.lastTracked),
        )
        return
      }

      if (trackingTimeout) clearTimeout(trackingTimeout)
      this._sendTrack(payload)
    },

    async _sendTrack(payload) {
      try {
        const email = payload.email || localStorage.getItem('cart_tracking_email') || null
        await axios.post('/api/cart/track', {
          session_id: getSessionId(),
          email,
          items: payload.items ?? [],
        })
        this.lastTracked = Date.now()
      } catch {
        // Silently fail — tracking should not break the cart
      }
    },

    async convert() {
      try {
        await axios.post('/api/cart/convert', { session_id: getSessionId() })
      } catch {
        // Silently fail
      }
    },

    setEmail(email) {
      if (email) localStorage.setItem('cart_tracking_email', email)
    },
  },
})
