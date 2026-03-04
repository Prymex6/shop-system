<template>
  <LandlordLayout :title="t('layout.landlordlayout.shops')">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- One-time password reveal banner -->
        <div v-if="generatedPassword && showPassword" class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-yellow-800 mb-1">
                {{ t('landlord.tenants.index.the_manager_account_has_been_created') }}
              </p>
              <p class="text-xs text-yellow-700 mb-3">
                {{ t('landlord.tenants.index.email') }} <strong>{{ generatedEmail }}</strong>
                &nbsp;|&nbsp;
                {{ t('landlord.tenants.index.nbsp_nbsp_this_password_will_not') }}
              </p>
              <div class="flex items-center gap-3">
                <code
                  class="bg-yellow-100 border border-yellow-300 rounded px-3 py-1 text-base font-mono tracking-widest text-yellow-900"
                  >{{ generatedPassword }}</code
                >
                <button
                  @click="copyPassword"
                  class="text-xs px-3 py-1 bg-yellow-700 hover:bg-yellow-800 text-white rounded transition"
                >
                  {{ copied ? 'Skopiowano!' : t('common.copy') }}
                </button>
              </div>
            </div>
            <button
              @click="showPassword = false"
              class="text-yellow-500 hover:text-yellow-700 ml-4 text-lg leading-none"
            >
              &times;
            </button>
          </div>
        </div>

        <div class="flex justify-end mb-8">
          <Link
            :href="route('landlord.tenants.create')"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
          >
            {{ t('landlord.tenants.index.add_shop') }}
          </Link>
        </div>

        <div class="bg-white shadow sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.tenants.index.domain') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.modifications.form.version') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.tenants.index.licence_until') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('landlord.tenants.index.created') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="tenant in tenants.data" :key="tenant.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">
                    {{ tenant.domains[0]?.domain || t('common.none') }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="tenant.version === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{
                      tenant.version === 'test'
                        ? t('landlord.tenants.create.test')
                        : t('landlord.tenants.create.stable')
                    }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': tenant.status === 'active',
                      'bg-amber-100 text-amber-800': tenant.status === 'pending_deletion',
                      'bg-red-100 text-red-800': !['active', 'pending_deletion'].includes(tenant.status),
                    }"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{
                      tenant.status === 'active'
                        ? t('common.active')
                        : tenant.status === 'pending_deletion'
                          ? t('common.for_deletion_7_days')
                          : t('common.inactive')
                    }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span
                    v-if="tenant.license_ends_at"
                    :class="isLicenseExpired(tenant) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                  >
                    {{ formatDate(tenant.license_ends_at) }}
                    <span v-if="isLicenseExpired(tenant)" class="text-xs ml-1">{{ t('common.expired') }}</span>
                  </span>
                  <span v-else class="text-gray-400 text-xs">{{ t('landlord.tenants.create.indefinitely') }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(tenant.created_at).toLocaleDateString(locale.value) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="relative inline-block text-left" @click.stop>
                    <button
                      @click="openMenu = openMenu === tenant.id ? null : tenant.id"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 text-sm"
                    >
                      {{ t('common.actions') }}
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    <div
                      v-if="openMenu === tenant.id"
                      class="absolute right-0 z-50 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1"
                    >
                      <Link
                        :href="route('landlord.tenants.show', tenant.id)"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                          />
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                          />
                        </svg>
                        {{ t('common.details') }}
                      </Link>
                      <Link
                        :href="route('landlord.tenants.edit', tenant.id)"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                          />
                        </svg>
                        {{ t('common.edit') }}
                      </Link>
                      <button
                        @click="impersonateAndClose(tenant.id)"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                          />
                        </svg>
                        {{ t('common.sign_in_as') }}
                      </button>
                      <Link
                        v-if="tenant.status === 'suspended'"
                        :href="route('landlord.tenants.activate', tenant.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-green-600 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ t('landlord.tenants.index.activate') }}
                      </Link>
                      <Link
                        v-else
                        :href="route('landlord.tenants.suspend', tenant.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-orange-600 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                          />
                        </svg>
                        {{ t('landlord.tenants.index.deactivate') }}
                      </Link>
                      <Link
                        :href="route('landlord.tenants.clear-cache', tenant.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-yellow-600 hover:bg-gray-50"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                          />
                        </svg>
                        {{ t('landlord.tenants.index.clear_the_cache') }}
                      </Link>
                      <div class="border-t border-gray-100 my-1"></div>
                      <Link
                        v-if="tenant.status === 'pending_deletion'"
                        :href="route('landlord.tenants.cancel-deletion', tenant.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-green-600 hover:bg-gray-50"
                        @click="openMenu = null"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 10l-5 5m0 0l5 5m-5-5h12a4 4 0 000-8h-1"
                          />
                        </svg>
                        {{ t('common.cancel_deletion') }}
                      </Link>
                      <button
                        v-else
                        @click="confirmDeleteAndClose(tenant)"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                          />
                        </svg>
                        {{ t('common.delete') }}
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Delete confirmation modal -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ t('landlord.tenants.index.delete_shop') }}</h3>
            <p class="text-gray-600 mb-1">
              {{ t('common.shop') }} <strong>{{ deleteTarget.name }}</strong>
              {{ t('landlord.tenants.index.will_be_marked_for_deletion_and') }}
              <strong>{{ t('landlord.tenants.index.7_days') }}</strong
              >{{ t('landlord.tenants.index.you_can_undo_this_until_then') }}
            </p>
            <p class="text-red-600 text-sm mb-4">{{ t('landlord.tenants.index.type_the_shop_s_name_exactly') }}</p>
            <input
              v-model="confirmName"
              type="text"
              :placeholder="deleteTarget.name"
              class="w-full border border-gray-300 rounded-md px-3 py-2 mb-6 focus:ring-2 focus:ring-red-500 focus:border-transparent"
              @keydown.enter="submitDelete"
            />
            <div class="flex justify-end gap-3">
              <button
                @click="deleteTarget = null"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                @click="submitDelete"
                :disabled="confirmName !== deleteTarget.name"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                {{ t('landlord.tenants.index.mark_for_deletion') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="tenants.links.length > 3" class="mt-6 flex justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <Link
              v-for="(link, index) in tenants.links"
              :key="index"
              :href="link.url"
              :class="{
                'bg-blue-600 text-white': link.active,
                'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                'cursor-not-allowed opacity-50': !link.url,
              }"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({
  tenants: Object,
})

