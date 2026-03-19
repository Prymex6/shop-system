<template>
  <Head :title="t('common.password_reset')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <img
          v-if="$page.props.tenant?.logo_url"
          :src="$page.props.tenant.logo_url"
          alt="Logo"
          class="h-16 mx-auto mb-3 object-contain"
        />
        <img v-else src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
        <h1 class="text-2xl font-bold text-gray-900">{{ $page.props.tenant?.name ?? $page.props.app_name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('auth.forgotpassword.staff_panel_password_reset') }}</p>
      </div>

      <div
        v-if="$page.props.errors?.throttle"
        class="bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
      >
        <i class="fa-solid fa-triangle-exclamation"></i>
        {{ $page.props.errors.throttle }}
      </div>

      <div
        v-if="$page.props.flash?.success"
        class="bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
      >
        <i class="fa-solid fa-circle-check"></i>
        {{ $page.props.flash.success }}
      </div>

      <div class="bg-white rounded-lg shadow-md p-8">
        <p class="text-sm text-gray-600 mb-6">
          {{ t('common.give_us_your_email_address_and') }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ t('common.email_address') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.email }"
              :placeholder="t('common.john_shop_com')"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
            {{ form.processing ? t('common.sending_2') : t('common.send_the_reset_link') }}
          </button>
        </form>
      </div>

      <div class="text-center">
        <a :href="route('tenant.login')" class="text-sm text-blue-600 hover:text-blue-700">
          {{ t('common.back_to_sign_in') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({ email: '' })

const submit = () => {
  form.post(route('tenant.password.email'))
}
</script>
