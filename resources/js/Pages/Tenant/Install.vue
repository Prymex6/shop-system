<template>
  <Head :title="t('pages.tenant.install.shop_setup')" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <!-- Logo / Header -->
      <div class="text-center mb-8">
        <img src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
        <h1 class="text-3xl font-bold text-gray-900">Witaj w {{ $page.props.app_name }}!</h1>
        <p class="text-gray-500 mt-1">{{ t('common.set_your_shop_up_in_a') }}</p>
      </div>

      <!-- Progress -->
      <div class="flex items-center justify-between mb-8 px-4">
        <div v-for="(s, i) in steps" :key="i" class="flex items-center">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
            :class="
              currentStep > i
                ? 'bg-green-500 text-white'
                : currentStep === i
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 text-gray-500'
            "
          >
            <span v-if="currentStep > i"><i class="fa-solid fa-check"></i></span>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span
            class="hidden md:block ml-2 text-sm font-medium"
            :class="currentStep >= i ? 'text-gray-700' : 'text-gray-400'"
            >{{ s }}</span
          >
          <div
            v-if="i < steps.length - 1"
            class="flex-1 h-0.5 mx-3 md:mx-4"
            :class="currentStep > i ? 'bg-green-400' : 'bg-gray-200'"
            style="min-width: 20px"
          ></div>
        </div>
      </div>

      <!-- Step 0: Account (create new OR set password for existing) -->
      <div v-if="currentStep === 0" class="bg-white rounded-2xl shadow-lg p-8">
        <template v-if="existingManagerEmail">
          <!-- Manager already exists — send them a real password-reset link -->
          <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('pages.tenant.install.finish_setting_up') }}</h2>
          <p v-if="!resetLinkSent" class="text-sm text-gray-500 mb-6">
            {{ t('pages.tenant.install.an_administrator_account_already_exists_at') }}
            <strong class="text-gray-700">{{ existingManagerEmail }}</strong
            >{{ t('pages.tenant.install.we_will_send_a_link_to') }}
          </p>
          <div v-else class="rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm p-4 mb-6">
            {{ t('pages.tenant.install.a_link_to_set_the_password') }} <strong>{{ existingManagerEmail }}</strong
            >{{ t('pages.tenant.install.check_your_inbox_set_a_password') }}
          </div>
        </template>

        <template v-else>
          <!-- Fresh install — create account -->
          <h2 class="text-xl font-bold text-gray-900 mb-1">
            {{ t('pages.tenant.install.create_an_administrator_account') }}
          </h2>
          <p class="text-sm text-gray-500 mb-6">{{ t('pages.tenant.install.this_account_will_be_the_one') }}</p>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-400': errors.name }"
                placeholder="Jan Kowalski"
                required
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('pages.tenant.install.email_address')
              }}</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-400': errors.email }"
                :placeholder="t('common.john_shop_com')"
                required
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('pages.tenant.install.password')
              }}</label>
              <input
                v-model="form.password"
                type="password"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-400': errors.password }"
                :placeholder="t('pages.tenant.install.at_least_8_characters')"
                required
              />
              <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('pages.tenant.install.repeat_password')
              }}</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('common.repeat_password')"
                required
              />
            </div>
          </div>
        </template>

        <div v-if="!(existingManagerEmail && resetLinkSent)" class="flex justify-end mt-8">
          <button
            @click="submitAccount"
            type="button"
            :disabled="processing"
            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors"
          >
            {{
              processing
                ? t('common.sending_2')
                : existingManagerEmail
                  ? t('common.send_a_link_to_set_the')
                  : t('manager.setup.next')
            }}
          </button>
        </div>
      </div>

      <!-- Step 1: Shop info -->
      <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('pages.tenant.install.about_the_shop') }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ t('common.basic_details_customers_can_see') }}</p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.shop_name_2') }}</label>
            <input
              v-model="form.shop_name"
              type="text"
              class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-400': errors.shop_name }"
              :placeholder="t('common.e_g_my_online_shop')"
              required
            />
            <p v-if="errors.shop_name" class="mt-1 text-sm text-red-600">{{ errors.shop_name }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
              <input
                v-model="form.shop_phone"
                type="tel"
                placeholder="123 456 789"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @blur="form.shop_phone = formatPhone(form.shop_phone)"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('pages.tenant.install.contact_email')
              }}</label>
              <input
                v-model="form.shop_email"
                type="email"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('common.hello_shop_com')"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('pages.tenant.install.shop_address')
            }}</label>
            <input
              v-model="form.shop_address"
              type="text"
              class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-400': errors.shop_address }"
              :placeholder="t('pages.tenant.install.1_example_st_00_001_warsaw')"
              required
            />
            <p v-if="errors.shop_address" class="mt-1 text-sm text-red-600">{{ errors.shop_address }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ t('pages.tenant.install.required_by_law_in_the_terms') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('pages.tenant.install.vat_number_optional')
            }}</label>
            <input
              v-model="form.shop_nip"
              type="text"
              class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="np. 1234567890"
            />
          </div>
        </div>

        <div class="flex justify-end mt-8">
          <button
            @click="submitShop"
            type="button"
            :disabled="processing"
            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors"
          >
            {{ processing ? 'Zapisywanie...' : t('manager.setup.next') }}
          </button>
        </div>
      </div>

      <p class="text-center mt-4 text-sm text-gray-400">
        {{ t('common.you_can_skip_any_step_everything') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  step: { type: Number, default: 0 },
  existing_manager_email: { type: String, default: null },
})

