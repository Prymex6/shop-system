<template>
  <ManagerLayout :title="t('common.delivery_zones')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.delivery_zones') }}</h1>
        <div class="flex gap-2">
          <Link
            :href="route('tenant.manager.shipping.index')"
            class="border border-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition"
          >
            {{ t('common.delivery_methods') }}
          </Link>
          <button
            @click="showForm = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
          >
            {{ t('manager.shipping.zones.new_zone') }}
          </button>
        </div>
      </div>

      <div v-if="zones.length === 0" class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
        {{ t('manager.shipping.zones.no_delivery_zones_defined') }}
      </div>

      <div v-else class="bg-white shadow rounded-lg divide-y divide-gray-50">
        <div v-for="zone in zones" :key="zone.id" class="px-5 py-4 flex items-center gap-4">
          <div class="flex-1">
            <span class="font-semibold text-gray-900">{{ zone.name }}</span>
            <span v-if="zone.is_default" class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{
              t('common.default')
            }}</span>
          </div>
          <span class="text-sm text-gray-500">{{ zone.methods?.length ?? 0 }} metod</span>
          <button @click="deleteZone(zone)" class="text-sm text-red-500 hover:text-red-700 font-medium px-2 py-1">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>

      <!-- Add zone form -->
      <div v-if="showForm" class="bg-white shadow rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.shipping.zones.new_delivery_zone') }}</h2>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name') }}</label>
          <input
            v-model="form.name"
            name="name"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
            :placeholder="t('manager.shipping.zones.e_g_poland')"
          />
          <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="form.is_default" type="checkbox" id="is_default" />
          <label for="is_default" class="text-sm text-gray-700">{{ t('manager.shipping.zones.default_zone') }}</label>
        </div>
        <div class="flex gap-2">
          <button
            type="submit"
            @click="storeZone"
            class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
          >
            {{ t('common.save') }}
          </button>
          <button
            @click="showForm = false"
            class="border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  zones: { type: Array, default: () => [] },
  methods: { type: Array, default: () => [] },
})

const showForm = ref(false)
const form = reactive({ name: '', is_default: false })
const errors = reactive({})

function storeZone() {
  router.post(route('tenant.manager.shipping.zones.store'), form, {
    onError: (e) => Object.assign(errors, e),
    onSuccess: () => {
      showForm.value = false
      form.name = ''
      form.is_default = false
    },
  })
}

function deleteZone(zone) {
  if (!confirm(t('manager.shipping.zones.delete_the_zone_a', { a: zone.name }))) return
  router.delete(route('tenant.manager.shipping.zones.destroy', zone.id))
}
</script>
