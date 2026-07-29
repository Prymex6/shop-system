<template>
  <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold">{{ t('manager.productimportmodal.import_products_csv') }}</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>

        <!-- Result info -->
        <div v-if="result" class="mb-4 p-4 bg-gray-50 rounded-lg space-y-2">
          <div class="flex items-center gap-2 text-green-700">
            <span class="font-semibold">{{ t('manager.productimportmodal.imported') }}</span>
            <span>{{ t('common.a_products_count', { a: result.imported }) }}</span>
          </div>
          <div class="flex items-center gap-2 text-yellow-700">
            <span class="font-semibold">{{ t('manager.productimportmodal.skipped') }}</span>
            <span>{{ result.skipped }} wierszy</span>
          </div>
          <div v-if="result.errors?.length" class="text-red-700">
            <span class="font-semibold">{{ t('common.errors_a', { a: result.errors.length }) }}</span>
            <ul class="mt-1 list-disc list-inside text-xs space-y-1">
              <li v-for="(err, idx) in result.errors" :key="idx">{{ err }}</li>
            </ul>
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.productimportmodal.csv_file')
            }}</label>
            <input
              ref="fileInput"
              type="file"
              accept=".csv"
              required
              @change="onFileChange"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p class="text-xs text-gray-500 mt-1">
              Kolumny: name, sku, price, compare_price, description, category, stock_quantity, is_published
            </p>
          </div>

          <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="!selectedFile || uploading"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ uploading ? 'Importowanie...' : 'Importuj' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'imported'])

const fileInput = ref(null)
const selectedFile = ref(null)
const uploading = ref(false)
const result = ref(null)
const error = ref(null)

function onFileChange(e) {
  selectedFile.value = e.target.files[0] ?? null
  result.value = null
  error.value = null
}

async function submit() {
  if (!selectedFile.value) return
  uploading.value = true
  error.value = null
  result.value = null

  const data = new FormData()
  data.append('file', selectedFile.value)

  try {
    const res = await axios.post(route('tenant.manager.products.import'), data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    result.value = res.data
    emit('imported', res.data)
    if (fileInput.value) fileInput.value.value = ''
    selectedFile.value = null
  } catch (e) {
    error.value = e.response?.data?.message ?? t('common.something_went_wrong_during_the_import')
  } finally {
    uploading.value = false
  }
}
</script>
