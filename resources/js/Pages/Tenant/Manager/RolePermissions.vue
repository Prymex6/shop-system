<template>
  <ManagerLayout :title="t('common.role_permissions')">
    <div class="max-w-4xl space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.role_permissions') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('manager.rolepermissions.set_what_each_staff_role_is') }}</p>
      </div>

      <!-- Manager info banner -->
      <div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-3 text-sm text-blue-700 flex items-center gap-3">
        <i class="fa-solid fa-circle-info text-blue-400 shrink-0"></i>
        <span
          >{{ t('manager.rolepermissions.role') }} <strong>Manager</strong>
          {{ t('manager.rolepermissions.always_holds_every_permission_there_is') }}</span
        >
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form @submit.prevent="save">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-5 py-3 text-left font-semibold text-gray-700">
                  {{ t('manager.rolepermissions.permission') }}
                </th>
                <th v-for="role in roles" :key="role" class="px-4 py-3 text-center font-semibold text-gray-700">
                  <div class="flex flex-col items-center gap-1">
                    <span>{{ roleLabel(role) }}</span>
                    <span
                      v-if="role === 'manager'"
                      class="text-[10px] font-normal text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full"
                      >{{ t('manager.rolepermissions.all') }}</span
                    >
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(label, perm) in permissions" :key="perm" class="hover:bg-gray-50">
                <td class="px-5 py-3 text-gray-800">{{ label }}</td>
                <td v-for="role in roles" :key="role" class="px-4 py-3 text-center">
                  <input
                    type="checkbox"
                    v-model="form[role][perm]"
                    :disabled="role === 'manager'"
                    class="w-4 h-4 border-gray-300 rounded"
                    :class="
                      role === 'manager'
                        ? 'accent-blue-400 cursor-not-allowed opacity-60'
                        : 'text-blue-600 cursor-pointer'
                    "
                    :title="role === 'manager' ? 'Manager zawsze ma to uprawnienie' : ''"
                  />
                </td>
              </tr>
            </tbody>
          </table>

          <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
            <button
              type="submit"
              :disabled="processing"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold text-sm rounded-lg transition-colors"
            >
              {{ processing ? 'Zapisywanie...' : t('common.save_permissions') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  permissions: Object,
  roles: Array,
  currentPermissions: Object,
})

const processing = ref(false)

// Build reactive form object: form[role][permission] = bool
// Manager always has all permissions (hardcoded in middleware), so pre-check all
const form = ref({})
for (const role of props.roles) {
  form.value[role] = {}
  for (const perm of Object.keys(props.permissions)) {
    form.value[role][perm] = role === 'manager' ? true : (props.currentPermissions[role] || []).includes(perm)
  }
}

const save = () => {
  processing.value = true
  router.put(
    route('tenant.manager.role-permissions.update'),
    { permissions: form.value },
    {
      onFinish: () => {
        processing.value = false
      },
    },
  )
}

const roleLabel = (role) => {
  const labels = {
    manager: 'Manager',
    fulfillment: t('manager.rolepermissions.staff_fulfilment'),
    warehouse: t('pages.landing.warehouse_staff'),
  }
  return labels[role] || role
}
</script>
