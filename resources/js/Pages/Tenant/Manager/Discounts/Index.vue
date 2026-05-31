<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  codes: Array,
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingCode = ref(null)

const createForm = useForm({
  code: '',
  type: 'percentage',
  value: '',
  min_order_value: '',
  max_uses: '',
  valid_from: '',
  valid_until: '',
  is_active: true,
})

const editForm = useForm({
  code: '',
  type: 'percentage',
  value: '',
  min_order_value: '',
  max_uses: '',
  valid_from: '',
  valid_until: '',
  is_active: true,
})

const openCreateModal = () => {
  createForm.reset()
  showCreateModal.value = true
}

const openEditModal = (code) => {
  editingCode.value = code
  editForm.code = code.code
  editForm.type = code.type
  editForm.value = code.value
  editForm.min_order_value = code.min_order_value || ''
  editForm.max_uses = code.max_uses || ''
  editForm.valid_from = code.valid_from ? code.valid_from.substring(0, 16) : ''
  editForm.valid_until = code.valid_until ? code.valid_until.substring(0, 16) : ''
  editForm.is_active = code.is_active
  showEditModal.value = true
}

const submitCreate = () => {
  createForm.post(route('tenant.manager.discounts.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

const submitEdit = () => {
  editForm.put(route('tenant.manager.discounts.update', editingCode.value.id), {
    onSuccess: () => {
      showEditModal.value = false
      editForm.reset()
    },
  })
}

const deleteCode = (code) => {
  if (confirm(t('manager.discounts.index.are_you_sure_you_want_to', { a: code.code }))) {
    router.delete(route('tenant.manager.discounts.destroy', code.id))
  }
}

const toggleCode = (code) => {
  router.post(route('tenant.manager.discounts.toggle', code.id))
}

const getStatusBadgeClass = (code) => {
  if (!code.is_active) return 'bg-gray-500'

  const now = new Date()
  if (code.valid_until && new Date(code.valid_until) < now) return 'bg-red-500'
  if (code.valid_from && new Date(code.valid_from) > now) return 'bg-yellow-500'
  if (code.max_uses && code.used_count >= code.max_uses) return 'bg-red-500'

  return 'bg-green-500'
}

const getStatusText = (code) => {
  if (!code.is_active) return t('common.inactive')

  const now = new Date()
  if (code.valid_until && new Date(code.valid_until) < now) return t('common.expired_2')
  if (code.valid_from && new Date(code.valid_from) > now) return t('common.pending_2')
  if (code.max_uses && code.used_count >= code.max_uses) return 'Wykorzystany'

  return t('common.active')
}
</script>

<template>
  <ManagerLayout>
    <Head title="Kody Rabatowe" />

    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Kody Rabatowe</h1>
        <p class="mt-2 text-gray-600">{{ t('manager.discounts.index.manage_discount_codes_and_promotions') }}</p>
      </div>

      <!-- Create Button -->
      <div class="mb-6">
        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('manager.discounts.index.add_discount_code') }}
        </button>
      </div>

      <!-- Codes List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kod</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.value') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Wykorzystanie
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.discounts.index.validity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="codes.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.discounts.index.no_discount_codes_yet_add_the') }}
                </td>
              </tr>
              <tr v-for="code in codes" :key="code.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-gray-900 uppercase">{{ code.code }}</div>
                  <div v-if="code.min_order_value" class="text-xs text-gray-500">
                    Min. {{ code.min_order_value }} PLN
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span v-if="code.type === 'percentage'" class="text-sm text-gray-900">Procentowy</span>
                  <span v-else class="text-sm text-gray-900">Kwotowy</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-semibold text-blue-600">{{ code.formatted_value }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">
                    {{ code.used_count }} {{ code.max_uses ? `/ ${code.max_uses}` : '' }}
                  </div>
                  <div v-if="code.usage_percentage !== null" class="mt-1 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" :style="{ width: code.usage_percentage + '%' }"></div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div v-if="code.valid_from">Od: {{ code.valid_from }}</div>
                  <div v-if="code.valid_until">Do: {{ code.valid_until }}</div>
                  <div v-if="!code.valid_from && !code.valid_until">{{ t('common.unlimited') }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getStatusBadgeClass(code)"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full text-white"
                  >
                    {{ getStatusText(code) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button @click="toggleCode(code)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                    {{ code.is_active ? 'Dezaktywuj' : 'Aktywuj' }}
                  </button>
                  <button @click="openEditModal(code)" class="text-blue-600 hover:text-blue-900 mr-3">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="deleteCode(code)" class="text-red-600 hover:text-red-900">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h2 class="text-2xl font-bold mb-6">{{ t('manager.discounts.index.add_discount_code_2') }}</h2>

          <form @submit.prevent="submitCreate" class="space-y-4">
            <!-- Code -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kod *</label>
              <input
                v-model="createForm.code"
                name="code"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-lg uppercase"
                placeholder="np. PIZZA20"
              />
              <div v-if="createForm.errors.code" class="text-red-600 text-sm mt-1">
                {{ createForm.errors.code }}
              </div>
            </div>

            <!-- Type and Value -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Typ *</label>
                <select v-model="createForm.type" name="type" required class="w-full px-3 py-2 border rounded-lg">
                  <option value="percentage">Procentowy (%)</option>
                  <option value="fixed">Kwotowy (PLN)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.value_2') }}</label>
                <input
                  v-model="createForm.value"
                  name="value"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3 py-2 border rounded-lg"
                  :placeholder="createForm.type === 'percentage' ? 'np. 20' : 'np. 10.00'"
                />
                <div v-if="createForm.errors.value" class="text-red-600 text-sm mt-1">
                  {{ createForm.errors.value }}
                </div>
              </div>
            </div>

            <!-- Min Order Value and Max Uses -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.min_order_value')
                }}</label>
                <input
                  v-model="createForm.min_order_value"
                  name="min_order_value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border rounded-lg"
                  placeholder="np. 50.00"
                />
                <div v-if="createForm.errors.min_order_value" class="text-red-600 text-sm mt-1">
                  {{ createForm.errors.min_order_value }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.max_uses')
                }}</label>
                <input
                  v-model="createForm.max_uses"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border rounded-lg"
                  placeholder="Bez limitu"
                />
                <div v-if="createForm.errors.max_uses" class="text-red-600 text-sm mt-1">
                  {{ createForm.errors.max_uses }}
                </div>
              </div>
            </div>

            <!-- Valid From and Until -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.valid_from')
                }}</label>
                <input
                  v-model="createForm.valid_from"
                  type="datetime-local"
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <div v-if="createForm.errors.valid_from" class="text-red-600 text-sm mt-1">
                  {{ createForm.errors.valid_from }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.valid_until')
                }}</label>
                <input
                  v-model="createForm.valid_until"
                  type="datetime-local"
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <div v-if="createForm.errors.valid_until" class="text-red-600 text-sm mt-1">
                  {{ createForm.errors.valid_until }}
                </div>
              </div>
            </div>

            <!-- Active -->
            <div class="flex items-center">
              <input v-model="createForm.is_active" type="checkbox" id="create-active" class="mr-2" />
              <label for="create-active" class="text-sm font-medium text-gray-700">
                {{ t('manager.discounts.index.active_immediately') }}
              </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="createForm.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ t('manager.discounts.index.add_code') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h2 class="text-2xl font-bold mb-6">{{ t('manager.discounts.index.edit_discount_code') }}</h2>

          <form @submit.prevent="submitEdit" class="space-y-4">
            <!-- Code -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kod *</label>
              <input
                v-model="editForm.code"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-lg uppercase"
              />
              <div v-if="editForm.errors.code" class="text-red-600 text-sm mt-1">
                {{ editForm.errors.code }}
              </div>
            </div>

            <!-- Type and Value -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Typ *</label>
                <select v-model="editForm.type" required class="w-full px-3 py-2 border rounded-lg">
                  <option value="percentage">Procentowy (%)</option>
                  <option value="fixed">Kwotowy (PLN)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.value_2') }}</label>
                <input
                  v-model="editForm.value"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <div v-if="editForm.errors.value" class="text-red-600 text-sm mt-1">
                  {{ editForm.errors.value }}
                </div>
              </div>
            </div>

            <!-- Min Order Value and Max Uses -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.min_order_value')
                }}</label>
                <input
                  v-model="editForm.min_order_value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <div v-if="editForm.errors.min_order_value" class="text-red-600 text-sm mt-1">
                  {{ editForm.errors.min_order_value }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.max_uses')
                }}</label>
                <input v-model="editForm.max_uses" type="number" min="1" class="w-full px-3 py-2 border rounded-lg" />
                <div v-if="editForm.errors.max_uses" class="text-red-600 text-sm mt-1">
                  {{ editForm.errors.max_uses }}
                </div>
              </div>
            </div>

            <!-- Valid From and Until -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.valid_from')
                }}</label>
                <input v-model="editForm.valid_from" type="datetime-local" class="w-full px-3 py-2 border rounded-lg" />
                <div v-if="editForm.errors.valid_from" class="text-red-600 text-sm mt-1">
                  {{ editForm.errors.valid_from }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.discounts.index.valid_until')
                }}</label>
                <input
                  v-model="editForm.valid_until"
                  type="datetime-local"
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <div v-if="editForm.errors.valid_until" class="text-red-600 text-sm mt-1">
                  {{ editForm.errors.valid_until }}
                </div>
              </div>
            </div>

            <!-- Active -->
            <div class="flex items-center">
              <input v-model="editForm.is_active" type="checkbox" id="edit-active" class="mr-2" />
              <label for="edit-active" class="text-sm font-medium text-gray-700">
                {{ t('common.active') }}
              </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ t('common.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>
