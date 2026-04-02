import { defineStore } from 'pinia'

const STORAGE_KEY = 'recently_viewed'
const MAX_ITEMS = 10

export const useRecentlyViewedStore = defineStore('recentlyViewed', {
  state: () => ({
    items: loadFromStorage(),
  }),

  actions: {
    add(product) {
      if (!product?.id) return

      const entry = {
        id: product.id,
        name: product.name,
        slug: product.slug,
        price: product.price,
        image: product.images?.[0]?.path ?? product.image ?? null,
      }

      // Remove if already exists (to move it to front)
      this.items = this.items.filter((i) => i.id !== product.id)

      // Add to front
      this.items.unshift(entry)

      // Keep only last MAX_ITEMS
      if (this.items.length > MAX_ITEMS) {
        this.items = this.items.slice(0, MAX_ITEMS)
      }

      saveToStorage(this.items)
    },

    getAll() {
      return this.items
    },

    clear() {
      this.items = []
      localStorage.removeItem(STORAGE_KEY)
    },
  },
})

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

function saveToStorage(items) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  } catch {
    // Ignore storage errors
  }
}
