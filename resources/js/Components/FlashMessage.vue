<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 transform translate-y-2"
    enter-to-class="opacity-100 transform translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 transform translate-y-0"
    leave-to-class="opacity-0 transform translate-y-2"
  >
    <div
      v-if="show"
      :class="{
        'bg-green-50 border-green-400': type === 'success',
        'bg-red-50 border-red-400': type === 'error',
        'bg-blue-50 border-blue-400': type === 'info',
      }"
      class="fixed top-4 right-4 max-w-md w-full border-l-4 p-4 shadow-lg rounded-md z-50"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <i v-if="type === 'success'" class="fa-solid fa-circle-check h-5 w-5 text-green-400"></i>
          <i v-else-if="type === 'error'" class="fa-solid fa-circle-xmark h-5 w-5 text-red-400"></i>
          <i v-else class="fa-solid fa-circle-info h-5 w-5 text-blue-400"></i>
        </div>
        <div class="ml-3 w-0 flex-1">
          <p
            :class="{
              'text-green-800': type === 'success',
              'text-red-800': type === 'error',
              'text-blue-800': type === 'info',
            }"
            class="text-sm font-medium"
          >
            {{ message }}
          </p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="show = false"
            :class="{
              'text-green-500 hover:text-green-600': type === 'success',
              'text-red-500 hover:text-red-600': type === 'error',
              'text-blue-500 hover:text-blue-600': type === 'info',
            }"
            class="rounded-md inline-flex focus:outline-none focus:ring-2 focus:ring-offset-2"
          >
            <span class="sr-only">{{ t('common.close') }}</span>
            <i class="fa-solid fa-xmark h-5 w-5"></i>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const page = usePage()
const show = ref(false)
const message = ref('')
const type = ref('success')

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      message.value = flash.success
      type.value = 'success'
      show.value = true
      setTimeout(() => {
        show.value = false
      }, 4000)
    } else if (flash?.error) {
      message.value = flash.error
      type.value = 'error'
      show.value = true
      setTimeout(() => {
        show.value = false
      }, 4000)
    }
  },
  { immediate: true, deep: true },
)
</script>
