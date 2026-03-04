<template>
  <LandlordLayout :title="t('landlord.tenants.create.add_shop')">
    <div class="max-w-3xl mx-auto space-y-6">
      <p class="text-sm text-gray-500 -mt-4">{{ t('landlord.tenants.create.fill_in_the_new_shop_s') }}</p>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Dane sklepu -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ t('common.shop_details') }}</h2>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >{{ t('common.shop_name') }} <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.name"
                type="text"
                required
                :placeholder="t('common.e_g_my_online_shop')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >{{ t('landlord.tenants.create.subdomain') }} <span class="text-red-500">*</span></label
              >
              <div class="flex rounded-lg shadow-sm">
                <input
                  v-model="form.subdomain"
                  type="text"
                  required
                  pattern="[a-z0-9\-]+"
                  :placeholder="t('landlord.tenants.create.my_shop')"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <span
                  class="inline-flex items-center px-4 border border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-gray-500 text-sm font-medium"
                >
                  .{{ baseDomain }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-400">
                {{ t('landlord.tenants.create.lower_case_letters_digits_and_hyphens') }}
              </p>
              <p v-if="form.errors.subdomain" class="mt-1 text-xs text-red-600">{{ form.errors.subdomain }}</p>
            </div>
          </div>
        </div>

        <!-- Konfiguracja -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
              {{ t('landlord.tenants.create.system_configuration') }}
            </h2>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >{{ t('common.status') }} <span class="text-red-500">*</span></label
                >
                <select
                  v-model="form.status"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="active">{{ t('common.active') }}</option>
                  <option value="suspended">{{ t('common.inactive') }}</option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >{{ t('landlord.tenants.create.system_edition') }} <span class="text-red-500">*</span></label
                >
                <select
                  v-model="form.version"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="stable">{{ t('landlord.tenants.create.stable') }}</option>
                  <option value="test">{{ t('landlord.tenants.create.test') }}</option>
                </select>
                <p v-if="form.errors.version" class="mt-1 text-xs text-red-600">{{ form.errors.version }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.licence_valid_until') }}</label>
              <input
                v-model="form.license_ends_at"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <div class="flex gap-2 mt-2">
                <button
                  type="button"
                  @click="setLicense(21)"
                  class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors"
                >
                  3 tygodnie
                </button>
                <button
                  type="button"
                  @click="setLicense(180)"
                  class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors"
                >
                  {{ t('landlord.tenants.create.6_months') }}
                </button>
                <button
                  type="button"
                  @click="setLicense(365)"
                  class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors"
                >
                  {{ t('landlord.tenants.create.1_year') }}
                </button>
                <button
                  type="button"
                  @click="form.license_ends_at = ''"
                  class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors"
                >
                  {{ t('landlord.tenants.create.indefinitely') }}
                </button>
              </div>
              <p class="mt-1 text-xs text-gray-400">
                {{ t('landlord.tenants.create.three_weeks_by_default_the_trial') }}
              </p>
              <p v-if="form.errors.license_ends_at" class="mt-1 text-xs text-red-600">
                {{ form.errors.license_ends_at }}
              </p>
            </div>
          </div>
        </div>

        <!-- Konto managera -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
              {{ t('landlord.tenants.create.manager_account') }}
              <span class="text-xs font-normal text-gray-400 normal-case ml-1">(opcjonalne)</span>
            </h2>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name') }}</label>
                <input
                  v-model="form.owner_name"
                  type="text"
                  placeholder="Jan Kowalski"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.email') }}</label>
                <input
                  v-model="form.owner_email"
                  type="email"
                  :placeholder="t('landlord.tenants.create.manager_shop_com')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p v-if="form.errors.owner_email" class="mt-1 text-xs text-red-600">{{ form.errors.owner_email }}</p>
              </div>
            </div>
            <p class="text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
              {{ t('landlord.tenants.create.give_an_email_address_and_a') }}
            </p>
          </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-between pt-2">
          <Link
            :href="route('landlord.tenants.index')"
            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            {{ t('common.cancel') }}
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Tworzenie...' : t('common.create_shop') }}
          </button>
        </div>
      </form>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const baseDomain = window.location.hostname

const defaultLicenseDate = () => {
  const d = new Date()
  d.setDate(d.getDate() + 21)
  return d.toISOString().split('T')[0]
}

const form = useForm({
  name: '',
  subdomain: '',
  status: 'active',
  version: 'stable',
  license_ends_at: defaultLicenseDate(),
  owner_name: '',
  owner_email: '',
})

function setLicense(days) {
  const d = new Date()
  d.setDate(d.getDate() + days)
  form.license_ends_at = d.toISOString().split('T')[0]
}

const submit = () => {
  form.post(route('landlord.tenants.store'))
}
</script>
