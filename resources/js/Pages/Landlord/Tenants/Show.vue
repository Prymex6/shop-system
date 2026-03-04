<template>
  <LandlordLayout :title="`Sklep: ${tenant.name}`">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <Link :href="route('landlord.tenants.index')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">{{
            t('landlord.tenants.show.larr_shops')
          }}</Link>
          <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ tenant.name }}</h1>
          <p class="text-sm text-gray-500">{{ tenant.domains[0]?.domain || `${tenant.subdomain}.${baseDomain}` }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span
            :class="{
              'bg-green-100 text-green-800': tenant.status === 'active',
              'bg-amber-100 text-amber-800': tenant.status === 'pending_deletion',
              'bg-red-100 text-red-800': !['active', 'pending_deletion'].includes(tenant.status),
            }"
            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
          >
            {{
              tenant.status === 'active'
                ? t('common.active')
                : tenant.status === 'pending_deletion'
                  ? t('common.for_deletion_7_days')
                  : t('common.inactive')
            }}
          </span>
          <span
            :class="tenant.version === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
          >
            {{ tenant.version === 'test' ? t('landlord.tenants.create.test') : t('landlord.tenants.create.stable') }}
          </span>
        </div>
      </div>

      <!-- Dane sklepu -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ t('common.shop_details') }}</h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-6">
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
              {{ t('landlord.tenants.create.subdomain') }}
            </p>
            <p class="text-sm text-gray-900">{{ tenant.subdomain }}.{{ baseDomain }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
              {{ t('landlord.tenants.show.custom_domain') }}
            </p>
            <p class="text-sm text-gray-900">{{ tenant.custom_domain || '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Plan</p>
            <p class="text-sm text-gray-900">{{ tenant.plan?.name || t('common.no_plan') }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
              {{ t('common.licence_valid_until') }}
            </p>
            <p class="text-sm" :class="isLicenseExpired ? 'text-red-600 font-semibold' : 'text-gray-900'">
              {{
                tenant.license_ends_at ? formatDate(tenant.license_ends_at) : t('landlord.tenants.create.indefinitely')
              }}
              <span v-if="isLicenseExpired" class="text-xs ml-1">{{ t('common.expired') }}</span>
            </p>
          </div>
          <div v-if="tenant.trial_ends_at">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
              {{ t('landlord.tenants.show.trial_until') }}
            </p>
            <p class="text-sm text-gray-900">{{ formatDate(tenant.trial_ends_at) }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
              {{ t('landlord.tenants.index.created') }}
            </p>
            <p class="text-sm text-gray-900">{{ formatDate(tenant.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Akcje -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ t('common.actions') }}</h2>
        </div>
        <div class="p-6 flex flex-wrap gap-3">
          <Link
            :href="route('landlord.tenants.edit', tenant.id)"
            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            {{ t('common.edit') }}
          </Link>
          <button
            @click="impersonate"
            class="px-4 py-2 border border-purple-300 text-sm font-medium rounded-lg text-purple-700 bg-white hover:bg-purple-50 transition-colors"
          >
            {{ t('common.sign_in_as') }}
          </button>
          <Link
            v-if="tenant.status === 'suspended'"
            :href="route('landlord.tenants.activate', tenant.id)"
            method="post"
            as="button"
            class="px-4 py-2 border border-green-300 text-sm font-medium rounded-lg text-green-700 bg-white hover:bg-green-50 transition-colors"
          >
            {{ t('landlord.tenants.index.activate') }}
          </Link>
          <Link
            v-else
            :href="route('landlord.tenants.suspend', tenant.id)"
            method="post"
            as="button"
            class="px-4 py-2 border border-orange-300 text-sm font-medium rounded-lg text-orange-700 bg-white hover:bg-orange-50 transition-colors"
          >
            {{ t('landlord.tenants.index.deactivate') }}
          </Link>
          <Link
            :href="route('landlord.tenants.clear-cache', tenant.id)"
            method="post"
            as="button"
            class="px-4 py-2 border border-yellow-300 text-sm font-medium rounded-lg text-yellow-700 bg-white hover:bg-yellow-50 transition-colors"
          >
            {{ t('landlord.tenants.index.clear_the_cache') }}
          </Link>
          <Link
            v-if="tenant.status === 'pending_deletion'"
            :href="route('landlord.tenants.cancel-deletion', tenant.id)"
            method="post"
            as="button"
            class="px-4 py-2 border border-green-300 text-sm font-medium rounded-lg text-green-700 bg-white hover:bg-green-50 transition-colors"
          >
            {{ t('common.cancel_deletion') }}
          </Link>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  tenant: Object,
})

const baseDomain = window.location.hostname

const formatDate = (dt) => {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString(locale.value)
}

const isLicenseExpired = computed(() => {
  if (!props.tenant.license_ends_at) return false
  return new Date(props.tenant.license_ends_at) < new Date()
})

function impersonate() {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('landlord.tenants.impersonate', props.tenant.id)
  form.target = '_blank'
  const input = document.createElement('input')
  input.type = 'hidden'
  input.name = '_token'
  input.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  form.appendChild(input)
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}
</script>
