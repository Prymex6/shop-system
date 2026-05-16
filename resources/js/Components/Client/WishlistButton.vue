<template>
  <button
    @click.prevent="toggle"
    :title="isInWishlist ? t('client.wishlist.remove_from_the_wishlist') : t('common.add_to_the_wishlist')"
    :class="[
      'flex items-center justify-center transition-all duration-150',
      sizeClass,
      isInWishlist ? 'text-red-500 hover:text-red-600' : 'text-gray-300 hover:text-red-400',
    ]"
  >
    <svg
      :class="iconClass"
      viewBox="0 0 24 24"
      :fill="isInWishlist ? 'currentColor' : 'none'"
      stroke="currentColor"
      stroke-width="2"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
      />
    </svg>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useWishlistStore } from '@/Stores/wishlistStore'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  productId: { type: Number, required: true },
  size: { type: String, default: 'md' }, // sm | md | lg
})

const wishlistStore = useWishlistStore()
const isInWishlist = computed(() => wishlistStore.has(props.productId))

const sizeClass = computed(
  () =>
    ({
      sm: 'w-7 h-7',
      md: 'w-9 h-9',
      lg: 'w-11 h-11',
    })[props.size] ?? 'w-9 h-9',
)

const iconClass = computed(
  () =>
    ({
      sm: 'w-4 h-4',
      md: 'w-5 h-5',
      lg: 'w-6 h-6',
    })[props.size] ?? 'w-5 h-5',
)

function toggle() {
  wishlistStore.toggle(props.productId)
}
</script>
