<template>
  <ManagerLayout :title="t('manager.purchaseorders.create.new_purchase_order')">
    <div class="max-w-3xl space-y-6">
      <div class="flex items-center gap-3">
        <Link
          :href="route('tenant.manager.purchase-orders.index')"
          class="text-gray-400 hover:text-gray-700 transition"
        >
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.purchaseorders.create.new_purchase_order') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.purchaseorders.create.create_a_purchase_order') }}</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-6">
        <!-- Supplier -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{
            t('manager.purchaseorders.create.supplier')
          }}</label>
          <select
            v-model="form.supplier_id"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('manager.purchaseorders.create.choose_a_supplier') }}</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">
              {{ s.company_name || s.name }}
            </option>
          </select>
          <p v-if="form.errors.supplier_id" class="text-red-600 text-sm mt-1">{{ form.errors.supplier_id }}</p>
        </div>

        <!-- Expected date + notes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.purchaseorders.create.expected_delivery_date')
            }}</label>
            <input
              v-model="form.expected_at"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.purchaseorders.create.notes')
            }}</label>
            <input
              v-model="form.notes"
              type="text"
              :placeholder="t('manager.purchaseorders.create.notes_optional')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Items -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-gray-700">{{
              t('manager.purchaseorders.create.order_items')
            }}</label>
            <button type="button" @click="addItem" class="text-sm font-medium text-blue-600 hover:text-blue-800">
              {{ t('manager.purchaseorders.create.add_item') }}
            </button>
          </div>

          <div class="space-y-3">
            <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 items-center">
              <div class="col-span-5">
                <select v-model="item.product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                  <option value="">{{ t('manager.purchaseorders.create.product') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="col-span-3">
                <input
                  v-model.number="item.quantity"
                  type="number"
                  min="1"
                  :placeholder="t('common.quantity')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
              <div class="col-span-3">
                <input
                  v-model.number="item.unit_cost"
                  type="number"
                  min="0"
                  step="0.01"
                  :placeholder="t('common.unit_price')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                />
              </div>
              <div class="col-span-1 flex justify-center">
                <button
                  type="button"
                  @click="removeItem(i)"
                  class="text-red-400 hover:text-red-600 text-lg leading-none"
                >
                  ✕
                </button>
              </div>
            </div>
            <p v-if="form.errors.items" class="text-red-600 text-sm">{{ form.errors.items }}</p>
          </div>

          <!-- Total -->
          <div v-if="form.items.length" class="mt-4 text-right text-sm font-semibold text-gray-900">
            {{ t('common.total_a', { a: formatPrice(total) }) }}
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
          <Link
            :href="route('tenant.manager.purchase-orders.index')"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
          >
            {{ t('common.cancel') }}
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm font-semibold"
          >
            {{ form.processing ? 'Tworzenie…' : t('common.create_order') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  suppliers: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
})

const form = useForm({
  supplier_id: '',
  notes: '',
  expected_at: '',
  items: [{ product_id: '', quantity: 1, unit_cost: 0, variant_id: null }],
})

const total = computed(() => form.items.reduce((sum, i) => sum + (i.quantity || 0) * (i.unit_cost || 0), 0))

function addItem() {
  form.items.push({ product_id: '', quantity: 1, unit_cost: 0, variant_id: null })
}

function removeItem(i) {
  if (form.items.length > 1) form.items.splice(i, 1)
}

function submit() {
  form.post(route('tenant.manager.purchase-orders.store'))
}

const formatPrice = (v) => new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(v ?? 0)
</script>
