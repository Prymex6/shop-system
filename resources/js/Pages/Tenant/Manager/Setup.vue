<template>
  <Head :title="t('manager.setup.setup_wizard')" />
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

      <!-- Step 1: Shop info -->
      <div v-if="currentStep === 0" class="bg-white shadow rounded-lg p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('common.about_the_shop') }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ t('common.basic_details_customers_can_see') }}</p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.shop_name_2') }}</label>
            <input
              v-model="form.shop_name"
              type="text"
              class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
              required
              :placeholder="t('common.e_g_my_online_shop')"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
              <input
                v-model="form.shop_phone"
                type="tel"
                placeholder="123 456 789"
                @blur="form.shop_phone = formatPhone(form.shop_phone)"
                class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.email') }}</label>
              <input
                v-model="form.shop_email"
                type="email"
                class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
                :placeholder="t('common.hello_shop_com')"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.setup.shop_address') }}</label>
            <input
              v-model="form.shop_address"
              type="text"
              class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.setup.1_example_st_warsaw')"
            />
          </div>
        </div>

        <div class="flex justify-end mt-8">
          <button
            @click="saveStep1"
            type="button"
            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors"
          >
            {{ t('manager.setup.next') }}
          </button>
        </div>
      </div>

      <!-- Step 2: Opening hours -->
      <div v-if="currentStep === 1" class="bg-white shadow rounded-lg p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('manager.setup.opening_hours') }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ t('manager.setup.when_is_the_shop_open') }}</p>

        <div class="space-y-3">
          <div v-for="(day, key) in hours" :key="key" class="flex items-center gap-4">
            <div class="w-32 flex items-center gap-2">
              <input type="checkbox" v-model="day.enabled" class="w-4 h-4 text-blue-600" />
              <span class="text-sm font-medium">{{ dayNames[key] }}</span>
            </div>
            <div v-if="day.enabled" class="flex items-center gap-2">
              <input v-model="day.open" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
              <span class="text-gray-400">–</span>
              <input v-model="day.close" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <span v-else class="text-sm text-gray-400">{{ t('common.closed') }}</span>
          </div>
        </div>

        <div class="flex justify-between mt-8">
          <button
            @click="currentStep--"
            type="button"
            class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
          >
            {{ t('manager.setup.back') }}
          </button>
          <button
            @click="saveStep2"
            type="button"
            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors"
          >
            {{ t('manager.setup.next') }}
          </button>
        </div>
      </div>

      <!-- Step 3: First category & product -->
      <div v-if="currentStep === 2" class="bg-white shadow rounded-lg p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('manager.setup.first_product') }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ t('manager.setup.optionally_add_a_first_category_and') }}</p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.setup.category_name') }}</label>
            <input
              v-model="form.category_name"
              type="text"
              class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.setup.e_g_electronics_clothing_accessories')"
            />
          </div>
          <div v-if="form.category_name" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.product_name') }}</label>
              <input
                v-model="form.product_name"
                type="text"
                class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
                :placeholder="t('manager.setup.e_g_wireless_headphones')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.price_pln') }}</label>
              <input
                v-model="form.product_price"
                type="number"
                min="0"
                step="0.01"
                class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-blue-500"
                placeholder="np. 25.00"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-between mt-8">
          <button
            @click="currentStep--"
            type="button"
            class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
          >
            {{ t('manager.setup.back') }}
          </button>
          <button
            @click="complete"
            type="button"
            class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-colors"
          >
            {{ t('manager.setup.finish_setting_up') }}
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
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
})

const currentStep = ref(0)
const steps = ['Sklep', 'Godziny', 'Menu']

const form = reactive({
  shop_name: props.settings.shop_name ?? '',
  shop_phone: props.settings.shop_phone ?? '',
  shop_email: props.settings.shop_email ?? '',
  shop_address: props.settings.shop_address ?? '',
  category_name: '',
  product_name: '',
  product_price: '',
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

function saveStep1() {
  if (!form.shop_name.trim()) return alert(t('common.enter_the_shop_s_name'))
  router.post(
    route('tenant.manager.setup.store'),
    {
      step: 'shop',
      shop_name: form.shop_name,
      shop_phone: form.shop_phone,
      shop_email: form.shop_email,
      shop_address: form.shop_address,
    },
    {
      preserveState: true,
      onSuccess: () => {
        currentStep.value = 1
      },
    },
  )
}

function saveStep2() {
  currentStep.value = 2
}

function complete() {
  router.post(route('tenant.manager.setup.store'), {
    step: 'complete',
    category_name: form.category_name,
    product_name: form.product_name,
    product_price: form.product_price,
  })
}
</script>
