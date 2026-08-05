<template>
  <div v-if="locales.length > 1" class="relative">
    <button
      type="button"
      class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
      :aria-label="t('common.change_language')"
      @click="open = !open"
    >
      <span>{{ FLAGS[current] ?? '🌐' }}</span>
      <span class="uppercase">{{ current }}</span>
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
      <div v-if="open" class="absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-50">
        <button
          v-for="locale in locales"
          :key="locale"
          type="button"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 first:rounded-t-md last:rounded-b-md"
          :class="{ 'bg-blue-50 text-blue-700 font-semibold': locale === current }"
          @click="select(locale)"
        >
          <span>{{ FLAGS[locale] ?? '🌐' }}</span>
          <span>{{ NAMES[locale] ?? locale }}</span>
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

const current = computed(() => page.props.locale ?? 'pl')
const locales = computed(() => page.props.availableLocales ?? [current.value])

// Each language is named in itself, because someone looking for their own
// language is looking for the word they would use for it.
const NAMES = { pl: t('components.localeswitcher.polish'), en: 'English' }
const FLAGS = { pl: '🇵🇱', en: '🇬🇧' }

function select(locale) {
  open.value = false
  if (locale === current.value) return

  // A full reload rather than an Inertia visit: the dictionary is chosen once
  // at boot, so the page has to come back through it to be re-rendered.
  router.post(
    route('tenant.locale.set'),
    { locale },
    {
      preserveScroll: true,
      onSuccess: () => window.location.reload(),
    },
  )
}

const closeOnOutsideClick = (event) => {
  if (!event.target.closest('.relative')) open.value = false
}

onMounted(() => document.addEventListener('click', closeOnOutsideClick))
onUnmounted(() => document.removeEventListener('click', closeOnOutsideClick))
</script>
