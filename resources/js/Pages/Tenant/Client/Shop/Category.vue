<template>
  <ClientLayout :title="category.name" :description="category.description">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Breadcrumbs -->
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <Link :href="route('tenant.shop')" class="hover:text-indigo-600">{{ t('common.shop') }}</Link>
        <span>/</span>
        <Link
          v-if="category.parent"
          :href="route('tenant.category.show', category.parent.slug)"
          class="hover:text-indigo-600"
        >
          {{ category.parent.name }}
        </Link>
        <span v-if="category.parent">/</span>
        <span class="text-gray-900 font-medium">{{ category.name }}</span>
      </nav>

      <div class="flex gap-8">
        <!-- Filter Sidebar -->
        <aside class="hidden lg:block w-60 flex-shrink-0">
          <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-6 sticky top-4">
            <h3 class="font-bold text-gray-900">{{ t('client.shop.category.filters') }}</h3>

            <!-- Subcategories -->
            <div v-if="category.children?.length">
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">
                {{ t('client.shop.category.subcategories') }}
              </p>
              <div class="space-y-1">
                <Link
                  v-for="child in category.children"
                  :key="child.id"
                  :href="route('tenant.category.show', child.slug)"
                  class="block text-sm text-gray-700 hover:text-indigo-600 py-0.5"
                >
                  {{ child.name }}
                </Link>
              </div>
            </div>

            <!-- Type -->
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">
                {{ t('client.filtersidebar.product_type') }}
              </p>
              <div class="space-y-1">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="" class="accent-indigo-600" />
                  {{ t('common.all') }}
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="physical" class="accent-indigo-600" />
                  {{ t('client.shop.category.physical') }}
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="digital" class="accent-indigo-600" />
                  {{ t('client.shop.category.digital') }}
                </label>
              </div>
            </div>

            <!-- Price range -->
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ t('common.price') }}</p>
              <div class="flex items-center gap-2">
                <input
                  v-model="localFilters.min_price"
                  type="number"
                  :placeholder="t('common.from')"
                  class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm"
                />
                <span class="text-gray-400">–</span>
                <input
                  v-model="localFilters.max_price"
                  type="number"
                  :placeholder="t('common.to_2')"
                  class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm"
                />
              </div>
            </div>

            <!-- In stock -->
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="localFilters.in_stock" class="accent-indigo-600" />
              {{ t('common.in_stock_only') }}
            </label>

            <button
              @click="applyFilters"
              class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition"
            >
              {{ t('client.shop.category.apply_filters') }}
            </button>
          </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1">
          <!-- Toolbar -->
          <div class="flex items-center justify-between mb-5 gap-3">
            <p class="text-sm text-gray-500 flex-shrink-0">{{ t('common.a_products_count', { a: products.total }) }}</p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="mobileFiltersOpen = true"
                class="lg:hidden flex items-center gap-2 text-sm border border-gray-200 rounded-xl px-3 py-2 text-gray-700 font-medium"
              >
                <i class="fa-solid fa-sliders"></i> {{ t('client.shop.category.filters') }}
              </button>
              <select
                v-model="localFilters.sort"
                @change="applyFilters"
                class="text-sm border border-gray-200 rounded-xl px-3 py-2"
              >
                <option value="">{{ t('common.default_2') }}</option>
                <option value="price_asc">{{ t('common.price_low_to_high') }}</option>
                <option value="price_desc">{{ t('common.price_high_to_low') }}</option>
                <option value="newest">{{ t('client.filtersidebar.newest') }}</option>
                <option value="rating">{{ t('client.shop.category.best_rated') }}</option>
                <option value="bestsellers">{{ t('client.shop.category.bestsellers') }}</option>
              </select>
            </div>
          </div>

          <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
            <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
          </div>

          <div v-else class="text-center py-16 text-gray-500">
            <p class="text-4xl mb-3">🔍</p>
            <p class="text-lg font-medium">{{ t('common.no_products_match_these_criteria') }}</p>
          </div>

          <!-- Pagination -->
          <div v-if="products.last_page > 1" class="mt-10 flex justify-center gap-2">
            <Link
              v-for="page in products.links"
              :key="page.label"
              :href="page.url"
              v-html="page.label"
              class="px-3 py-2 rounded-lg text-sm border transition"
              :class="
                page.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 hover:border-indigo-300'
              "
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile filter drawer -->
    <Teleport to="body">
      <div v-if="mobileFiltersOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-black/50" @click="mobileFiltersOpen = false"></div>
        <div class="absolute inset-y-0 right-0 w-full max-w-xs bg-white shadow-xl flex flex-col">
          <div class="flex items-center justify-between p-5 border-b">
            <h3 class="font-bold text-gray-900">{{ t('client.shop.category.filters') }}</h3>
            <button type="button" @click="mobileFiltersOpen = false" class="text-gray-400 hover:text-gray-600 text-xl">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-6">
            <!-- Subcategories -->
            <div v-if="category.children?.length">
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">
                {{ t('client.shop.category.subcategories') }}
              </p>
              <div class="space-y-1">
                <Link
                  v-for="child in category.children"
                  :key="child.id"
                  :href="route('tenant.category.show', child.slug)"
                  class="block text-sm text-gray-700 hover:text-indigo-600 py-0.5"
                >
                  {{ child.name }}
                </Link>
              </div>
            </div>

            <!-- Type -->
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">
                {{ t('client.filtersidebar.product_type') }}
              </p>
              <div class="space-y-1">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="" class="accent-indigo-600" />
                  {{ t('common.all') }}
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="physical" class="accent-indigo-600" />
                  {{ t('client.shop.category.physical') }}
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="radio" v-model="localFilters.type" value="digital" class="accent-indigo-600" />
                  {{ t('client.shop.category.digital') }}
                </label>
              </div>
            </div>

            <!-- Price range -->
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ t('common.price') }}</p>
              <div class="flex items-center gap-2">
                <input
                  v-model="localFilters.min_price"
                  type="number"
                  :placeholder="t('common.from')"
                  class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm"
                />
                <span class="text-gray-400">–</span>
                <input
                  v-model="localFilters.max_price"
                  type="number"
                  :placeholder="t('common.to_2')"
                  class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm"
                />
              </div>
            </div>

            <!-- In stock -->
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="localFilters.in_stock" class="accent-indigo-600" />
              {{ t('common.in_stock_only') }}
            </label>
          </div>

          <div class="p-5 border-t">
            <button
              @click="applyFiltersAndClose"
              class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition"
            >
              {{ t('client.shop.category.apply_filters') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </ClientLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import ProductCard from '@/Components/Client/ProductCard.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  category: { type: Object, required: true },
  products: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const localFilters = reactive({ ...props.filters })
const mobileFiltersOpen = ref(false)

function applyFiltersAndClose() {
  applyFilters()
  mobileFiltersOpen.value = false
}

function applyFilters() {
  router.get(route('tenant.category.show', props.category.slug), localFilters, { preserveScroll: true })
}
</script>