const page = usePage()
const currentStep = ref(props.step)
const processing = ref(false)
const errors = computed(() => page.props.errors ?? {})
const existingManagerEmail = props.existing_manager_email
const resetLinkSent = ref(false)

const steps = ['Konto', 'Sklep']

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  shop_name: '',
  shop_phone: '',
  shop_email: '',
  shop_nip: '',
  shop_address: '',
  logo_url: '',
  shop_description: '',
})

const dayNames = {
  monday: t('common.mon'),
  tuesday: t('pages.tenant.install.tue'),
  wednesday: t('common.wed'),
  thursday: t('pages.tenant.install.thu'),
  friday: t('common.fri'),
  saturday: t('pages.tenant.install.sat'),
  sunday: t('pages.tenant.install.sun'),
}

const hours = reactive({
  monday: { enabled: true, open: '10:00', close: '22:00' },
  tuesday: { enabled: true, open: '10:00', close: '22:00' },
  wednesday: { enabled: true, open: '10:00', close: '22:00' },
  thursday: { enabled: true, open: '10:00', close: '22:00' },
  friday: { enabled: true, open: '10:00', close: '23:00' },
  saturday: { enabled: true, open: '11:00', close: '23:00' },
  sunday: { enabled: false, open: '12:00', close: '21:00' },
})

function submitAccount() {
  processing.value = true

  if (existingManagerEmail) {
    router.post(
      route('tenant.install.account'),
      {},
      {
        onFinish: () => {
          processing.value = false
        },
        onSuccess: () => {
          resetLinkSent.value = true
        },
      },
    )
    return
  }

  router.post(
    route('tenant.install.account'),
    {
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    },
    {
      onFinish: () => {
        processing.value = false
      },
      onSuccess: () => {
        currentStep.value = 1
      },
    },
  )
}

function submitShop() {
  processing.value = true
  router.post(
    route('tenant.install.shop'),
    {
      shop_name: form.shop_name,
      shop_phone: form.shop_phone,
      shop_email: form.shop_email,
      shop_nip: form.shop_nip,
      shop_address: form.shop_address,
    },
    {
      onFinish: () => {
        processing.value = false
      },
      onSuccess: () => {},
    },
  )
}

function submitBranding() {
  processing.value = true
  router.post(
    route('tenant.install.branding'),
    {
      logo_url: form.logo_url,
      shop_description: form.shop_description,
    },
    {
      onFinish: () => {
        processing.value = false
      },
    },
  )
}

function submitComplete() {
  processing.value = true
  router.post(
    route('tenant.install.complete'),
    {},
    {
      onFinish: () => {
        processing.value = false
      },
    },
  )
}
</script>
