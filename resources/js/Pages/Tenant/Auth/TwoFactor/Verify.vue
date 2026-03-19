<template>
  <Head :title="t('auth.twofactor.verify.2fa_verification')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <img
          v-if="$page.props.tenant?.logo_url"
          :src="$page.props.tenant.logo_url"
          alt="Logo"
          class="h-16 mx-auto mb-3 object-contain"
        />
        <img v-else src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
        <h1 class="text-2xl font-bold text-gray-900">{{ t('auth.twofactor.verify.two_factor_authentication') }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ t('auth.twofactor.verify.we_have_sent_a_verification_code') }}
        </p>
      </div>

      <div
        v-if="sendMessage"
        class="bg-blue-50 border border-blue-300 text-blue-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
      >
        <i class="fa-solid fa-circle-info"></i>
        {{ sendMessage }}
      </div>

      <div class="bg-white rounded-lg shadow-md p-8">
        <form v-if="!useRecovery" @submit.prevent="submitCode" class="space-y-6">
          <div>
            <label for="code" class="block text-sm font-medium text-gray-700">
              {{ t('auth.twofactor.verify.verification_code') }}
            </label>
            <input
              id="code"
              v-model="form.code"
              type="text"
              inputmode="numeric"
              maxlength="6"
              required
              autofocus
              autocomplete="one-time-code"
              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-center text-2xl tracking-[0.5em] font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.code }"
              placeholder="------"
            />
            <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
              {{ form.errors.code }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
            {{ form.processing ? 'Weryfikacja...' : 'Zweryfikuj' }}
          </button>

          <button
            type="button"
            @click="resend"
            :disabled="resending || cooldown > 0"
            class="w-full text-sm text-blue-600 hover:text-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{
              cooldown > 0
                ? t('auth.twofactor.verify.send_again_a_s', { a: cooldown })
                : t('common.send_the_code_again')
            }}
          </button>
        </form>

        <form v-else @submit.prevent="submitCode" class="space-y-6">
          <div>
            <label for="recovery" class="block text-sm font-medium text-gray-700">
              {{ t('auth.twofactor.verify.recovery_code') }}
            </label>
            <input
              id="recovery"
              v-model="form.code"
              type="text"
              required
              autofocus
              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.code }"
              placeholder="XXXXXXXXXX"
            />
            <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
              {{ form.errors.code }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ form.processing ? 'Weryfikacja...' : 'Zweryfikuj kodem odzyskiwania' }}
          </button>
        </form>

        <div class="mt-4 text-center">
          <button type="button" @click="toggleRecovery" class="text-sm text-gray-500 hover:text-gray-700">
            {{ useRecovery ? t('common.back_to_the_emailed_code') : t('common.i_cannot_get_to_my_email') }}
          </button>
        </div>
      </div>

      <div class="text-center">
        <a :href="route('tenant.login')" class="text-sm text-gray-500 hover:text-gray-700">
          {{ t('auth.twofactor.verify.back_to_sign_in') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const useRecovery = ref(false)

function toggleRecovery() {
  useRecovery.value = !useRecovery.value
  form.clearErrors()
}
const sendMessage = ref('')
const resending = ref(false)
const cooldown = ref(0)
let cooldownTimer = null

const form = useForm({ code: '' })

function startCooldown() {
  cooldown.value = 60
  cooldownTimer = setInterval(() => {
    cooldown.value -= 1
    if (cooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}

async function resend() {
  resending.value = true
  sendMessage.value = ''
  try {
    const { data } = await axios.post(route('tenant.2fa.send-code'))
    sendMessage.value = data.message || t('common.code_sent')
    startCooldown()
  } catch (e) {
    sendMessage.value = e.response?.data?.message || t('common.the_code_could_not_be_sent')
  } finally {
    resending.value = false
  }
}

function submitCode() {
  form.post(route('tenant.2fa.verify.post'), {
    onFinish: () => form.reset('code'),
  })
}

onMounted(() => {
  resend()
})

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})
</script>
