<template>
  <LandlordLayout :title="t('landlord.shopsearch.shop_search')">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Search form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-48">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.city') }}</label>
              <input
                v-model="city"
                type="text"
                :placeholder="t('landlord.shopsearch.e_g_krakow')"
                @keydown.enter="doSearch"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.radius') }}</label>
              <select
                v-model="radius"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="1000">1 km</option>
                <option value="2000">2 km</option>
                <option value="5000">5 km</option>
                <option value="10000">10 km</option>
                <option value="20000">20 km</option>
                <option value="50000">50 km</option>
              </select>
            </div>
            <button
              @click="doSearch"
              :disabled="loading || !city.trim()"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg text-sm transition-colors"
            >
              <template v-if="loading">{{ t('client.pickuppointpicker.searching') }}</template>
              <template v-else><i class="fa-solid fa-magnifying-glass mr-1"></i> {{ t('common.search') }}</template>
            </button>
          </div>
          <p class="text-xs text-gray-400 mt-3">
            {{ t('landlord.shopsearch.data_from_openstreetmap_where_a_shop') }} <strong>Google</strong>
            {{ t('landlord.shopsearch.in_the_actions_column_to_check') }}
          </p>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-700 text-sm">
          {{ error }}
        </div>

        <!-- Results summary -->
        <div v-if="results !== null" class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-600">
            {{ t('landlord.shopsearch.found') }} <strong>{{ results.length }}</strong> miejsc w promieniu
            <strong>{{ radiusLabel }}</strong> {{ t('landlord.shopsearch.from') }} <strong>{{ foundCity }}</strong>
            &nbsp;·&nbsp;
            <span class="text-green-600 font-medium">{{ withContact }} z danymi w OSM</span>
          </div>
          <button
            @click="contactOnly = !contactOnly"
            :class="
              contactOnly ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
            "
            class="px-3 py-1.5 rounded-lg text-sm transition-colors"
          >
            <i v-if="contactOnly" class="fa-solid fa-check mr-1"></i> {{ t('landlord.shopsearch.with_osm_data_only') }}
          </button>
        </div>

        <!-- Results table -->
        <div v-if="results !== null" class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.name') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.address') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('landlord.shopsearch.website_facebook') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('landlord.shopsearch.email') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.phone') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <template v-for="r in filtered" :key="r.id">
                <!-- Main row -->
                <tr :class="r.has_contact ? 'bg-white' : 'bg-gray-50'">
                  <td class="px-4 py-3">
                    <div class="font-medium text-gray-900 text-sm">{{ r.name }}</div>
                    <div class="text-xs text-gray-400">{{ r.type }}</div>
                  </td>
                  <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">{{ r.address || '—' }}</td>
                  <td class="px-4 py-3 text-xs space-y-1">
                    <!-- OSM data or manual override -->
                    <template v-if="!editing[r.id]">
                      <a
                        v-if="r._website ?? r.website"
                        :href="r._website ?? r.website"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1 text-blue-600 hover:underline truncate max-w-[180px]"
                      >
                        <i class="fa-solid fa-globe"></i> {{ r._website ?? r.website }}
                      </a>
                      <a
                        v-if="r._facebook ?? r.facebook"
                        :href="r._facebook ?? r.facebook"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1 text-blue-600 hover:underline"
                      >
                        <i class="fa-brands fa-facebook"></i> Facebook
                      </a>
                      <span
                        v-if="!r._website && !r.website && !r._facebook && !r.facebook"
                        class="text-gray-300 italic text-xs"
                        >{{ t('landlord.shopsearch.none') }}</span
                      >
                    </template>
                    <!-- Edit mode -->
                    <template v-else>
                      <input
                        v-model="editData[r.id].website"
                        type="url"
                        placeholder="https://..."
                        class="w-full px-2 py-1 border rounded text-xs mb-1"
                      />
                      <input
                        v-model="editData[r.id].facebook"
                        type="url"
                        :placeholder="t('landlord.shopsearch.facebook_url')"
                        class="w-full px-2 py-1 border rounded text-xs"
                      />
                    </template>
                  </td>
                  <td class="px-4 py-3 text-xs">
                    <template v-if="!editing[r.id]">
                      <a
                        v-if="r._email ?? r.email"
                        :href="`mailto:${r._email ?? r.email}`"
                        class="text-blue-600 hover:underline"
                        >{{ r._email ?? r.email }}</a
                      >
                      <span v-else class="text-gray-300 italic">{{ t('landlord.shopsearch.none') }}</span>
                    </template>
                    <input
                      v-else
                      v-model="editData[r.id].email"
                      type="email"
                      placeholder="email@..."
                      class="w-full px-2 py-1 border rounded text-xs"
                    />
                  </td>
                  <td class="px-4 py-3 text-xs text-gray-700">
                    <template v-if="!editing[r.id]">
                      {{ r._phone ?? r.phone ?? '—' }}
                    </template>
                    <input
                      v-else
                      v-model="editData[r.id].phone"
                      type="text"
                      placeholder="+48..."
                      class="w-full px-2 py-1 border rounded text-xs"
                    />
                  </td>
                  <td class="px-4 py-3 text-xs whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                      <!-- Google search -->
                      <a
                        :href="`https://www.google.com/search?q=${encodeURIComponent(r.name + ' ' + foundCity + ' sklep')}`"
                        target="_blank"
                        rel="noopener"
                        class="text-orange-600 hover:text-orange-800 font-medium"
                      >
                        <i class="fa-solid fa-magnifying-glass"></i> Google
                      </a>
                      <!-- Google Maps -->
                      <a
                        v-if="r.maps_url"
                        :href="r.maps_url"
                        target="_blank"
                        rel="noopener"
                        class="text-green-600 hover:text-green-800 font-medium"
                      >
                        <i class="fa-solid fa-location-dot"></i> Maps
                      </a>
                      <!-- searching indicator -->
                      <span v-if="autoLoading[r.id]" class="text-gray-400 text-xs"
                        ><i class="fa-solid fa-magnifying-glass"></i> {{ t('landlord.shopsearch.searching') }}</span
                      >
                      <!-- Edit / Save -->
                      <button
                        v-if="!editing[r.id]"
                        @click="startEdit(r)"
                        class="text-blue-600 hover:text-blue-800 font-medium text-left"
                      >
                        <i class="fa-solid fa-pen"></i> {{ t('common.edit') }}
                      </button>
                      <template v-else>
                        <button @click="saveEdit(r)" class="text-green-600 hover:text-green-800 font-medium text-left">
                          <i class="fa-solid fa-check"></i> {{ t('common.save') }}
                        </button>
                        <button @click="cancelEdit(r.id)" class="text-gray-400 hover:text-gray-600 text-left">
                          <i class="fa-solid fa-xmark"></i> {{ t('common.cancel') }}
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-if="filtered.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                  {{ contactOnly ? t('common.no_results_with_data_in_osm') : t('client.shop.search.no_results') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import axios from 'axios'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const city = ref('')
const radius = ref('5000')
const loading = ref(false)
const error = ref(null)
const results = ref(null)
const foundCity = ref('')
const contactOnly = ref(false)

