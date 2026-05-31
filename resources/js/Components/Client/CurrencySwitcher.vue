<template>
  <div class="relative" v-if="enabledCurrencies.length > 1">
    <button
      @click="open = !open"
      class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
      <span>{{ flagFor(currentCurrency) }}</span>
      <span>{{ currentCurrency }}</span>
      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="open" class="absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-md shadow-lg z-50">
        <button
          v-for="currency in enabledCurrencies"
          :key="currency"
          @click="selectCurrency(currency)"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 first:rounded-t-md last:rounded-b-md"
          :class="{ 'bg-blue-50 text-blue-700 font-semibold': currency === currentCurrency }"
        >
          <span>{{ flagFor(currency) }}</span>
          <span>{{ currency }}</span>
          <span class="ml-auto text-xs text-gray-400">{{ labelFor(currency) }}</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const page = usePage()
const open = ref(false)

const currentCurrency = computed(() => page.props.current_currency ?? 'PLN')
const enabledCurrencies = computed(() => page.props.enabled_currencies ?? ['PLN'])

const FLAGS = {
  PLN: '🇵🇱',
  EUR: '🇪🇺',
  USD: '🇺🇸',
  GBP: '🇬🇧',
  CZK: '🇨🇿',
}

const LABELS = {
  PLN: t('common.z_oty'),
  EUR: 'euro',
  USD: 'dollar',
  GBP: 'pound',
  CZK: 'koruna',
}

const flagFor = (c) => FLAGS[c] ?? c
const labelFor = (c) => LABELS[c] ?? ''

const selectCurrency = (currency) => {
  open.value = false
  if (currency === currentCurrency.value) return

  router.post(
    '/currency',
    { currency },
    {
      preserveScroll: true,
      onSuccess: () => window.location.reload(),
    },
  )
}

// Close on outside click
const handleClick = (e) => {
  if (!e.target.closest('.relative')) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClick))
onUnmounted(() => document.removeEventListener('click', handleClick))
</script>
