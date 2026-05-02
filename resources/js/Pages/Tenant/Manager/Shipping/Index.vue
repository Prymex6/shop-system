<template>
  <ManagerLayout :title="t('common.delivery_methods')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.delivery_methods') }}</h1>
        <div class="flex gap-2">
          <Link
            :href="route('tenant.manager.shipping.zones.index')"
            class="border border-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition"
          >
            {{ t('common.delivery_zones') }}
          </Link>
          <button
            @click="showForm = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
          >
            {{ t('manager.shipping.index.new_method') }}
          </button>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg divide-y divide-gray-50">
        <div v-for="method in methods" :key="method.id" class="px-5 py-4 flex items-center gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-semibold text-gray-900">{{ method.name }}</span>
              <span
                class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="{
                  'bg-blue-100 text-blue-700': method.type === 'standard',
                  'bg-orange-100 text-orange-700': method.type === 'express',
                  'bg-green-100 text-green-700': method.type === 'pickup',
                  'bg-purple-100 text-purple-700': method.type === 'digital',
                }"
              >
                {{ typeLabel(method.type) }}
              </span>
              <span
                v-if="method.carrier"
                class="text-xs px-2 py-0.5 rounded-full font-medium bg-indigo-100 text-indigo-700"
              >
                {{ carrierLabel(method.carrier) }}
              </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
              {{ formatPrice(method.price) }}
              <span v-if="method.free_from">
                · {{ t('manager.shipping.index.free_from_a', { a: formatPrice(method.free_from) }) }}</span
              >
              · {{ t('manager.shipping.index.a_b_days', { a: method.delivery_days_min, b: method.delivery_days_max }) }}
            </p>
          </div>
          <button
            @click="toggleMethod(method)"
            class="text-xs font-semibold px-2 py-0.5 rounded-full"
            :class="
              method.is_active
                ? 'bg-green-100 text-green-700 hover:bg-green-200'
                : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
            "
          >
            {{ method.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
          </button>
          <button @click="editMethod(method)" class="text-sm text-blue-600 hover:text-blue-900 font-medium px-2 py-1">
            {{ t('common.edit') }}
          </button>
          <button @click="deleteMethod(method)" class="text-sm text-red-500 hover:text-red-700 font-medium px-2 py-1">
            {{ t('common.delete') }}
          </button>
        </div>
        <div v-if="!methods.length" class="text-center py-12 text-gray-400 text-sm">
          {{ t('manager.shipping.index.no_delivery_methods_yet_add_the') }}
        </div>
      </div>

      <!-- Form Modal -->
      <div
        v-if="showForm"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        @click.self="closeForm"
      >
        <div class="bg-white shadow rounded-lg w-full max-w-lg p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-5">
            {{ editing ? t('common.edit_method') : t('common.new_delivery_method') }}
          </h2>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
                <input
                  v-model="form.name"
                  required
                  class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.fraud.index.type') }}</label>
                <select v-model="form.type" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                  <option value="standard">{{ t('client.checkout.standard') }}</option>
                  <option value="express">{{ t('client.checkout.express') }}</option>
                  <option value="pickup">{{ t('manager.shipping.index.collection_in_person') }}</option>
                  <option value="digital">{{ t('manager.shipping.index.digital_automatic') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.shipping.index.carrier')
                }}</label>
                <select v-model="form.carrier" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                  <option :value="null">{{ t('manager.shipping.index.none_shop_s_own_delivery') }}</option>
                  <option value="inpost">{{ t('manager.shipping.index.inpost_parcel_locker') }}</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                  {{ t('manager.shipping.index.name_a_carrier_and_the_customer') }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.price_pln') }}</label>
                <input
                  v-model="form.price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.shipping.index.free_from_pln')
                }}</label>
                <input
                  v-model="form.free_from"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                  :placeholder="t('manager.collections.index.no')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.shipping.index.time_min_days')
                }}</label>
                <input
                  v-model="form.delivery_days_min"
                  type="number"
                  min="0"
                  class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.shipping.index.time_max_days')
                }}</label>
                <input
                  v-model="form.delivery_days_max"
                  type="number"
                  min="0"
                  class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                />
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_active" class="accent-indigo-600" />
              {{ t('landlord.modifications.form.active') }}
            </label>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeForm" class="px-4 py-2 text-sm text-gray-600 font-medium">
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition"
              >
                {{ editing ? t('common.save') : t('common.add') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  methods: { type: Array, default: () => [] },
})

const showForm = ref(false)
const editing = ref(null)
const form = reactive({
  name: '',
  type: 'standard',
  carrier: null,
  price: 0,
  free_from: '',
  delivery_days_min: 1,
  delivery_days_max: 3,
  is_active: true,
})

function carrierLabel(carrier) {
  return { inpost: t('manager.shipping.index.inpost_parcel_locker') }[carrier] ?? carrier
}

function typeLabel(type) {
  return (
    {
      standard: t('client.checkout.standard'),
      express: t('client.checkout.express'),
      pickup: t('common.collection'),
      digital: t('client.checkout.digital'),
    }[type] ?? type
  )
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

function editMethod(method) {
  editing.value = method
  Object.assign(form, method)
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editing.value = null
  Object.assign(form, {
    name: '',
    type: 'standard',
    carrier: null,
    price: 0,
    free_from: '',
    delivery_days_min: 1,
    delivery_days_max: 3,
    is_active: true,
  })
}

function submitForm() {
  if (editing.value) {
    router.put(route('tenant.manager.shipping.methods.update', editing.value.id), form, { onSuccess: closeForm })
  } else {
    router.post(route('tenant.manager.shipping.methods.store'), form, { onSuccess: closeForm })
  }
}

function toggleMethod(method) {
  router.patch(route('tenant.manager.shipping.methods.toggle', method.id), {}, { preserveScroll: true })
}

function deleteMethod(method) {
  if (!confirm(t('manager.shipping.index.delete_the_method_a', { a: method.name }))) return
  router.delete(route('tenant.manager.shipping.methods.destroy', method.id))
}
</script>
