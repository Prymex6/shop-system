<template>
  <ManagerLayout :title="t('layout.managerlayout.staff')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('layout.managerlayout.staff') }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ t('manager.staff.index.manage_the_shop_s_staff_accounts') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
        >
          <i class="fa-solid fa-plus mr-2"></i>
          {{ t('manager.staff.index.add_staff_member') }}
        </button>
      </div>

      <!-- Staff List -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('pages.landing.staff_member') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.email') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.phone') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.rolepermissions.role') }}
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
              <tr v-for="staff in staffList.data" :key="staff.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div
                      class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                      :class="roleAvatarClass(staff.role)"
                    >
                      <i :class="`fa-solid ${roleIcon(staff.role)}`"></i>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ staff.name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ staff.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ staff.phone || '-' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="roleClass(staff.role)"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ roleLabel(staff.role) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="staff.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ staff.is_active ? t('common.active') : t('common.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button @click="openModal(staff)" class="text-blue-600 hover:text-blue-900">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="deleteStaff(staff)" class="text-red-600 hover:text-red-900">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>

              <tr v-if="staffList.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.staff.index.no_staff') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="staffList.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ t('common.showing') }} <span class="font-medium">{{ staffList.from }}</span> {{ t('common.to') }}
              <span class="font-medium">{{ staffList.to }}</span> z
              <span class="font-medium">{{ staffList.total }}</span> {{ t('common.results') }}
            </div>
            <div class="flex space-x-2">
              <template v-for="link in staffList.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  ]"
                  v-html="link.label"
                ></Link>
                <span
                  v-else
                  :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                    'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md opacity-50 cursor-not-allowed',
                  ]"
                  v-html="link.label"
                ></span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Staff Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ editingStaff ? t('common.edit_staff_member_2') : t('common.new_staff_member') }}
        </h3>
        <form @submit.prevent="saveStaff">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ t('common.full_name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">{{ t('common.email_2') }}</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">
                {{ t('common.password_label') }} {{ editingStaff ? t('common.leave_empty_to_keep_it_as_2') : '*' }}
              </label>
              <input
                v-model="form.password"
                type="password"
                :required="!editingStaff"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">{{ t('common.phone') }}</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="123 456 789"
                @blur="form.phone = formatPhone(form.phone)"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">{{ t('manager.staff.form.role') }}</label>
              <select
                v-model="form.role"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">{{ t('common.choose_a_role') }}</option>
                <option value="manager">Manager</option>
                <option value="fulfillment">{{ t('manager.rolepermissions.staff_fulfilment') }}</option>
                <option value="warehouse">{{ t('pages.landing.warehouse_staff') }}</option>
              </select>
              <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
            </div>

            <div v-if="editingStaff" class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                id="staff-active"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
              <label for="staff-active" class="ml-2 block text-sm text-gray-900">
                {{ t('common.active') }}
              </label>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ form.processing ? 'Zapisywanie...' : t('common.save') }}
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
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  staffList: Object,
})

const showModal = ref(false)
const editingStaff = ref(null)

const form = useForm({
  name: '',
  email: '',
  password: '',
  phone: '',
  role: '',
  is_active: true,
})

const roleIcon = (role) => {
  const icons = {
    manager: 'fa-user-tie',
    fulfillment: 'fa-box-open',
    warehouse: 'fa-warehouse',
  }
  return icons[role] || 'fa-user'
}

const roleLabel = (role) => {
  const labels = {
    manager: 'Manager',
    fulfillment: t('pages.landing.staff_member'),
    warehouse: t('pages.landing.warehouse_staff'),
  }
  return labels[role] || role
}

const roleClass = (role) => {
  const classes = {
    manager: 'bg-purple-100 text-purple-800',
    fulfillment: 'bg-blue-100 text-blue-800',
    warehouse: 'bg-amber-100 text-amber-800',
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const roleAvatarClass = (role) => {
  const classes = {
    manager: 'bg-purple-100 text-purple-600',
    fulfillment: 'bg-blue-100 text-blue-600',
    warehouse: 'bg-amber-100 text-amber-600',
  }
  return classes[role] || 'bg-gray-200 text-gray-600'
}

const openModal = (staff = null) => {
  if (staff) {
    editingStaff.value = staff
    form.name = staff.name
    form.email = staff.email
    form.password = ''
    form.phone = staff.phone || ''
    form.role = staff.role
    form.is_active = staff.is_active
  } else {
    editingStaff.value = null
    form.reset()
    form.clearErrors()
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingStaff.value = null
  form.reset()
  form.clearErrors()
}

const saveStaff = () => {
  if (editingStaff.value) {
    form.put(route('tenant.manager.staff.update', editingStaff.value.id), {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('tenant.manager.staff.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

const deleteStaff = (staff) => {
  if (confirm(t('manager.staff.index.are_you_sure_you_want_to', { a: staff.name }))) {
    router.delete(route('tenant.manager.staff.destroy', staff.id))
  }
}
</script>
