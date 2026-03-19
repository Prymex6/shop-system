<template>
  <Head :title="t('common.sign_in_2')" />

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
        <h1 class="text-2xl font-bold text-gray-900">{{ $page.props.tenant?.name ?? $page.props.app_name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('auth.login.staff_panel_sign_in_to_continue') }}</p>
      </div>

      <div
        v-if="$page.props.errors?.throttle"
        class="bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
      >
        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
        {{ $page.props.errors.throttle }}
      </div>

      <div class="bg-white rounded-lg shadow-md p-8">
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              {{ t('common.email_address') }}
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              autocomplete="username"
              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.email }"
              :placeholder="t('common.john_shop_com')"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
              {{ form.errors.email }}
            </p>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              {{ t('common.password') }}
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': form.errors.password }"
              placeholder="********"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
              {{ form.errors.password }}
            </p>
          </div>

          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-700">
              {{ t('common.remember_me') }}
            </label>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
            {{ form.processing ? 'Logowanie...' : t('common.sign_in') }}
          </button>
        </form>
      </div>

      <div class="text-center space-y-2">
        <div>
          <a :href="route('tenant.password.request')" class="text-sm text-blue-600 hover:text-blue-700">
            {{ t('common.i_forgot_my_password') }}
          </a>
        </div>
        <div>
          <a :href="route('tenant.shop')" class="text-sm text-gray-500 hover:text-gray-700">
            {{ t('auth.login.back_to_the_shop') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('tenant.login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
