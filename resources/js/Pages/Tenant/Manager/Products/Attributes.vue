<template>
  <ManagerLayout :title="t('manager.products.attributes.product_attributes')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.products.attributes.product_attributes') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.products.attributes.define_traits_such_as_colour_or') }}
          </p>
        </div>
        <button
          @click="openAttrModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.products.attributes.new_attribute') }}
        </button>
      </div>

      <div v-if="!localAttributes.length" class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
        {{ t('manager.products.attributes.no_attributes_yet_add_the_first') }}
      </div>

      <div v-else class="space-y-4">
        <div v-for="attr in localAttributes" :key="attr.id" class="bg-white shadow rounded-lg overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">{{ attr.name }}</h3>
            <div class="flex items-center gap-3">
              <button @click="openAttrModal(attr)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                {{ t('common.edit') }}
              </button>
              <button @click="destroyAttr(attr)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                {{ t('common.delete') }}
              </button>
            </div>
          </div>

          <div class="px-5 py-4">
            <div class="flex flex-wrap gap-2 mb-3">
              <span
                v-for="val in attr.values"
                :key="val.id"
                class="inline-flex items-center gap-2 bg-gray-100 rounded-full pl-1 pr-2 py-1 text-xs text-gray-700"
              >
                <span
                  v-if="val.color_hex"
                  class="w-3.5 h-3.5 rounded-full border border-gray-300 inline-block"
                  :style="{ backgroundColor: val.color_hex }"
                />
                {{ val.value }}
                <button @click="destroyValue(attr, val)" class="text-gray-400 hover:text-red-600 ml-1">&times;</button>
              </span>
              <span v-if="!attr.values.length" class="text-xs text-gray-400">{{
                t('manager.products.attributes.no_values')
              }}</span>
            </div>

            <form @submit.prevent="addValue(attr)" class="flex items-center gap-2">
              <input
                v-model="newValueForms[attr.id].value"
                type="text"
                :placeholder="t('manager.products.attributes.new_value_e_g_red')"
                class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
              />
              <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer shrink-0">
                <input v-model="newValueForms[attr.id].useColor" type="checkbox" class="h-3.5 w-3.5" />
                {{ t('manager.products.form.spec_colour') }}
              </label>
              <input
                v-if="newValueForms[attr.id].useColor"
                v-model="newValueForms[attr.id].color_hex"
                type="color"
                class="w-9 h-9 border border-gray-300 rounded-lg cursor-pointer"
              />
              <button
                type="submit"
                :disabled="!newValueForms[attr.id].value?.trim()"
                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-xs font-semibold disabled:opacity-40"
              >
                {{ t('common.add') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Attribute modal -->
    <div
      v-if="showAttrModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showAttrModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editingAttr ? t('common.edit_attribute') : t('common.new_attribute') }}
          </h3>
          <button @click="showAttrModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>
        <form @submit.prevent="submitAttr" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name') }}</label>
            <input
              v-model="attrForm.name"
              type="text"
              required
              :placeholder="t('manager.products.attributes.e_g_colour')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <p v-if="attrError" class="text-red-600 text-sm">{{ attrError }}</p>
          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="showAttrModal = false"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="attrSubmitting"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50"
            >
              {{ editingAttr ? t('common.save') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  attributes: { type: Array, default: () => [] },
})

const localAttributes = ref(props.attributes.map((a) => ({ ...a, values: a.values ?? [] })))

const newValueForms = reactive({})
localAttributes.value.forEach((a) => {
  newValueForms[a.id] = { value: '', useColor: false, color_hex: '#000000' }
})

const showAttrModal = ref(false)
const editingAttr = ref(null)
const attrSubmitting = ref(false)
const attrError = ref('')
const attrForm = ref({ name: '' })

const openAttrModal = (attr = null) => {
  editingAttr.value = attr
  attrError.value = ''
  attrForm.value = { name: attr?.name ?? '' }
  showAttrModal.value = true
}

const submitAttr = async () => {
  attrSubmitting.value = true
  attrError.value = ''
  try {
    if (editingAttr.value) {
      const { data } = await window.axios.put(
        route('tenant.manager.attributes.update', editingAttr.value.id),
        attrForm.value,
      )
      const idx = localAttributes.value.findIndex((a) => a.id === editingAttr.value.id)
      if (idx !== -1) localAttributes.value[idx].name = data.attribute.name
    } else {
      const { data } = await window.axios.post(route('tenant.manager.attributes.store'), attrForm.value)
      const attr = { ...data.attribute, values: [] }
      localAttributes.value.push(attr)
      newValueForms[attr.id] = { value: '', useColor: false, color_hex: '#000000' }
    }
    showAttrModal.value = false
  } catch (e) {
    attrError.value = e?.response?.data?.message ?? t('common.the_attribute_could_not_be_saved')
  } finally {
    attrSubmitting.value = false
  }
}

const destroyAttr = async (attr) => {
  if (!confirm(t('manager.products.attributes.delete_the_attribute_a_and_all', { a: attr.name }))) return
  await window.axios.delete(route('tenant.manager.attributes.destroy', attr.id))
  localAttributes.value = localAttributes.value.filter((a) => a.id !== attr.id)
}

const addValue = async (attr) => {
  const form = newValueForms[attr.id]
  if (!form.value?.trim()) return
  const { data } = await window.axios.post(route('tenant.manager.attributes.values.store', attr.id), {
    value: form.value.trim(),
    color_hex: form.useColor ? form.color_hex : null,
  })
  attr.values.push(data.value)
  newValueForms[attr.id] = { value: '', useColor: false, color_hex: '#000000' }
}

const destroyValue = async (attr, val) => {
  await window.axios.delete(route('tenant.manager.attributes.values.destroy', [attr.id, val.id]))
  attr.values = attr.values.filter((v) => v.id !== val.id)
}
</script>
