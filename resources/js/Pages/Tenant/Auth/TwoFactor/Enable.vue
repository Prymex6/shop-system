<template>
  <ManagerLayout :title="t('auth.twofactor.enable.two_factor_authentication_2fa')">
    <div class="max-w-xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('auth.twofactor.enable.two_factor_authentication_2fa') }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ t('auth.twofactor.enable.once_this_is_on_every_sign') }}
        </p>
      </div>

      <div
        v-if="flashRecoveryCodes && flashRecoveryCodes.length"
        class="bg-amber-50 border border-amber-300 rounded-lg p-5 space-y-3"
      >
        <p class="text-sm font-semibold text-amber-800">
          {{ t('auth.twofactor.enable.write_these_recovery_codes_down_they') }}
        </p>
        <p class="text-xs text-amber-700">
          {{ t('auth.twofactor.enable.each_code_works_once_in_place') }}
        </p>
        <div class="grid grid-cols-2 gap-2">
          <code
            v-for="code in flashRecoveryCodes"
            :key="code"
            class="bg-white border border-amber-200 rounded px-3 py-1.5 text-sm font-mono text-center"
          >
            {{ code }}
          </code>
        </div>
        <button
          @click="copyAll"
          class="text-xs px-3 py-1.5 bg-amber-700 hover:bg-amber-800 text-white rounded transition"
        >
          {{ copied ? 'Skopiowano!' : t('common.copy_all') }}
        </button>
      </div>

      <div v-if="!twoFactorEnabled" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
        <p class="text-sm text-gray-600">
          {{ t('auth.twofactor.enable.2fa_is_currently') }}
          <span class="font-semibold text-red-600">{{ t('auth.twofactor.enable.off') }}</span
          >{{ t('auth.twofactor.enable.confirm_with_your_password_to_turn') }}
        </p>
        <form @submit.prevent="submitEnable" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.password') }}</label>
            <input
              v-model="enableForm.password"
              type="password"
              required
              autocomplete="current-password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': enableForm.errors.password }"
            />
            <p v-if="enableForm.errors.password" class="mt-1 text-xs text-red-600">{{ enableForm.errors.password }}</p>
          </div>
          <button
            type="submit"
            :disabled="enableForm.processing"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
          >
            {{ enableForm.processing ? t('common.turning_it_on') : t('common.turn_2fa_on') }}
          </button>
        </form>
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        <p class="text-sm text-gray-600">
          {{ t('auth.twofactor.enable.2fa_is_currently') }}
          <span class="font-semibold text-green-600">{{ t('auth.twofactor.enable.on') }}</span
          >{{ t('auth.twofactor.enable.you_have') }} <strong>{{ recoveryCodesCount }}</strong>
          {{ t('auth.twofactor.enable.recovery_codes') }}
        </p>

        <div class="border-t border-gray-100 pt-4">
          <p class="text-sm font-medium text-gray-700 mb-2">
            {{ t('auth.twofactor.enable.generate_new_recovery_codes') }}
          </p>
          <p class="text-xs text-gray-500 mb-3">{{ t('auth.twofactor.enable.the_old_codes_stop_working') }}</p>
          <form @submit.prevent="submitRegenerate" class="flex items-end gap-3">
            <div class="flex-1">
              <input
                v-model="regenerateForm.password"
                type="password"
                required
                :placeholder="t('common.password')"
                autocomplete="current-password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': regenerateForm.errors.password }"
              />
            </div>
            <button
              type="submit"
              :disabled="regenerateForm.processing"
              class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50"
            >
              {{ t('auth.twofactor.enable.generate') }}
            </button>
          </form>
        </div>

        <div class="border-t border-gray-100 pt-4">
          <p class="text-sm font-medium text-gray-700 mb-2">{{ t('auth.twofactor.enable.turn_2fa_off') }}</p>
          <form @submit.prevent="submitDisable" class="flex items-end gap-3">
            <div class="flex-1">
              <input
                v-model="disableForm.password"
                type="password"
                required
                :placeholder="t('common.password')"
                autocomplete="current-password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                :class="{ 'border-red-500': disableForm.errors.password }"
              />
            </div>
            <button
              type="submit"
              :disabled="disableForm.processing"
              class="px-4 py-2 border border-red-300 text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 transition-colors disabled:opacity-50"
            >
              {{ t('auth.twofactor.enable.turn_off') }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  two_factor_enabled: { type: Boolean, default: false },
  recovery_codes_count: { type: Number, default: 0 },
})

const page = usePage()
const copied = ref(false)

// Reflects the current DB state on initial load; flipped optimistically after
// enable/disable so the UI doesn't need a full reload to update.
const twoFactorEnabled = ref(props.two_factor_enabled)
const recoveryCodesCount = ref(props.recovery_codes_count)

const flashRecoveryCodes = computed(() => page.props.flash?.recovery_codes)

const enableForm = useForm({ password: '' })
const disableForm = useForm({ password: '' })
const regenerateForm = useForm({ password: '' })

function submitEnable() {
  enableForm.post(route('tenant.manager.2fa.enable.post'), {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorEnabled.value = true
      recoveryCodesCount.value = 8
      enableForm.reset()
    },
  })
}

function submitDisable() {
  disableForm.post(route('tenant.manager.2fa.disable'), {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorEnabled.value = false
      recoveryCodesCount.value = 0
      disableForm.reset()
    },
  })
}

function submitRegenerate() {
  regenerateForm.post(route('tenant.manager.2fa.recovery-codes'), {
    preserveScroll: true,
    onSuccess: () => {
      recoveryCodesCount.value = 8
      regenerateForm.reset()
    },
  })
}

function copyAll() {
  if (!flashRecoveryCodes.value) return
  navigator.clipboard.writeText(flashRecoveryCodes.value.join('\n'))
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}
</script>
