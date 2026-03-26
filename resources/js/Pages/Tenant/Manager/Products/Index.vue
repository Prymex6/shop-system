<template>
  <ManagerLayout :title="t('common.products')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.products') }}</h1>
        <div class="flex items-center gap-2">
          <button
            @click="showImportModal = true"
            class="bg-gray-100 text-gray-700 px-3 py-2 rounded-md font-semibold hover:bg-gray-200 transition text-sm"
          >
            {{ t('manager.customers.index.import_csv') }}
          </button>
          <Link
            :href="route('tenant.manager.products.create')"
            class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
          >
            {{ t('manager.products.index.new_product') }}
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6 flex flex-wrap gap-3">
        <input
          v-model="localFilters.search"
          type="text"
          :placeholder="t('common.search_products')"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm flex-1 min-w-[200px]"
          @input="applyFilters"
        />
        <select
          v-model="localFilters.category"
          @change="applyFilters"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm"
        >
          <option value="">{{ t('manager.products.index.all_categories') }}</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <select
          v-model="localFilters.type"
          @change="applyFilters"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm"
        >
          <option value="">{{ t('manager.products.index.all_types') }}</option>
          <option value="physical">{{ t('client.shop.category.physical') }}</option>
          <option value="digital">{{ t('client.shop.category.digital') }}</option>
        </select>
        <select
          v-model="localFilters.status"
          @change="applyFilters"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm"
        >
          <option value="">{{ t('common.all_statuses') }}</option>
          <option value="published">{{ t('manager.products.index.published') }}</option>
          <option value="draft">{{ t('manager.products.index.drafts') }}</option>
        </select>
        <select
          v-model="localFilters.stock"
          @change="applyFilters"
          class="border border-gray-200 rounded-md px-3 py-2 text-sm"
        >
          <option value="">{{ t('manager.products.form.stock_level') }}</option>
          <option value="low">{{ t('manager.inventory.index.low_stock') }}</option>
        </select>
      </div>

      <!-- Bulk action bar -->
      <Transition name="slide-down">
        <div
          v-if="selectedIds.length"
          class="bg-blue-600 text-white rounded-2xl px-4 py-3 mb-4 flex items-center gap-4 flex-wrap"
        >
          <span class="font-semibold text-sm">Zaznaczono: {{ selectedIds.length }}</span>
          <div class="flex items-center gap-2 flex-wrap">
            <button
              @click="bulkActivate(true)"
              class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
            >
              {{ t('landlord.tenants.index.activate') }}
            </button>
            <button
              @click="bulkActivate(false)"
              class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
            >
              {{ t('landlord.tenants.index.deactivate') }}
            </button>
            <button
              @click="openBulkEdit('price')"
              class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
            >
              {{ t('manager.products.index.change_the_price') }}
            </button>
            <button
              @click="openBulkEdit('category')"
              class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
            >
              {{ t('manager.products.index.change_the_category') }}
            </button>
            <button
              @click="bulkDelete"
              class="bg-red-400 hover:bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
            >
              {{ t('manager.products.index.delete_selected') }}
            </button>
          </div>
          <button @click="clearSelection" class="ml-auto text-white/70 hover:text-white text-sm">
            {{ t('manager.products.index.clear_the_selection') }}
          </button>
        </div>
      </Transition>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 w-8">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate="someSelected"
                  @change="toggleSelectAll"
                  class="h-4 w-4 text-blue-600 rounded"
                />
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600 w-16">
                {{ t('manager.products.index.photo') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.fraud.index.type') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.category') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.price') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.inventory.index.stock') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.status') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="product in products.data"
              :key="product.id"
              class="hover:bg-gray-50"
              :class="{ 'bg-indigo-50': selectedIds.includes(product.id) }"
            >
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(product.id)"
                  @change="toggleSelect(product.id)"
                  class="h-4 w-4 text-blue-600 rounded"
                />
              </td>
              <td class="px-4 py-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden">
                  <img v-if="product.image" :src="'/storage/' + product.image" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-lg">
                    {{ product.type === 'digital' ? '📥' : '📦' }}
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <Link
                  :href="route('tenant.manager.products.edit', product.id)"
                  class="font-semibold text-gray-900 hover:text-blue-600 transition"
                >
                  {{ product.name }}
                </Link>
                <p v-if="product.sku" class="text-xs text-gray-400">SKU: {{ product.sku }}</p>
              </td>
              <td class="px-4 py-3">
                <span
                  v-if="product.type === 'digital'"
                  class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium"
                  >{{ t('client.cartsidebar.digital') }}</span
                >
                <span v-else class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{
                  t('manager.products.index.physical')
                }}</span>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ product.category?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatPrice(product.price) }}</td>
              <td class="px-4 py-3 text-center">
                <span v-if="product.type === 'digital'" class="text-xs text-gray-400">—</span>
                <span v-else-if="!product.track_stock" class="text-xs text-gray-400">∞</span>
                <span v-else-if="product.stock_quantity <= 0" class="text-xs font-semibold text-red-600">{{
                  t('common.none')
                }}</span>
                <span
                  v-else-if="product.stock_quantity <= product.low_stock_threshold"
                  class="text-xs font-semibold text-orange-600"
                >
                  {{ product.stock_quantity }} ⚠️
                </span>
                <span v-else class="text-xs font-semibold text-green-700">{{ product.stock_quantity }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="togglePublish(product)"
                  class="text-xs font-semibold px-2 py-0.5 rounded-full transition"
                  :class="
                    product.is_published
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  "
                >
                  {{ product.is_published ? t('manager.articles.form.published') : t('manager.articles.form.draft') }}
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <Link
                    :href="route('tenant.manager.products.edit', product.id)"
                    class="text-blue-600 hover:text-blue-900 px-2 py-1 text-xs font-medium"
                    >{{ t('common.edit') }}</Link
                  >
                  <button
                    @click="destroy(product)"
                    class="text-red-500 hover:text-red-700 px-2 py-1 text-xs font-medium"
                  >
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data.length">
              <td colspan="9" class="text-center py-12 text-gray-400">{{ t('common.no_products') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="products.last_page > 1" class="mt-6 flex justify-center gap-2">
        <Link
          v-for="link in products.links"
          :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="px-3 py-2 rounded-lg text-sm border transition"
          :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:border-blue-300'"
        />
      </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div v-if="bulkEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl max-w-sm w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-gray-900">
          {{
            bulkEditField === 'price'
              ? t('manager.products.index.change_the_price')
              : t('manager.products.index.change_the_category')
          }}
          <span class="text-sm font-normal text-gray-500 ml-1"
            >({{ t('common.a_products_count', { a: selectedIds.length }) }})</span
          >
        </h3>

        <div v-if="bulkEditField === 'price'">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{
            t('manager.products.index.new_price_pln')
          }}</label>
          <input
            v-model="bulkEditValue"
            type="number"
            step="0.01"
            min="0"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="np. 49.99"
          />
        </div>

        <div v-if="bulkEditField === 'category'">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.category') }}</label>
          <select v-model="bulkEditValue" class="w-full px-3 py-2 border rounded-lg">
            <option value="">{{ t('manager.products.index.no_categories') }}</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button @click="bulkEditModal = false" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
          <button @click="submitBulkEdit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            {{ t('client.checkout.apply') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <ProductImportModal :show="showImportModal" @close="showImportModal = false" @imported="onImported" />
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ProductImportModal from '@/Components/Manager/ProductImportModal.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  products: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const localFilters = reactive({ ...props.filters })
const selectedIds = ref([])
const bulkEditModal = ref(false)
const bulkEditField = ref('')
const bulkEditValue = ref('')
const showImportModal = ref(false)

// ---- Filters ----
function applyFilters() {
  router.get(route('tenant.manager.products.index'), localFilters, { preserveState: true, replace: true })
}

// ---- Formatting ----
function formatPrice(val) {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(val ?? 0)
}

// ---- Single actions ----
function togglePublish(product) {
  router.patch(route('tenant.manager.products.toggle-publish', product.id), {}, { preserveScroll: true })
}

function destroy(product) {
  if (!confirm(t('manager.products.index.delete_the_product_a', { a: product.name }))) return
  router.delete(route('tenant.manager.products.destroy', product.id))
}

// ---- Selection ----
const allSelected = computed(
  () => props.products.data.length > 0 && props.products.data.every((p) => selectedIds.value.includes(p.id)),
)
const someSelected = computed(() => selectedIds.value.length > 0 && !allSelected.value)

function toggleSelect(id) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((i) => i !== id)
  } else {
    selectedIds.value.push(id)
  }
}

