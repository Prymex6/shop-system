<template>
  <Head :title="t('common.register')" />

  <ClientLayout>
    <div class="flex items-center justify-center min-h-[70vh] px-4 py-8">
      <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-md p-8">
          <h1 class="text-2xl font-bold text-gray-900 text-center mb-6">
            {{ t('client.auth.register.create_an_account') }}
          </h1>

          <div
            v-if="$page.props.errors?.throttle"
            class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"
          >
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $page.props.errors.throttle }}
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name') }}</label>
              <input
                id="name"
                name="name"
                v-model="form.name"
                type="text"
                required
                autofocus
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('landlord.shopsearch.email')
              }}</label>
              <input
                id="email"
                name="email"
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.email }"
              />
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('client.auth.register.phone_optional')
              }}</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                placeholder="123 456 789"
                @blur="form.phone = formatPhone(form.phone)"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('common.password')
              }}</label>
              <input
                id="password"
                name="password"
                v-model="form.password"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('common.repeat_password')
              }}</label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
            </div>

            <div>
              <label for="referral_code" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('client.auth.register.referral_code_optional')
              }}</label>
              <input
                id="referral_code"
                v-model="form.referral_code"
                type="text"
                maxlength="10"
                :placeholder="t('client.auth.register.e_g_abc12345')"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent uppercase"
              />
              <p v-if="form.errors.referral_code" class="mt-1 text-sm text-red-600">{{ form.errors.referral_code }}</p>
            </div>

            <div>
              <label class="flex items-start gap-2 cursor-pointer">
                <input
                  v-model="form.terms_accepted"
                  type="checkbox"
                  required
                  class="mt-1 h-4 w-4 text-red-600 border-gray-300 rounded"
                />
                <span class="text-sm text-gray-700">
                  {{ t('common.i_accept') }}
                  <a href="/regulamin" target="_blank" class="text-red-600 hover:underline">regulamin</a>
                  i
                  <a href="/polityka-prywatnosci" target="_blank" class="text-red-600 hover:underline">{{
                    t('common.the_privacy_policy')
                  }}</a>
                  {{ t('client.auth.register.and_i_consent_to_my_personal') }}
                </span>
              </label>
              <p v-if="form.errors.terms_accepted" class="mt-1 text-sm text-red-600">
                {{ form.errors.terms_accepted }}
              </p>
            </div>

            <button
              type="submit"
              :disabled="form.processing || !form.terms_accepted"
              class="w-full py-3 theme-primary-bg hover:opacity-90 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Rejestracja...' : t('client.auth.login.sign_up') }}
            </button>
          </form>

          <!-- Social sign-in (test edition only) -->
          <div v-if="$page.props.app_version === 'test'" class="mt-6">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
              </div>
              <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">{{ t('client.auth.register.or_sign_up_with') }}</span>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
              <a
                :href="route('tenant.client.social.redirect', 'google')"
                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fa-brands fa-google mr-2 text-lg" style="color: #4285f4"></i>
                <span class="text-sm font-medium text-gray-700">Google</span>
              </a>
              <a
                :href="route('tenant.client.social.redirect', 'facebook')"
                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fa-brands fa-facebook-f mr-2 text-lg" style="color: #1877f2"></i>
                <span class="text-sm font-medium text-gray-700">Facebook</span>
              </a>
            </div>
          </div>

          <p class="mt-6 text-center text-sm text-gray-600">
            {{ t('client.auth.register.already_have_an_account') }}
            <Link :href="route('tenant.client.login')" class="text-red-600 hover:text-red-700 font-medium">
              {{ t('common.sign_in') }}
            </Link>
          </p>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({
  name: '',
  email: '',
  phone: '',
  referral_code: '',
  password: '',
  password_confirmation: '',
  terms_accepted: false,
})

const submit = () => {
  form.post(route('tenant.client.register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
