<template>
  <ManagerLayout :title="t('manager.promotions.index.promotions_and_banners')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.promotions.index.promotions_and_banners') }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ t('manager.promotions.index.manage_promotions_and_the_notification_banner') }}
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('manager.promotions.index.new_promotion') }}
        </button>
      </div>

      <!-- Announcement Banner Section -->
      <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.promotions.index.notification_banner') }}</h2>
        <p class="text-sm text-gray-500">{{ t('manager.promotions.index.an_information_bar_shown_at_the') }}</p>

        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="bannerForm.announcement_enabled"
              type="checkbox"
              class="h-4 w-4 text-blue-600 rounded"
              @change="saveBanner"
            />
            <span class="text-sm font-medium text-gray-700">{{ t('manager.promotions.index.banner_on') }}</span>
          </label>
        </div>

        <div v-if="bannerForm.announcement_enabled" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.promotions.index.banner_text')
            }}</label>
            <input
              v-model="bannerForm.announcement_text"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              :placeholder="t('manager.promotions.index.e_g_free_delivery_over_199')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.background_colour') }}</label>
            <div class="flex items-center gap-3">
              <input
                v-model="bannerForm.announcement_color"
                type="color"
                class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
              />
              <span class="text-sm text-gray-500 font-mono">{{ bannerForm.announcement_color }}</span>
            </div>
          </div>

          <!-- Preview -->
          <div
            class="rounded-lg px-4 py-3 text-sm font-medium text-white text-center"
            :style="{ backgroundColor: bannerForm.announcement_color }"
          >
            {{ bannerForm.announcement_text || t('common.banner_preview') }}
          </div>

          <div class="flex justify-end">
            <button
              @click="saveBanner"
              :disabled="bannerSaving"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm"
            >
              {{ bannerSaving ? 'Zapisywanie...' : t('common.save_banner') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Promotions Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.promotions.index.banner_text') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('manager.promotions.index.dates') }}
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
              <tr v-if="!promotions.length">
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                  {{ t('manager.promotions.index.no_promotions_yet_create_the_first') }}
                </td>
              </tr>
              <tr v-for="promo in promotions" :key="promo.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="font-semibold text-gray-900">{{ promo.name }}</div>
                  <div v-if="promo.discount_code" class="text-xs text-gray-400">Kod: {{ promo.discount_code }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                  {{ promo.banner_text || '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                  <div v-if="promo.starts_at">Od: {{ formatDate(promo.starts_at) }}</div>
                  <div v-if="promo.ends_at">Do: {{ formatDate(promo.ends_at) }}</div>
                  <div v-if="!promo.starts_at && !promo.ends_at">{{ t('common.unlimited') }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="promo.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ promo.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                  <button @click="openEditModal(promo)" class="text-blue-600 hover:text-blue-900">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="deletePromo(promo)" class="text-red-600 hover:text-red-900">
                    {{ t('common.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-lg w-full">
        <div class="p-6">
          <h2 class="text-xl font-bold mb-6">
            {{ editingPromo ? t('common.edit_promotion') : t('common.new_promotion') }}
          </h2>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-lg"
                :placeholder="t('manager.promotions.index.e_g_summer_2025')"
              />
              <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.promotions.index.banner_text')
              }}</label>
              <input
                v-model="form.banner_text"
                type="text"
                class="w-full px-3 py-2 border rounded-lg"
                :placeholder="t('manager.promotions.index.e_g_summer_sale_up_to')"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.promotions.index.start_date')
                }}</label>
                <input v-model="form.starts_at" type="datetime-local" class="w-full px-3 py-2 border rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.promotions.index.end_date')
                }}</label>
                <input v-model="form.ends_at" type="datetime-local" class="w-full px-3 py-2 border rounded-lg" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.promotions.index.linked_discount_code')
              }}</label>
              <input
                v-model="form.discount_code"
                type="text"
                class="w-full px-3 py-2 border rounded-lg uppercase"
                :placeholder="t('manager.promotions.index.e_g_summer2025')"
              />
            </div>

            <div class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" id="promo-active" class="h-4 w-4 text-blue-600 rounded" />
              <label for="promo-active" class="text-sm font-medium text-gray-700">{{
                t('manager.promotions.index.promotion_active')
              }}</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ form.processing ? 'Zapisywanie...' : editingPromo ? t('common.save') : t('common.create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  promotions: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) },
})

const showModal = ref(false)
const editingPromo = ref(null)
const bannerSaving = ref(false)

const bannerForm = reactive({
  announcement_enabled: props.settings?.announcement_enabled ?? false,
  announcement_text: props.settings?.announcement_text ?? '',
  announcement_color: props.settings?.announcement_color ?? '#4f46e5',
})

async function saveBanner() {
  bannerSaving.value = true
  router.post(
    route('tenant.manager.promotions.banner'),
    {
      announcement_enabled: bannerForm.announcement_enabled,
      announcement_text: bannerForm.announcement_text,
      announcement_color: bannerForm.announcement_color,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        bannerSaving.value = false
      },
    },
  )
}

const form = useForm({
  name: '',
  banner_text: '',
  starts_at: '',
  ends_at: '',
  discount_code: '',
  is_active: true,
})

function openCreateModal() {
  editingPromo.value = null
  form.reset()
  form.is_active = true
  showModal.value = true
}

function openEditModal(promo) {
  editingPromo.value = promo
  form.name = promo.name
  form.banner_text = promo.banner_text || ''
  form.starts_at = promo.starts_at ? promo.starts_at.substring(0, 16) : ''
  form.ends_at = promo.ends_at ? promo.ends_at.substring(0, 16) : ''
  form.discount_code = promo.discount_code || ''
  form.is_active = promo.is_active
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingPromo.value = null
  form.reset()
}

function submitForm() {
  if (editingPromo.value) {
    form.put(route('tenant.manager.promotions.update', editingPromo.value.id), {
      onSuccess: closeModal,
    })
  } else {
    form.post(route('tenant.manager.promotions.store'), {
      onSuccess: closeModal,
    })
  }
}

function deletePromo(promo) {
  if (!confirm(t('manager.promotions.index.delete_the_promotion_a', { a: promo.name }))) return
  router.delete(route('tenant.manager.promotions.destroy', promo.id))
}

function formatDate(date) {
  return new Date(date).toLocaleString(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
