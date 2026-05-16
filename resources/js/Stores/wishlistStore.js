import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useWishlistStore = defineStore('wishlist', () => {
  const productIds = ref(new Set())

  const count = computed(() => productIds.value.size)

  function has(productId) {
    return productIds.value.has(productId)
  }

  async function toggle(productId) {
    try {
      const response = await axios.post(route('tenant.wishlist.toggle', productId))
      if (response.data.added) {
        productIds.value.add(productId)
      } else {
        productIds.value.delete(productId)
      }
      save()
    } catch (e) {
      console.error('Wishlist toggle failed', e)
    }
  }

  function load() {
    const saved = localStorage.getItem('shop_wishlist')
    if (saved) {
      try {
        productIds.value = new Set(JSON.parse(saved))
      } catch {
        // Same as the cart: unreadable storage starts empty.
      }
    }
  }

  function save() {
    localStorage.setItem('shop_wishlist', JSON.stringify([...productIds.value]))
  }

  load()

  return { productIds, count, has, toggle }
})
