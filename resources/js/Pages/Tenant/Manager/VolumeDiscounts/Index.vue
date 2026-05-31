<template>
  <ManagerLayout :title="t('common.volume_discounts')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('common.volume_discounts') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.volumediscounts.index.automatic_discounts_on_larger_quantities') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.volumediscounts.index.add_discount') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.loyalty.campaigns.applies_to') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.volumediscounts.index.min_quantity') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.discount') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.active') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="vd in volumeDiscounts.data" :key="vd.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div v-if="vd.product" class="font-medium text-gray-900">{{ vd.product.name }}</div>
                <div v-else-if="vd.category" class="text-gray-700">Kategoria: {{ vd.category.name }}</div>
                <div v-else class="text-gray-400 italic">{{ t('common.all_products') }}</div>
              </td>
              <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ vd.min_quantity }} szt.</td>
              <td class="px-4 py-3 text-center">
                <span class="font-bold text-blue-600">
                  {{
                    vd.discount_type === 'percent'
                      ? vd.discount_value + '%'
                      : vd.discount_value + ' ' + t('common.currency_pln')
                  }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="toggleActive(vd)"
                  :class="
                    vd.is_active
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                  "
                  class="px-2 py-0.5 rounded-full text-xs font-medium transition"
                >
                  {{ vd.is_active ? t('common.active') : t('common.inactive') }}
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(vd)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(vd)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!volumeDiscounts.data?.length">
              <td colspan="5" class="text-center py-12 text-gray-400">
                {{ t('manager.volumediscounts.index.no_volume_discounts') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_discount') : t('common.new_volume_discount') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.volumediscounts.index.product_empty_all')
            }}</label>
            <select v-model="form.product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option value="">{{ t('manager.volumediscounts.index.all_products') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.volumediscounts.index.category_empty_all')
            }}</label>
            <select v-model="form.category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option value="">{{ t('manager.volumediscounts.index.all_categories') }}</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.volumediscounts.index.minimum_quantity')
            }}</label>
            <input
              v-model="form.min_quantity"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.flashsales.index.discount_type')
              }}</label>
              <select v-model="form.discount_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="percent">{{ t('manager.flashsales.index.percentage') }}</option>
                <option value="fixed">{{ t('common.amount_pln') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.value_2') }}</label>
              <input
                v-model="form.discount_value"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="vd_active" class="h-4 w-4 text-blue-600 rounded" />
            <label for="vd_active" class="text-sm font-medium text-gray-700">{{ t('common.active') }}</label>
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ editing ? t('common.save') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  volumeDiscounts: { type: Object, required: true },
  products: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({
  product_id: '',
  category_id: '',
  min_quantity: 2,
  discount_type: 'percent',
  discount_value: '',
  is_active: true,
})

const openModal = (vd = null) => {
  editing.value = vd
  if (vd) {
    form.product_id = vd.product_id ?? ''
    form.category_id = vd.category_id ?? ''
    form.min_quantity = vd.min_quantity ?? 2
    form.discount_type = vd.discount_type ?? 'percent'
    form.discount_value = vd.discount_value ?? ''
    form.is_active = vd.is_active ?? true
  } else {
    form.reset()
    form.discount_type = 'percent'
    form.min_quantity = 2
    form.is_active = true
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.volume-discounts.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.volume-discounts.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (vd) => {
  if (!confirm(t('common.delete_this_discount'))) return
  router.delete(route('tenant.manager.volume-discounts.destroy', vd.id))
}

const toggleActive = (vd) => {
  router.put(
    route('tenant.manager.volume-discounts.update', vd.id),
    { is_active: !vd.is_active },
    { preserveScroll: true },
  )
}
</script>
