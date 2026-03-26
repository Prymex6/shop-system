<template>
  <ManagerLayout :title="t('common.product_bundles')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('common.product_bundles') }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ t('manager.productbundles.index.build_bundles_out_of_several_products') }}
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('manager.productbundles.index.new_bundle') }}
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.price') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.productbundles.index.components') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!bundles.data?.length">
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.productbundles.index.no_bundles_yet_create_the_first') }}
                </td>
              </tr>
              <tr v-for="bundle in bundles.data" :key="bundle.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="font-semibold text-gray-900">{{ bundle.name }}</div>
                  <div class="text-xs text-gray-400">{{ bundle.slug }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatPrice(bundle.price) }}
                  <span v-if="bundle.compare_price" class="ml-2 text-xs text-gray-400 line-through">
                    {{ formatPrice(bundle.compare_price) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ bundle.items_count ?? bundle.items?.length ?? 0 }} pozycji
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="bundle.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ bundle.is_active ? t('common.active') : t('common.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                  <button @click="openEditModal(bundle)" class="text-blue-600 hover:text-blue-900">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="deleteBundle(bundle)" class="text-red-600 hover:text-red-900">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h2 class="text-xl font-bold mb-6">{{ editingBundle ? t('common.edit_bundle') : t('common.new_bundle') }}</h2>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border rounded-lg"
                  @input="autoSlug"
                />
                <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.productbundles.index.slug')
                }}</label>
                <input
                  v-model="form.slug"
                  type="text"
                  required
                  class="w-full px-3 py-2 border rounded-lg font-mono text-sm"
                />
                <p v-if="form.errors.slug" class="text-red-600 text-sm mt-1">{{ form.errors.slug }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.description') }}</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.productbundles.index.price_pln')
                }}</label>
                <input
                  v-model="form.price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <p v-if="form.errors.price" class="text-red-600 text-sm mt-1">{{ form.errors.price }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.productbundles.index.compare_at_price_pln')
                }}</label>
                <input
                  v-model="form.compare_price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border rounded-lg"
                />
              </div>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model="form.is_active"
                type="checkbox"
                id="bundle-active"
                class="h-4 w-4 text-blue-600 rounded"
              />
              <label for="bundle-active" class="text-sm font-medium text-gray-700">{{
                t('manager.productbundles.index.bundle_active')
              }}</label>
            </div>

            <!-- Bundle items -->
            <div class="border-t pt-4">
              <h3 class="text-sm font-semibold text-gray-800 mb-3">
                {{ t('manager.productbundles.index.bundle_components') }}
              </h3>

              <div class="space-y-2 mb-3">
                <div
                  v-for="(item, idx) in form.items"
                  :key="idx"
                  class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                >
                  <select v-model="item.product_id" class="flex-1 px-3 py-2 border rounded-lg text-sm">
                    <option value="">{{ t('manager.productbundles.index.choose_a_product') }}</option>
                    <option v-for="prod in products" :key="prod.id" :value="prod.id">
                      {{ prod.name }}
                    </option>
                  </select>
                  <div class="flex items-center gap-1">
                    <label class="text-xs text-gray-500">{{ t('common.quantity_2') }}</label>
                    <input
                      v-model.number="item.quantity"
                      type="number"
                      min="1"
                      class="w-16 px-2 py-2 border rounded-lg text-sm text-center"
                    />
                  </div>
                  <button type="button" @click="removeItem(idx)" class="text-red-500 hover:text-red-700 text-sm">
                    {{ t('common.delete') }}
                  </button>
                </div>
                <div v-if="!form.items.length" class="text-sm text-gray-400 italic">
                  {{ t('manager.productbundles.index.no_components_add_products_below') }}
                </div>
              </div>

              <button type="button" @click="addItem" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                {{ t('manager.productbundles.index.add_product_to_the_bundle') }}
              </button>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ form.processing ? 'Zapisywanie...' : editingBundle ? t('common.save') : t('common.create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  bundles: { type: Object, default: () => ({ data: [], links: [] }) },
  products: { type: Array, default: () => [] },
})

const showModal = ref(false)
const editingBundle = ref(null)

const form = useForm({
  name: '',
  slug: '',
  description: '',
  price: '',
  compare_price: '',
  is_active: true,
  items: [],
})

function autoSlug() {
  if (!editingBundle.value) {
    form.slug = form.name
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '')
  }
}

function addItem() {
  form.items.push({ product_id: '', quantity: 1 })
}

function removeItem(idx) {
  form.items.splice(idx, 1)
}

function openCreateModal() {
  editingBundle.value = null
  form.reset()
  form.items = []
  form.is_active = true
  showModal.value = true
}

function openEditModal(bundle) {
  editingBundle.value = bundle
  form.name = bundle.name
  form.slug = bundle.slug
  form.description = bundle.description || ''
  form.price = bundle.price
  form.compare_price = bundle.compare_price || ''
  form.is_active = bundle.is_active
  form.items = (bundle.items || [])
    .filter((i) => i && i.product_id)
    .map((i) => ({ product_id: i.product_id, quantity: i.quantity }))
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingBundle.value = null
  form.reset()
  form.items = []
}

function submitForm() {
  if (editingBundle.value) {
    form.put(route('tenant.manager.bundles.update', editingBundle.value.id), {
      onSuccess: closeModal,
    })
  } else {
    form.post(route('tenant.manager.bundles.store'), {
      onSuccess: closeModal,
    })
  }
}

function deleteBundle(bundle) {
  if (!confirm(t('manager.productbundles.index.delete_the_bundle_a', { a: bundle.name }))) return
  router.delete(route('tenant.manager.bundles.destroy', bundle.id))
}

function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}
</script>
