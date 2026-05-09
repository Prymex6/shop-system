<template>
  <ManagerLayout :title="t('manager.supply.index.warehouses')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.supply.index.warehouses') }}</h1>
          <p class="text-sm text-gray-500 mt-1">{{ t('manager.warehouses.index.manage_warehouse_locations') }}</p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.warehouses.index.new_warehouse') }}
        </button>
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.city') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.address') }}
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
            <tr v-for="wh in warehouses.data" :key="wh.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold text-gray-900">{{ wh.name }}</td>
              <td class="px-4 py-3 text-gray-600">{{ wh.city ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ wh.address ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  :class="wh.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ wh.is_active ? t('common.active') : t('common.inactive') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <Link
                    :href="route('tenant.manager.warehouses.stock', wh.id)"
                    class="text-blue-600 hover:text-blue-900 text-xs font-medium"
                  >
                    {{ t('manager.inventory.index.stock') }}
                  </Link>
                  <button @click="openModal(wh)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(wh)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!warehouses.data?.length">
              <td colspan="5" class="text-center py-12 text-gray-400">
                {{ t('manager.warehouses.index.no_warehouses') }}
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
            {{ editing ? t('common.edit_warehouse') : t('common.new_warehouse') }}
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.city') }}</label>
            <input v-model="form.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.address') }}</label>
            <input
              v-model="form.address"
              type="text"
              :placeholder="t('manager.warehouses.index.1_magazynowa_st_00_001_warsaw')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="wh_active" class="h-4 w-4 text-blue-600 rounded" />
            <label for="wh_active" class="text-sm font-medium text-gray-700">{{ t('common.active') }}</label>
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
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  warehouses: { type: Object, required: true },
})

const showModal = ref(false)
const editing = ref(null)

const form = useForm({ name: '', city: '', address: '', is_active: true })

const openModal = (wh = null) => {
  editing.value = wh
  if (wh) {
    form.name = wh.name ?? ''
    form.city = wh.city ?? ''
    form.address = wh.address ?? ''
    form.is_active = wh.is_active ?? true
  } else {
    form.reset()
    form.is_active = true
  }
  showModal.value = true
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.warehouses.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.warehouses.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (wh) => {
  if (!confirm(t('manager.warehouses.index.delete_the_warehouse_a', { a: wh.name }))) return
  router.delete(route('tenant.manager.warehouses.destroy', wh.id))
}
</script>
