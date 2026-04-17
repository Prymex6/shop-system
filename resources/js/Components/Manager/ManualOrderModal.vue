<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/60 z-50 flex items-stretch overflow-hidden"
    @keydown.esc="$emit('close')"
    tabindex="-1"
  >
    <div class="bg-white w-full max-w-5xl mx-auto flex flex-col overflow-hidden md:my-6 md:rounded-xl md:shadow-2xl">
      <!-- Header -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 shrink-0">
        <h2 class="text-xl font-bold text-gray-900">
          <i class="fa-solid fa-plus mr-2 text-blue-600"></i>{{ t('manager.manualordermodal.new_order_manual') }}
        </h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-hidden flex flex-col md:flex-row min-h-0">
        <!-- LEFT: Product Picker -->
        <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-100 min-h-0">
          <div class="px-4 pt-4 shrink-0 space-y-2">
            <!-- Search -->
            <div class="relative">
              <i
                class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"
              ></i>
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('manager.manualordermodal.search_products_by_name_or_sku')"
                class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @input="onSearch"
              />
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <i class="fa-solid fa-xmark text-xs"></i>
              </button>
            </div>
            <p v-if="searchQuery" class="text-xs text-gray-500 pb-1">
              {{ t('manager.manualordermodal.results_for') }}
              <span class="font-medium text-gray-700">{{ searchQuery }}</span> — znaleziono
              {{ products.length }}
            </p>
          </div>

          <!-- Products grid -->
          <div class="flex-1 overflow-y-auto p-4">
            <div v-if="productsLoading" class="text-center py-10 text-gray-400 text-sm">
              <i class="fa-solid fa-spinner animate-spin text-2xl mb-2 block"></i>
              {{ t('manager.manualordermodal.loading') }}
            </div>
            <div v-else-if="products.length === 0" class="text-center py-10 text-gray-400 text-sm">
              <i class="fa-solid fa-face-meh text-2xl mb-2 block"></i>
              {{ t('common.no_products') }}
            </div>
            <div v-else class="grid grid-cols-2 gap-3">
              <div
                v-for="product in products"
                :key="product.id"
                class="bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-400 hover:shadow-sm transition-all cursor-pointer"
                @click="openProductPicker(product)"
              >
                <div class="flex gap-2 mb-2">
                  <img
                    v-if="product.image"
                    :src="product.image"
                    class="w-10 h-10 rounded object-cover shrink-0 bg-gray-100"
                  />
                  <div v-else class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-image text-gray-300 text-lg"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-900 text-sm leading-tight">{{ product.name }}</p>
                    <p v-if="product.sku" class="text-xs text-gray-400">{{ product.sku }}</p>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                  <span
                    v-if="product.stock !== null"
                    :class="product.stock > 0 ? 'text-green-600' : 'text-red-500'"
                    class="text-xs"
                  >
                    szt: {{ product.stock }}
                  </span>
                </div>
                <div v-if="product.variants?.length" class="mt-1">
                  <span class="text-xs text-blue-600"
                    ><i class="fa-solid fa-tag mr-1"></i
                    >{{ t('common.a_variants', { a: product.variants.length }) }}</span
                  >
                </div>
                <button
                  class="mt-2 w-full py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded transition"
                >
                  <i class="fa-solid fa-plus mr-1"></i> {{ t('common.add') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Cart + Customer Form -->
        <div class="w-full md:w-80 flex flex-col overflow-hidden bg-gray-50 min-h-0 shrink-0">
          <!-- Cart -->
          <div class="flex-1 overflow-y-auto p-4 space-y-2 min-h-0">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('common.cart') }}</h3>
            <div v-if="cart.length === 0" class="text-center py-8 text-gray-400 text-sm">
              {{ t('manager.manualordermodal.choose_products_on_the_left') }}
            </div>
            <div v-for="(item, idx) in cart" :key="idx" class="bg-white rounded-lg border border-gray-200 px-3 py-2">
              <div class="flex items-start gap-2">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 leading-tight">{{ item.name }}</p>
                  <p v-if="item.variant_label" class="text-xs text-gray-500">{{ item.variant_label }}</p>
                  <input
                    v-model.number="item.price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 w-24 text-xs border border-gray-200 rounded px-2 py-0.5 text-gray-700"
                    :title="t('manager.manualordermodal.unit_price')"
                  />
                </div>
                <div class="flex items-center gap-1 shrink-0 mt-0.5">
                  <button
                    @click="decreaseQty(idx)"
                    class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold flex items-center justify-center"
                  >
                    <i class="fa-solid fa-minus text-xs"></i>
                  </button>
                  <span class="w-5 text-center text-sm font-semibold">{{ item.quantity }}</span>
                  <button
                    @click="increaseQty(idx)"
                    class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold flex items-center justify-center"
                  >
                    +
                  </button>
                  <button
                    @click="removeItem(idx)"
                    class="w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 text-red-500 text-xs ml-0.5 flex items-center justify-center"
                  >
                    <i class="fa-solid fa-xmark text-xs"></i>
                  </button>
                </div>
              </div>
              <p class="text-right text-xs text-gray-500 mt-1">
                = {{ itemTotal(item).toFixed(2) }} {{ t('common.currency_pln') }}
              </p>
            </div>
          </div>

          <!-- Total -->
          <div
            class="px-4 py-3 border-t border-gray-200 bg-white flex justify-between font-bold text-gray-900 shrink-0"
          >
            <span>{{ t('manager.manualordermodal.total') }}</span>
            <span>{{ cartTotal.toFixed(2) }} {{ t('common.currency_pln') }}</span>
          </div>

          <!-- Customer form -->
          <div class="p-4 border-t border-gray-200 bg-white space-y-3 overflow-y-auto shrink-0">
            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
              {{ t('manager.manualordermodal.customer_details') }}
            </p>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.full_name_2') }}</label>
              <input
                v-model="form.customer_name"
                type="text"
                placeholder="Jan Kowalski"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.email') }}</label>
                <input
                  v-model="form.customer_email"
                  type="email"
                  :placeholder="t('manager.manualordermodal.john_example_com')"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.phone') }}</label>
                <input
                  v-model="form.customer_phone"
                  type="tel"
                  placeholder="123 456 789"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide pt-1">
              {{ t('manager.manualordermodal.shipping_address') }}
            </p>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                  t('manager.manualordermodal.first_name')
                }}</label>
                <input
                  v-model="form.shipping_first_name"
                  type="text"
                  placeholder="Jan"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                  t('manager.manualordermodal.surname')
                }}</label>
                <input
                  v-model="form.shipping_last_name"
                  type="text"
                  placeholder="Kowalski"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{
                t('manager.manualordermodal.street_and_number')
              }}</label>
              <input
                v-model="form.shipping_street"
                type="text"
                :placeholder="t('manager.manualordermodal.5_10_kwiatowa_st')"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div class="grid grid-cols-5 gap-2">
              <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                  t('manager.manualordermodal.postcode')
                }}</label>
                <input
                  v-model="form.shipping_postal_code"
                  type="text"
                  placeholder="00-000"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div class="col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('common.city') }}</label>
                <input
                  v-model="form.shipping_city"
                  type="text"
                  :placeholder="t('manager.manualordermodal.warsaw')"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide pt-1">{{ t('common.payment') }}</p>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{
                t('manager.manualordermodal.method')
              }}</label>
              <select
                v-model="form.payment_method"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500"
              >
                <option value="transfer">{{ t('manager.manualordermodal.bank_transfer') }}</option>
                <option value="cod">{{ t('manager.manualordermodal.cash_on_delivery') }}</option>
                <option value="cash">{{ t('manager.manualordermodal.cash') }}</option>
                <option value="card">{{ t('manager.manualordermodal.card') }}</option>
                <option value="other">{{ t('manager.manualordermodal.other') }}</option>
              </select>
            </div>
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="form.is_paid"
                class="w-4 h-4 rounded text-green-600 border-gray-300 focus:ring-green-500"
              />
              <span class="text-sm font-medium text-gray-700">{{ t('manager.manualordermodal.already_paid') }}</span>
            </label>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{
                t('manager.manualordermodal.notes_for_the_customer')
              }}</label>
              <textarea
                v-model="form.notes"
                rows="2"
                :placeholder="t('manager.manualordermodal.the_customer_can_see_this')"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 resize-none"
              ></textarea>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{
                t('manager.manualordermodal.internal_note')
              }}</label>
              <textarea
                v-model="form.internal_notes"
                rows="2"
                :placeholder="t('manager.manualordermodal.for_the_manager_only')"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 resize-none"
              ></textarea>
            </div>

            <p v-if="orderError" class="text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-3 py-2">
              {{ orderError }}
            </p>

            <button
              @click="submitOrder"
              :disabled="cart.length === 0 || !form.customer_name || orderProcessing"
              class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold rounded-lg transition"
            >
              <i v-if="orderProcessing" class="fa-solid fa-spinner animate-spin mr-1"></i>
              <i v-else class="fa-solid fa-check mr-1"></i>
              {{ orderProcessing ? 'Tworzenie...' : t('common.create_order') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Variant picker sub-modal -->
    <div v-if="variantPickerProduct" class="fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="font-bold text-gray-900">{{ variantPickerProduct.name }}</h3>
          <button @click="variantPickerProduct = null" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <p class="text-sm text-gray-500">{{ t('common.choose_a_variant') }}</p>
        <div class="space-y-2">
          <button
            v-for="v in variantPickerProduct.variants"
            :key="v.id"
            @click="addToCart(variantPickerProduct, v)"
            class="w-full flex items-center justify-between px-4 py-2.5 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition text-left"
          >
            <span class="text-sm font-medium text-gray-800">{{ v.label || t('client.quickviewmodal.variant') }}</span>
            <span class="text-sm font-bold text-gray-900">{{ formatPrice(v.price) }}</span>
          </button>
        </div>
        <button
          @click="addToCart(variantPickerProduct, null)"
          class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 border border-dashed border-gray-300 rounded-lg transition"
        >
          Bez wariantu ({{ formatPrice(variantPickerProduct.price) }})
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  show: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'success'])

