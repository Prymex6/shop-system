<template>
  <ManagerLayout :title="`Warianty — ${product.name}`">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <Link
            :href="route('tenant.manager.products.edit', product.id)"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            >&larr; {{ product.name }}</Link
          >
          <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ t('common.product_variants') }}</h1>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.products.variants.new_variant') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('layout.managerlayout.attributes') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.price') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.stock') }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.active') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="v in variants" :key="v.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-700">
                <span
                  v-for="(val, key) in v.attributes"
                  :key="key"
                  class="inline-block mr-2 text-xs bg-gray-100 rounded px-1.5 py-0.5"
                  >{{ key }}: {{ val }}</span
                >
              </td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ v.sku || '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ v.price ? formatPrice(v.price) : '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ v.stock_quantity }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="v.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ v.is_active ? 'Tak' : t('manager.collections.index.no') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(v)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(v)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!variants.length">
              <td colspan="6" class="text-center py-12 text-gray-400">
                {{ t('manager.products.variants.no_variants_this_product_is_sold') }}
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
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_variant') : t('common.new_variant') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div v-if="attributes.length">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{
              t('layout.managerlayout.attributes')
            }}</label>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="attr in attributes" :key="attr.id">
                <label class="block text-xs text-gray-500 mb-1">{{ attr.name }}</label>
                <select
                  v-model="form.attributes[attr.name]"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                >
                  <option value="">—</option>
                  <option v-for="val in attr.values" :key="val.id" :value="val.value">{{ val.value }}</option>
                </select>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
            {{ t('manager.products.variants.no_attributes_defined_colour_size_and') }}
            <Link
              :href="route('tenant.manager.attributes.index')"
              class="text-blue-600 hover:text-blue-800 font-medium"
              >{{ t('manager.products.variants.add_them_here') }}</Link
            >.
          </p>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
              <input
                v-model="form.sku"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.form.stock_level')
              }}</label>
              <input
                v-model.number="form.stock_quantity"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.variants.price_optional_overrides_the_product_s')
              }}</label>
              <input
                v-model.number="form.price"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.products.variants.price_before_the_reduction')
              }}</label>
              <input
                v-model.number="form.compare_price"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="variant_active" class="h-4 w-4 text-blue-600 rounded" />
            <label for="variant_active" class="text-sm font-medium text-gray-700">{{ t('common.active') }}</label>
          </div>
          <p v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</p>

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
              :disabled="submitting"
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
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
  attributes: { type: Array, default: () => [] },
})

const variants = ref(props.product.variants ?? [])
const showModal = ref(false)
const editing = ref(null)
const submitting = ref(false)
const errorMessage = ref('')

const emptyForm = () => ({
  sku: '',
  attributes: {},
  price: null,
  compare_price: null,
  stock_quantity: 0,
  is_active: true,
})
const form = ref(emptyForm())

const openModal = (variant = null) => {
  editing.value = variant
  errorMessage.value = ''
  form.value = variant
    ? {
        sku: variant.sku ?? '',
        attributes: { ...(variant.attributes ?? {}) },
        price: variant.price,
        compare_price: variant.compare_price,
        stock_quantity: variant.stock_quantity ?? 0,
        is_active: !!variant.is_active,
      }
    : emptyForm()
  showModal.value = true
}

const submit = async () => {
  submitting.value = true
  errorMessage.value = ''

  const payload = {
    ...form.value,
    attributes: Object.fromEntries(Object.entries(form.value.attributes).filter(([, v]) => v)),
  }

  try {
    if (editing.value) {
      const { data } = await window.axios.put(
        route('tenant.manager.products.variants.update', [props.product.id, editing.value.id]),
        payload,
      )
      const idx = variants.value.findIndex((v) => v.id === editing.value.id)
      if (idx !== -1) variants.value[idx] = data.variant
    } else {
      const { data } = await window.axios.post(
        route('tenant.manager.products.variants.store', props.product.id),
        payload,
      )
      variants.value.push(data.variant)
    }
    showModal.value = false
  } catch (e) {
    errorMessage.value = e?.response?.data?.message ?? t('common.the_variant_could_not_be_saved')
  } finally {
    submitting.value = false
  }
}

const destroy = async (variant) => {
  if (!confirm(t('common.delete_this_variant'))) return
  await window.axios.delete(route('tenant.manager.products.variants.destroy', [props.product.id, variant.id]))
  variants.value = variants.value.filter((v) => v.id !== variant.id)
}

const formatPrice = (val) =>
  new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
</script>