function toggleSelectAll(e) {
  if (e.target.checked) {
    selectedIds.value = props.products.data.map((p) => p.id)
  } else {
    selectedIds.value = []
  }
}

function clearSelection() {
  selectedIds.value = []
}

// ---- Bulk actions ----
function bulkActivate(publish) {
  if (!selectedIds.value.length) return
  router.patch(
    route('tenant.manager.products.bulk.update'),
    {
      ids: selectedIds.value,
      field: 'is_published',
      value: publish,
    },
    {
      preserveScroll: true,
      onSuccess: () => clearSelection(),
    },
  )
}

function openBulkEdit(field) {
  bulkEditField.value = field
  bulkEditValue.value = ''
  bulkEditModal.value = true
}

function submitBulkEdit() {
  router.patch(
    route('tenant.manager.products.bulk.update'),
    {
      ids: selectedIds.value,
      field: bulkEditField.value,
      value: bulkEditValue.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        bulkEditModal.value = false
        clearSelection()
      },
    },
  )
}

function bulkDelete() {
  if (!selectedIds.value.length) return
  if (!confirm(t('manager.products.index.delete_a_products_this_cannot_be', { a: selectedIds.value.length }))) return
  router.delete(route('tenant.manager.products.bulk.destroy'), {
    data: { ids: selectedIds.value },
    onSuccess: () => clearSelection(),
  })
}

// ---- Import ----
function onImported(result) {
  router.reload({ only: ['products'] })
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