const products = ref([])
const productsLoading = ref(false)
const searchQuery = ref('')
const cart = ref([])
const orderProcessing = ref(false)
const orderError = ref('')
const variantPickerProduct = ref(null)

let searchTimeout = null

const defaultForm = () => ({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_first_name: '',
  shipping_last_name: '',
  shipping_street: '',
  shipping_postal_code: '',
  shipping_city: '',
  shipping_country: 'PL',
  payment_method: 'transfer',
  is_paid: false,
  notes: '',
  internal_notes: '',
})

const form = ref(defaultForm())

watch(
  () => props.show,
  (val) => {
    if (val) {
      cart.value = []
      orderError.value = ''
      form.value = defaultForm()
      searchQuery.value = ''
      variantPickerProduct.value = null
      loadProducts()
    }
  },
)

function clearSearch() {
  searchQuery.value = ''
  loadProducts()
}

async function loadProducts() {
  productsLoading.value = true
  try {
    const { data } = await axios.get(route('tenant.manager.orders.manual.products'), {
      params: searchQuery.value ? { q: searchQuery.value } : {},
    })
    products.value = data
  } finally {
    productsLoading.value = false
  }
}

function onSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadProducts, 300)
}

function openProductPicker(product) {
  if (product.variants?.length) {
    variantPickerProduct.value = product
  } else {
    addToCart(product, null)
  }
}

