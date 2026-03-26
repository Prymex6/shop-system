<template>
  <ManagerLayout :title="t('layout.clientlayout.collections')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('layout.clientlayout.collections') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.collections.index.group_products_into_themed_collections') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.collections.index.new_collection') }}
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.products_2') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('landlord.modifications.form.active') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.collections.index.period') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.collections.index.created') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="col in collections.data" :key="col.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div v-if="col.image" class="w-10 h-10 rounded-lg overflow-hidden shrink-0">
                    <img :src="'/storage/' + col.image" class="w-full h-full object-cover" />
                  </div>
                  <div
                    v-else
                    class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-400 shrink-0"
                  >
                    <i class="fa-solid fa-layer-group text-sm"></i>
                  </div>
                  <div>
                    <p class="font-semibold text-gray-900">{{ col.name }}</p>
                    <p v-if="col.description" class="text-xs text-gray-400 truncate max-w-48">{{ col.description }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ col.slug }}</td>
              <td class="px-4 py-3 text-center">
                <span class="font-semibold text-gray-700">{{ col.products_count ?? 0 }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="col.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ col.is_active ? 'Tak' : t('manager.collections.index.no') }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-500">
                <span v-if="col.starts_at || col.ends_at">
                  {{ col.starts_at ? formatDate(col.starts_at) : '∞' }} –
                  {{ col.ends_at ? formatDate(col.ends_at) : '∞' }}
                </span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-400">{{ formatDate(col.created_at) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openProducts(col)" class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                    {{ t('common.products') }}
                  </button>
                  <button @click="openModal(col)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(col)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!collections.data?.length">
              <td colspan="7" class="text-center py-12 text-gray-400">
                {{ t('manager.collections.index.no_collections') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="collections.last_page > 1" class="flex justify-center gap-2">
        <Link
          v-for="link in collections.links"
          :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="px-3 py-2 rounded-lg text-sm border transition"
          :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:border-blue-300'"
        />
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_collection') : t('common.new_collection') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
            <input
              v-model="form.name"
              @input="autoSlug"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.description') }}</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.collections.index.image_url')
            }}</label>
            <input
              v-model="form.image"
              type="text"
              placeholder="/storage/..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.collections.index.start_date')
              }}</label>
              <input
                v-model="form.starts_at"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.collections.index.end_date')
              }}</label>
              <input
                v-model="form.ends_at"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="col_active" class="h-4 w-4 text-blue-600 rounded" />
            <label for="col_active" class="text-sm font-medium text-gray-700">{{
              t('landlord.modifications.form.active')
            }}</label>
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
              {{ editing ? t('common.save_changes') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Products Modal -->
    <div
      v-if="showProductsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showProductsModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">Produkty w kolekcji: {{ currentCollection?.name }}</h3>
          <button @click="showProductsModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>
        <div class="p-6 space-y-4">
          <!-- Search to add -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.collections.index.add_product')
            }}</label>
            <div class="flex gap-2">
              <input
                v-model="productSearch"
                type="text"
                :placeholder="t('manager.collections.index.search_by_name')"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
              />
              <button
                @click="searchProducts"
                class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
              >
                {{ t('common.search') }}
              </button>
            </div>
            <div
              v-if="searchResults.length"
              class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto"
            >
              <div
                v-for="p in searchResults"
                :key="p.id"
                class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 cursor-pointer"
                @click="addProduct(p)"
              >
                <span class="text-sm text-gray-700">{{ p.name }}</span>
                <button class="text-xs text-blue-600 font-medium">{{ t('common.add_2') }}</button>
              </div>
            </div>
          </div>

          <!-- Current products list -->
          <div>
            <p class="text-sm font-medium text-gray-700 mb-2">
              {{ t('manager.collections.index.products_in_the_collection') }}
            </p>
            <div v-if="!collectionProducts.length" class="text-sm text-gray-400 text-center py-4">
              {{ t('manager.collections.index.no_products_in_this_collection') }}
            </div>
            <div v-else class="space-y-2 max-h-56 overflow-y-auto">
              <div
                v-for="p in collectionProducts"
                :key="p.id"
                class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg"
              >
                <span class="text-sm text-gray-700">{{ p.name }}</span>
                <button @click="removeProduct(p)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                  {{ t('common.delete') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  collections: { type: Object, required: true },
  products: { type: Array, default: () => [] },
})

const showModal = ref(false)
const showProductsModal = ref(false)
const editing = ref(null)
const currentCollection = ref(null)
const collectionProducts = ref([])
const productSearch = ref('')
const searchResults = ref([])

const form = useForm({
  name: '',
  slug: '',
  description: '',
  image: '',
  is_active: true,
  starts_at: '',
  ends_at: '',
})

const autoSlug = () => {
  if (!editing.value) {
    form.slug = form.name
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '')
  }
}

const openModal = (col = null) => {
  editing.value = col
  if (col) {
    form.name = col.name ?? ''
    form.slug = col.slug ?? ''
    form.description = col.description ?? ''
    form.image = col.image ?? ''
    form.is_active = col.is_active ?? true
    form.starts_at = col.starts_at ? col.starts_at.substring(0, 10) : ''
    form.ends_at = col.ends_at ? col.ends_at.substring(0, 10) : ''
  } else {
    form.reset()
    form.is_active = true
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.collections.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.collections.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (col) => {
  if (!confirm(t('manager.collections.index.delete_the_collection_a', { a: col.name }))) return
  router.delete(route('tenant.manager.collections.destroy', col.id))
}

const openProducts = (col) => {
  currentCollection.value = col
  collectionProducts.value = col.products ?? []
  searchResults.value = []
  productSearch.value = ''
  showProductsModal.value = true
}

const searchProducts = () => {
  const q = productSearch.value.toLowerCase()
  searchResults.value = props.products
    .filter((p) => p.name.toLowerCase().includes(q) && !collectionProducts.value.find((cp) => cp.id === p.id))
    .slice(0, 10)
}

const addProduct = (p) => {
  router.post(
    route('tenant.manager.collections.products.add', currentCollection.value.id),
    { product_id: p.id },
    {
      preserveScroll: true,
      onSuccess: () => {
        collectionProducts.value.push(p)
        searchResults.value = searchResults.value.filter((r) => r.id !== p.id)
      },
    },
  )
}

const removeProduct = (p) => {
  router.delete(route('tenant.manager.collections.products.remove', currentCollection.value.id), {
    data: { product_id: p.id },
    preserveScroll: true,
    onSuccess: () => {
      collectionProducts.value = collectionProducts.value.filter((cp) => cp.id !== p.id)
    },
  })
}

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(locale.value) : '—')
</script>
