<template>
  <ManagerLayout :title="t('manager.suppliers.index.suppliers')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.suppliers.index.suppliers') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.suppliers.index.manage_suppliers_and_contacts') }}</p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.suppliers.index.add_supplier') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.suppliers.index.company') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('landlord.shopsearch.email') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.phone') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.suppliers.index.contact_person') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.active') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold text-gray-900">{{ s.company_name }}</td>
              <td class="px-4 py-3">
                <a :href="`mailto:${s.email}`" class="text-blue-600 hover:underline text-xs">{{ s.email ?? '—' }}</a>
              </td>
              <td class="px-4 py-3 text-gray-600 text-xs">{{ s.phone ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-600 text-xs">{{ s.contact_person ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ s.is_active ? t('common.active') : t('common.inactive') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(s)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(s)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!suppliers.data?.length">
              <td colspan="6" class="text-center py-12 text-gray-400">
                {{ t('manager.suppliers.index.no_suppliers') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white shadow rounded-lg w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_supplier') : t('common.new_supplier') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.suppliers.index.company_name')
            }}</label>
            <input
              v-model="form.company_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.email') }}</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
            <input v-model="form.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.suppliers.index.contact_person')
            }}</label>
            <input
              v-model="form.contact_person"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.purchaseorders.create.notes')
            }}</label>
            <textarea
              v-model="form.notes"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            ></textarea>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="sup_active" class="h-4 w-4 text-blue-600 rounded" />
            <label for="sup_active" class="text-sm font-medium text-gray-700">{{ t('common.active') }}</label>
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
              {{ editing ? t('common.save') : t('common.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  suppliers: { type: Object, required: true },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({ company_name: '', email: '', phone: '', contact_person: '', notes: '', is_active: true })

const openModal = (s = null) => {
  editing.value = s
  if (s) {
    form.company_name = s.company_name ?? ''
    form.email = s.email ?? ''
    form.phone = s.phone ?? ''
    form.contact_person = s.contact_person ?? ''
    form.notes = s.notes ?? ''
    form.is_active = s.is_active ?? true
  } else {
    form.reset()
    form.is_active = true
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.suppliers.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.suppliers.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (s) => {
  if (!confirm(t('manager.suppliers.index.delete_the_supplier_a', { a: s.company_name }))) return
  router.delete(route('tenant.manager.suppliers.destroy', s.id))
}
</script>