const page = usePage()

function impersonateAndClose(tenantId) {
  impersonate(tenantId)
  openMenu.value = null
}

function confirmDeleteAndClose(tenant) {
  confirmDelete(tenant)
  openMenu.value = null
}

function impersonate(tenantId) {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('landlord.tenants.impersonate', tenantId)
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
const showPassword = ref(true)
const copied = ref(false)
const deleteTarget = ref(null)
const openMenu = ref(null)

function closeMenu() {
  openMenu.value = null
}
onMounted(() => document.addEventListener('click', closeMenu))
onUnmounted(() => document.removeEventListener('click', closeMenu))

function formatDate(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleDateString(locale.value)
}

function isLicenseExpired(tenant) {
  if (!tenant.license_ends_at) return false
  return new Date(tenant.license_ends_at) < new Date()
}

const generatedPassword = page.props.flash?.generated_password
const generatedEmail = page.props.flash?.generated_password_email

function copyPassword() {
  navigator.clipboard.writeText(generatedPassword).then(() => {
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  })
}

const confirmName = ref('')

function confirmDelete(tenant) {
  deleteTarget.value = tenant
  confirmName.value = ''
}

function submitDelete() {
  if (!deleteTarget.value || confirmName.value !== deleteTarget.value.name) return
  router.delete(route('landlord.tenants.destroy', deleteTarget.value.id), {
    data: { confirm_name: confirmName.value },
    onFinish: () => {
      deleteTarget.value = null
    },
  })
}
</script>