function addToCart(product, variant) {
  variantPickerProduct.value = null
  const price = variant ? variant.price : product.price
  const existing = cart.value.find((i) => i.product_id === product.id && i.variant_id === (variant?.id ?? null))
  if (existing) {
    existing.quantity++
  } else {
    cart.value.push({
      product_id: product.id,
      variant_id: variant?.id ?? null,
      name: product.name,
      variant_label: variant?.label || null,
      price: price,
      quantity: 1,
    })
  }
}

const itemTotal = (item) => parseFloat(item.price) * item.quantity
const cartTotal = computed(() => cart.value.reduce((s, i) => s + itemTotal(i), 0))
const increaseQty = (idx) => {
  cart.value[idx].quantity++
}
const decreaseQty = (idx) => {
  if (cart.value[idx].quantity <= 1) cart.value.splice(idx, 1)
  else cart.value[idx].quantity--
}
const removeItem = (idx) => {
  cart.value.splice(idx, 1)
}

const formatPrice = (v) => parseFloat(v).toFixed(2) + ' ' + t('common.currency_pln')

async function submitOrder() {
  if (!cart.value.length || !form.value.customer_name) return
  orderError.value = ''
  orderProcessing.value = true

  try {
    const { data } = await axios.post(route('tenant.manager.orders.manual.store'), {
      ...form.value,
      payment_status: form.value.is_paid ? 'paid' : 'pending',
      items: cart.value.map((i) => ({
        product_id: i.product_id,
        variant_id: i.variant_id,
        quantity: i.quantity,
        price: parseFloat(i.price),
      })),
    })

    if (data.success) {
      emit('success', data.order_number)
      emit('close')
    } else {
      orderError.value = data.message || t('common.something_went_wrong')
    }
  } catch (err) {
    orderError.value =
      err.response?.data?.message || err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(' ')
        : t('common.something_went_wrong_try_again')
  } finally {
    orderProcessing.value = false
  }
}
</script>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}
</style>
