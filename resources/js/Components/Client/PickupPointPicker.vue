<template>
  <div class="bg-white rounded-2xl shadow p-6">
    <h2 class="text-xl font-semibold mb-1">{{ t('client.pickuppointpicker.choose_a_parcel_locker') }}</h2>
    <p class="text-sm text-gray-500 mb-4">{{ t('client.pickuppointpicker.the_parcel_waits_in_the_locker') }}</p>

    <div class="flex gap-2">
      <input
        v-model="query"
        type="text"
        :placeholder="t('client.pickuppointpicker.city_e_g_warsaw')"
        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
        @keyup.enter="search"
      />
      <button type="button" class="btn btn--dark btn--compact" :disabled="loading || !query.trim()" @click="search">
        {{ loading ? t('client.pickuppointpicker.searching') : t('common.search') }}
      </button>
    </div>

    <p v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</p>

    <p v-else-if="searched && !points.length" class="text-sm text-gray-500 mt-3">
      {{ t('client.pickuppointpicker.we_found_no_parcel_lockers_in') }}
    </p>

    <div v-else-if="points.length" class="mt-4 max-h-80 overflow-y-auto space-y-2 pr-1">
      <label
        v-for="point in points"
        :key="point.code"
        class="flex items-start gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
        :class="
          point.code === modelValue?.code ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-200'
        "
      >
        <input
          type="radio"
          class="mt-1 accent-indigo-600"
          :value="point.code"
          :checked="point.code === modelValue?.code"
          @change="select(point)"
        />
        <span class="flex-1">
          <span class="block font-semibold text-gray-900">{{ point.code }}</span>
          <span class="block text-sm text-gray-600">{{ point.street }}, {{ point.postCode }} {{ point.city }}</span>
          <span v-if="point.description" class="block text-xs text-gray-400 mt-0.5">{{ point.description }}</span>
        </span>
      </label>
    </div>

    <p v-if="modelValue?.code" class="text-sm text-gray-700 mt-4">
      {{ t('client.pickuppointpicker.chosen_locker') }} <strong>{{ modelValue.code }}</strong> —
      {{ modelValue.data?.street }},
      {{ modelValue.data?.city }}
    </p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  // { code, data } of the locker already chosen, or null.
  modelValue: { type: Object, default: null },
  shippingMethodId: { type: [Number, String], required: true },
  // The city typed into the delivery address, used as the first guess.
  city: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const query = ref(props.city)
const points = ref([])
const loading = ref(false)
const searched = ref(false)
const error = ref('')

// A locker chosen in one town is not a locker in another, so switching the
// delivery city clears the choice rather than carrying it silently forward.
watch(
  () => props.city,
  (city) => {
    if (!query.value.trim() && city) query.value = city
  },
)

watch(
  () => props.shippingMethodId,
  () => {
    points.value = []
    searched.value = false
    emit('update:modelValue', null)
  },
)

async function search() {
  const city = query.value.trim()
  if (!city) return

  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(route('tenant.checkout.pickup-points'), {
      params: { shipping_method_id: props.shippingMethodId, city },
    })
    points.value = data.points ?? []
    searched.value = true
  } catch {
    error.value = t('common.the_list_of_parcel_lockers_could')
    points.value = []
  } finally {
    loading.value = false
  }
}

function select(point) {
  emit('update:modelValue', { code: point.code, data: point })
}
</script>
