<template>
  <LandlordLayout :title="plan ? t('common.edit_plan') : t('common.new_plan')">
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">
          {{ plan ? t('common.edit_plan') : t('common.new_subscription_plan') }}
        </h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.plans.form.plan_name') }}</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('landlord.plans.form.price_pln_year')
            }}</label>
            <input
              v-model="form.price"
              type="number"
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              placeholder="np. 480"
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('landlord.plans.form.leave_empty_if_the_plan_is') }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('landlord.plans.form.max_orders_per_month')
            }}</label>
            <input
              v-model="form.max_orders_per_month"
              type="number"
              min="0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              :placeholder="t('landlord.plans.form.leave_empty_for_unlimited')"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('landlord.plans.form.max_products')
              }}</label>
              <input
                v-model="form.max_products"
                type="number"
                min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                :placeholder="t('landlord.plans.form.unlimited')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('landlord.plans.form.max_staff_accounts')
              }}</label>
              <input
                v-model="form.max_staff"
                type="number"
                min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                :placeholder="t('landlord.plans.form.unlimited')"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('landlord.plans.form.max_disk_space_mb')
            }}</label>
            <input
              v-model="form.max_storage_mb"
              type="number"
              min="0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              :placeholder="t('landlord.plans.form.unlimited')"
            />
            <p class="text-xs text-amber-600 mt-1">
              {{ t('landlord.plans.form.recorded_but_not_yet_enforced_automatically') }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{
              t('landlord.plans.form.features_list')
            }}</label>
            <div v-for="(feature, i) in form.features" :key="i" class="flex gap-2 mb-2">
              <input
                v-model="form.features[i]"
                type="text"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                :placeholder="t('landlord.plans.form.e_g_text_notifications')"
              />
              <button type="button" @click="form.features.splice(i, 1)" class="text-red-500 hover:text-red-700 px-2">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <button type="button" @click="form.features.push('')" class="text-blue-600 hover:text-blue-800 text-sm">
              {{ t('landlord.plans.form.add_feature') }}
            </button>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 text-blue-600" />
            <label for="is_active" class="text-sm text-gray-700">{{
              t('landlord.plans.form.plan_active_visible_to_customers')
            }}</label>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
              {{ plan ? t('common.save_changes') : t('common.create_plan') }}
            </button>
            <Link
              :href="route('landlord.plans.index')"
              class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg"
            >
              {{ t('common.cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  plan: { type: Object, default: null },
})

const errors = usePage().props.errors || {}

const form = reactive({
  name: props.plan?.name ?? '',
  price: props.plan?.price ?? '',
  max_orders_per_month: props.plan?.max_orders_per_month ?? '',
  max_products: props.plan?.max_products ?? '',
  max_staff: props.plan?.max_staff ?? '',
  max_storage_mb: props.plan?.max_storage_mb ?? '',
  features: props.plan?.features ? [...props.plan.features] : [],
  is_active: props.plan?.is_active ?? true,
})

function submit() {
  const data = {
    ...form,
    price: form.price === '' ? null : form.price,
    max_orders_per_month: form.max_orders_per_month === '' ? null : form.max_orders_per_month,
    max_products: form.max_products === '' ? null : form.max_products,
    max_staff: form.max_staff === '' ? null : form.max_staff,
    max_storage_mb: form.max_storage_mb === '' ? null : form.max_storage_mb,
    features: form.features.filter((f) => f.trim()),
  }

  if (props.plan) {
    router.put(route('landlord.plans.update', props.plan.id), data)
  } else {
    router.post(route('landlord.plans.store'), data)
  }
}
</script>
