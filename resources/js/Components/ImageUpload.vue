<template>
  <div class="space-y-2">
    <!-- Drop zone / current image -->
    <div
      class="relative rounded-lg border-2 border-dashed transition-colors"
      :class="modelValue ? 'border-gray-200 bg-gray-50' : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50'"
    >
      <!-- Preview -->
      <div v-if="modelValue" class="relative group">
        <img
          :src="modelValue"
          :alt="t('common.preview')"
          :class="['rounded-lg', previewClass || 'w-full h-32 object-cover']"
        />
        <div
          class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3"
        >
          <label
            class="cursor-pointer bg-white text-gray-700 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
          >
            {{ t('components.imageupload.change') }}
            <input type="file" accept="image/*" class="hidden" @change="handleFile" :disabled="uploading" />
          </label>
          <button
            type="button"
            @click="$emit('update:modelValue', '')"
            class="bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
          >
            {{ t('common.delete') }}
          </button>
        </div>
      </div>

      <!-- Upload area (no image) -->
      <label v-else class="flex flex-col items-center justify-center py-8 cursor-pointer">
        <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
        <span class="text-sm font-medium text-gray-600">{{ t('components.imageupload.click_to_upload_a_photo') }}</span>
        <span class="text-xs text-gray-400 mt-1">{{ t('components.imageupload.png_jpg_webp_5_mb_max') }}</span>
        <input type="file" accept="image/*" class="hidden" @change="handleFile" :disabled="uploading" />
      </label>
    </div>

    <!-- Uploading indicator -->
    <div v-if="uploading" class="flex items-center gap-2 text-sm text-blue-600">
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
      </svg>
      {{ t('components.imageupload.uploading') }}
    </div>

    <!-- Error -->
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

    <!-- Hint -->
    <p v-if="hint && !error" class="text-xs text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: String, required: true },
  hint: { type: String, default: '' },
  previewClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const uploading = ref(false)
const error = ref('')

const handleFile = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  error.value = ''
  uploading.value = true

  const formData = new FormData()
  formData.append('file', file)
  formData.append('field', props.field)

  try {
    const res = await axios.post(route('tenant.manager.settings.upload'), formData)
    emit('update:modelValue', res.data.url)
  } catch (e) {
    error.value = e.response?.data?.message || t('common.the_file_could_not_be_uploaded')
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}
</script>
