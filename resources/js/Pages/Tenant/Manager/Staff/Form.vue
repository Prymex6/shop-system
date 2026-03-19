<template>
  <ManagerLayout :title="user ? t('common.edit_staff_member') : t('common.add_staff_member')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ user ? t('common.edit_staff_member') : t('common.add_staff_member') }}
        </h1>
      </div>

      <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name_2') }}</label>
            <input
              v-model="form.name"
              name="name"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('pages.landing.email') }}</label>
            <input
              v-model="form.email"
              name="email"
              type="email"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >{{ t('common.password_label') }} {{ user ? t('common.leave_empty_to_keep_it_as') : '*' }}</label
            >
            <input
              v-model="form.password"
              name="password"
              type="password"
              :required="!user"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.staff.form.role') }}</label>
            <select
              v-model="form.role"
              name="role"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">{{ t('common.choose_a_role') }}</option>
              <option value="manager">Manager</option>
              <option value="fulfillment">{{ t('manager.staff.form.staff_order_fulfilment') }}</option>
              <option value="warehouse">{{ t('pages.landing.warehouse_staff') }}</option>
            </select>
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
            <input
              v-model="form.phone"
              name="phone"
              type="tel"
              placeholder="123 456 789"
              @blur="form.phone = formatPhone(form.phone)"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
          </div>

          <div v-if="user" class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              name="is_active"
              class="rounded border-gray-300 text-blue-600"
            />
            <label for="is_active" class="ml-2 text-sm text-gray-700">{{
              t('manager.staff.form.account_active')
            }}</label>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium"
            >
              {{ form.processing ? 'Zapisywanie...' : user ? t('common.save_changes') : t('common.add_staff_member') }}
            </button>
            <Link
              href="/manager/staff"
              class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium"
            >
              {{ t('common.cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  user: Object,
})

const form = useForm({
  name: props.user?.name ?? '',
  email: props.user?.email ?? '',
  password: '',
  role: props.user?.role ?? '',
  phone: props.user?.phone ?? '',
  is_active: props.user?.is_active ?? true,
})

const submit = () => {
  if (props.user) {
    form.put(`/manager/staff/${props.user.id}`)
  } else {
    form.post('/manager/staff')
  }
}
</script>
