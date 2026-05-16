<template>
  <Head :title="t('common.password_reset')" />

  <ClientLayout>
    <div class="flex items-center justify-center min-h-[70vh] px-4 py-8">
      <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-md p-8">
          <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">
            {{ t('client.auth.forgotpassword.password_reset') }}
          </h1>
          <p class="text-sm text-gray-600 text-center mb-6">{{ t('common.give_us_your_email_address_and') }}</p>

          <div
            v-if="$page.props.errors?.throttle"
            class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"
          >
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $page.props.errors.throttle }}
          </div>

          <div
            v-if="$page.props.flash?.success"
            class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"
          >
            <i class="fa-solid fa-circle-check mr-2"></i>{{ $page.props.flash.success }}
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('common.email_address')
              }}</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': form.errors.email }"
                :placeholder="t('client.auth.forgotpassword.john_example_com')"
              />
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-3 theme-primary-bg hover:opacity-90 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
            >
              <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
              {{ form.processing ? t('common.sending_2') : t('common.send_the_reset_link') }}
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-gray-600">
            <a :href="route('tenant.client.login')" class="text-red-600 hover:text-red-700">{{
              t('common.back_to_sign_in')
            }}</a>
          </p>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({ email: '' })

const submit = () => {
  form.post(route('tenant.client.password.email'))
}
</script>