// Per-row edit state
const editing = reactive({})
const editData = reactive({})
const autoLoading = reactive({})

const radiusLabel = computed(() => {
  const map = { 1000: '1 km', 2000: '2 km', 5000: '5 km', 10000: '10 km', 20000: '20 km', 50000: '50 km' }
  return map[radius.value] ?? radius.value
})

const withContact = computed(
  () => results.value?.filter((r) => r.has_contact || r._website || r._facebook || r._email).length ?? 0,
)

const filtered = computed(() => {
  if (!results.value) return []
  if (!contactOnly.value) return results.value
  return results.value.filter((r) => r.has_contact || r._website || r._facebook || r._email || r._phone)
})

const startEdit = (r) => {
  editData[r.id] = {
    website: r._website ?? r.website ?? '',
    facebook: r._facebook ?? r.facebook ?? '',
    email: r._email ?? r.email ?? '',
    phone: r._phone ?? r.phone ?? '',
  }
  editing[r.id] = true
}

const saveEdit = (r) => {
  const d = editData[r.id]
  r._website = d.website || null
  r._facebook = d.facebook || null
  r._email = d.email || null
  r._phone = d.phone || null
  editing[r.id] = false
}

const cancelEdit = (id) => {
  editing[id] = false
}

// Token to cancel sequential search when a new search starts
let searchToken = 0

const autoFind = async (r) => {
  autoLoading[r.id] = true
  try {
    const res = await axios.get(route('landlord.shop-search.find-contact'), {
      params: { name: r.name, city: foundCity.value },
    })
    const d = res.data
    if (d.website) r._website = d.website
    if (d.facebook) r._facebook = d.facebook
    if (d.email) r._email = d.email
  } catch {
    // silently skip on error
  } finally {
    autoLoading[r.id] = false
  }
}

const autoFindSequential = async (items, token) => {
  for (let i = 0; i < items.length; i++) {
    if (searchToken !== token) return // new search started, abort
    await autoFind(items[i])
    if (i < items.length - 1 && searchToken === token) {
      await new Promise((resolve) => setTimeout(resolve, 900))
    }
  }
}

const doSearch = async () => {
  if (!city.value.trim() || loading.value) return
  loading.value = true
  error.value = null
  results.value = null
  searchToken++ // cancel any in-progress sequential search
  const token = searchToken

  try {
    const res = await axios.get(route('landlord.shop-search.search'), {
      params: { city: city.value, radius: radius.value },
    })
    results.value = res.data.results
    foundCity.value = city.value
    // Auto-find contact sequentially (900ms apart) for shops missing data
    const toSearch = results.value.filter((r) => !r.website && !r.facebook && !r.email)
    autoFindSequential(toSearch, token)
  } catch (e) {
    error.value = e.response?.data?.error ?? t('common.something_unexpected_went_wrong')
  } finally {
    loading.value = false
  }
}
</script>
