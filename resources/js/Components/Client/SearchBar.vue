<template>
  <div class="relative w-full" ref="containerRef">
    <form @submit.prevent="goToSearch" role="search">
      <div class="relative">
        <input
          v-model="query"
          ref="inputRef"
          type="search"
          autocomplete="off"
          :placeholder="t('client.searchbar.search_products')"
          :aria-label="t('client.searchbar.search_for_a_product')"
          aria-autocomplete="list"
          :aria-expanded="showDropdown"
          aria-haspopup="listbox"
          class="w-full pl-4 pr-10 py-2 text-sm border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:border-transparent focus:ring-2 focus:theme-primary-ring transition outline-none"
          @keydown.down.prevent="moveDown"
          @keydown.up.prevent="moveUp"
          @keydown.enter.prevent="selectOrSearch"
          @keydown.esc="close"
          @focus="onFocus"
        />
        <button
          type="submit"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition"
        >
          <i v-if="!loading" class="fa-solid fa-magnifying-glass text-sm"></i>
          <i v-else class="fa-solid fa-spinner animate-spin text-sm"></i>
        </button>
      </div>
    </form>

    <!-- Autocomplete dropdown -->
    <Transition name="dropdown">
      <div
        v-if="showDropdown"
        role="listbox"
        class="absolute left-0 right-0 mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl z-[200] overflow-hidden"
      >
        <!-- Results -->
        <div v-if="results.length">
          <Link
            v-for="(item, idx) in results"
            :key="item.id"
            :href="route('tenant.product.show', item.slug)"
            role="option"
            :aria-selected="activeIndex === idx"
            class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition cursor-pointer"
            :class="activeIndex === idx ? 'bg-gray-50' : ''"
            @click="close"
            @mouseenter="activeIndex = idx"
          >
            <!-- Thumbnail -->
            <div class="w-10 h-10 rounded-lg bg-gray-100 shrink-0 overflow-hidden flex items-center justify-center">
              <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
              <i v-else class="fa-regular fa-image text-gray-300 text-sm"></i>
            </div>
            <!-- Name + price -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-sm font-semibold theme-primary">{{ formatPrice(item.price) }}</span>
                <span
                  v-if="item.compare_price && item.compare_price > item.price"
                  class="text-xs text-gray-400 line-through"
                  >{{ formatPrice(item.compare_price) }}</span
                >
              </div>
            </div>
            <i class="fa-solid fa-arrow-right text-xs text-gray-300 shrink-0"></i>
          </Link>

          <!-- View all -->
          <Link
            :href="route('tenant.shop.search') + '?q=' + encodeURIComponent(query)"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-t border-gray-100 hover:bg-gray-50 transition"
            :class="activeIndex === results.length ? 'bg-gray-50' : ''"
            @click="close"
            @mouseenter="activeIndex = results.length"
          >
            <i class="fa-solid fa-magnifying-glass text-xs text-gray-400"></i>
            <span class="text-gray-600"
              >{{ t('client.searchbar.show_every_result_for') }}
              <strong class="text-gray-900">"{{ query }}"</strong></span
            >
          </Link>
        </div>

        <!-- No results -->
        <div v-else-if="searched && !loading" class="px-4 py-4 text-sm text-gray-500 text-center">
          <i class="fa-regular fa-face-sad-tear text-gray-300 text-2xl mb-2 block"></i>
          {{ t('client.searchbar.no_products_for') }} <strong>"{{ query }}"</strong>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import { useCurrency } from '@/composables/useCurrency'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const query = ref('')
const results = ref([])
const loading = ref(false)
const searched = ref(false)
const activeIndex = ref(-1)
const containerRef = ref(null)
const inputRef = ref(null)

let debounceTimer = null

const showDropdown = computed(() => query.value.length >= 2 && (results.value.length > 0 || searched.value))

watch(query, (val) => {
  activeIndex.value = -1
  searched.value = false
  clearTimeout(debounceTimer)

  if (val.length < 2) {
    results.value = []
    return
  }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const { data } = await axios.get(route('tenant.api.search-suggest'), { params: { q: val } })
      results.value = data
      searched.value = true
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 280)
})

const moveDown = () => {
  const max = results.value.length // +1 for "view all" link
  activeIndex.value = activeIndex.value < max ? activeIndex.value + 1 : 0
}

const moveUp = () => {
  const max = results.value.length
  activeIndex.value = activeIndex.value > 0 ? activeIndex.value - 1 : max
}

const selectOrSearch = () => {
  if (activeIndex.value >= 0 && activeIndex.value < results.value.length) {
    router.visit(route('tenant.product.show', results.value[activeIndex.value].slug))
    close()
  } else {
    goToSearch()
  }
}

const goToSearch = () => {
  if (!query.value.trim()) return
  router.visit(route('tenant.shop.search') + '?q=' + encodeURIComponent(query.value.trim()))
  close()
}

const close = () => {
  results.value = []
  searched.value = false
  activeIndex.value = -1
}

const onFocus = () => {
  if (query.value.length >= 2 && !results.value.length) {
    // re-trigger if user focused back
    watch(query, () => {}, { once: true })
  }
}

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    close()
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(debounceTimer)
})

const { formatPrice } = useCurrency()
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>
