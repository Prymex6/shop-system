<template>
  <ManagerLayout :title="t('manager.tax.index.vat_rates')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.tax.index.vat_rates') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.tax.index.manage_vat_rates') }}</p>
        </div>
        <button
          @click="openAdd"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700"
        >
          {{ t('manager.tax.index.add_rate') }}
        </button>
      </div>

      <!-- Tax Rates Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.tax.index.rate') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.country') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.default') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('landlord.modifications.form.active') }}
              </th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="taxRates.length === 0">
              <td colspan="6" class="text-center py-10 text-gray-400">
                {{ t('manager.tax.index.no_vat_rates_yet_add_the') }}
              </td>
            </tr>
            <tr v-for="rate in taxRates" :key="rate.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ rate.name }}</td>
              <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ rate.rate }}%</td>
              <td class="px-4 py-3 text-center text-gray-500">{{ rate.country ?? t('common.all') }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  v-if="rate.is_default"
                  class="inline-flex px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold"
                  >{{ t('common.default') }}</span
                >
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="rate.is_active ? 'text-green-600' : 'text-red-400'" class="text-xs font-semibold">
                  {{ rate.is_active ? 'Tak' : t('manager.collections.index.no') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-3">
                  <button @click="openEdit(rate)" class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(rate)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Add / Edit Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showModal = false"
      >
        <div class="bg-white shadow rounded-lg w-full max-w-md p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-4">
            {{ editing ? t('common.edit_vat_rate') : t('common.new_vat_rate') }}
          </h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.tax.index.rate_name') }}</label>
              <input
                v-model="form.name"
                type="text"
                :placeholder="t('manager.tax.index.e_g_vat_23_vat_0')"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
              />
              <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.tax.index.rate_2') }}</label>
              <input
                v-model="form.rate"
                type="number"
                min="0"
                max="100"
                step="0.01"
                placeholder="23"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
              />
              <p v-if="errors.rate" class="text-red-500 text-xs mt-1">{{ errors.rate }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.tax.index.country_iso_code_optional')
              }}</label>
              <input
                v-model="form.country"
                type="text"
                maxlength="2"
                placeholder="PL"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 uppercase"
              />
              <p class="text-gray-400 text-xs mt-1">{{ t('manager.tax.index.leave_empty_to_apply_everywhere') }}</p>
            </div>

            <div class="flex gap-6">
              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input v-model="form.is_default" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                {{ t('manager.tax.index.default_rate') }}
              </label>
              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                {{ t('landlord.modifications.form.active') }}
              </label>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="save"
              class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold text-sm hover:bg-blue-700"
            >
              {{ editing ? t('common.save_changes') : t('common.add_rate') }}
            </button>
            <button
              @click="showModal = false"
              class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold text-sm hover:bg-gray-200"
            >
              {{ t('common.cancel') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({ taxRates: Array })

const showModal = ref(false)
const editing = ref(null)
const errors = ref({})

const form = reactive({
  name: '',
  rate: '',
  country: '',
  is_default: false,
  is_active: true,
})

function openAdd() {
  editing.value = null
  form.name = ''
  form.rate = ''
  form.country = ''
  form.is_default = false
  form.is_active = true
  errors.value = {}
  showModal.value = true
}

function openEdit(rate) {
  editing.value = rate
  form.name = rate.name
  form.rate = rate.rate
  form.country = rate.country ?? ''
  form.is_default = rate.is_default
  form.is_active = rate.is_active
  errors.value = {}
  showModal.value = true
}

function save() {
  const url = editing.value ? route('tenant.manager.tax.update', editing.value.id) : route('tenant.manager.tax.store')

  const method = editing.value ? 'put' : 'post'

  router[method](
    url,
    { ...form },
    {
      onSuccess: () => {
        showModal.value = false
      },
      onError: (e) => {
        errors.value = e
      },
    },
  )
}

function destroy(rate) {
  if (!confirm(t('manager.tax.index.delete_the_rate_a', { a: rate.name }))) return
  router.delete(route('tenant.manager.tax.destroy', rate.id))
}
</script>
