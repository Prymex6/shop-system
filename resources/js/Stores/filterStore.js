import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export const useFilterStore = defineStore('filters', () => {
  const query = ref('')
  const categoryId = ref(null)
  const minPrice = ref(null)
  const maxPrice = ref(null)
  const inStock = ref(false)
  const type = ref(null) // 'physical' | 'digital' | null
  const sort = ref('newest') // newest | price_asc | price_desc | popular | rating
  const attributes = ref({}) // { 'Kolor': ['Czerwony'], 'Rozmiar': ['XL'] }

  const hasActiveFilters = computed(
    () =>
      !!query.value ||
      !!categoryId.value ||
      !!minPrice.value ||
      !!maxPrice.value ||
      inStock.value ||
      !!type.value ||
      Object.keys(attributes.value).length > 0,
  )

  function setQuery(val) {
    query.value = val
  }

  function setCategory(id) {
    categoryId.value = id
  }

  function setPriceRange(min, max) {
    minPrice.value = min || null
    maxPrice.value = max || null
  }

  function toggleStock() {
    inStock.value = !inStock.value
  }

  function setType(val) {
    type.value = val
  }

  function setSort(val) {
    sort.value = val
  }

  function toggleAttribute(attrName, value) {
    if (!attributes.value[attrName]) {
      attributes.value[attrName] = []
    }
    const idx = attributes.value[attrName].indexOf(value)
    if (idx === -1) {
      attributes.value[attrName].push(value)
    } else {
      attributes.value[attrName].splice(idx, 1)
      if (attributes.value[attrName].length === 0) {
        delete attributes.value[attrName]
      }
    }
  }

  function hasAttribute(attrName, value) {
    return (attributes.value[attrName] ?? []).includes(value)
  }

  function reset() {
    query.value = ''
    categoryId.value = null
    minPrice.value = null
    maxPrice.value = null
    inStock.value = false
    type.value = null
    sort.value = 'newest'
    attributes.value = {}
  }

  function toParams() {
    return {
      q: query.value || undefined,
      category: categoryId.value || undefined,
      min_price: minPrice.value || undefined,
      max_price: maxPrice.value || undefined,
      in_stock: inStock.value ? '1' : undefined,
      type: type.value || undefined,
      sort: sort.value !== 'newest' ? sort.value : undefined,
      ...Object.fromEntries(Object.entries(attributes.value).map(([k, v]) => [`attr[${k}]`, v.join(',')])),
    }
  }

  function applyToUrl(baseUrl) {
    router.get(baseUrl, toParams(), { preserveState: true, replace: true })
  }

  function loadFromParams(params) {
    query.value = params.q ?? ''
    categoryId.value = params.category ? Number(params.category) : null
    minPrice.value = params.min_price ? Number(params.min_price) : null
    maxPrice.value = params.max_price ? Number(params.max_price) : null
    inStock.value = params.in_stock === '1'
    type.value = params.type ?? null
    sort.value = params.sort ?? 'newest'

    // Reset attributes then restore
    attributes.value = {}
    for (const [key, val] of Object.entries(params)) {
      const m = key.match(/^attr\[(.+)]$/)
      if (m) {
        attributes.value[m[1]] = String(val).split(',').filter(Boolean)
      }
    }
  }

  return {
    query,
    categoryId,
    minPrice,
    maxPrice,
    inStock,
    type,
    sort,
    attributes,
    hasActiveFilters,
    setQuery,
    setCategory,
    setPriceRange,
    toggleStock,
    setType,
    setSort,
    toggleAttribute,
    hasAttribute,
    reset,
    toParams,
    applyToUrl,
    loadFromParams,
  }
})
