<template>
  <ManagerLayout :title="t('manager.tools.index.webhooks')">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.tools.index.webhooks') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.webhooks.index.tell_other_services_when_something_happens') }}
          </p>
        </div>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.webhooks.index.new_webhook') }}
        </button>
      </div>

      <!-- Toast -->
      <Transition name="slide-down">
        <div
          v-if="testResult"
          class="fixed top-4 right-4 z-50 px-5 py-3 rounded-xl shadow-xl text-sm font-medium"
          :class="testResult.success ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        >
          {{ testResult.message }}
        </div>
      </Transition>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.webhooks.index.name_url') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.webhooks.index.events') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.active') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.webhooks.index.last_call') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('manager.webhooks.index.errors') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="wh in webhooks.data" :key="wh.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <p class="font-semibold text-gray-900">{{ wh.name }}</p>
                <p class="text-xs text-gray-400 font-mono truncate max-w-56">{{ wh.url }}</p>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="event in wh.events ?? []"
                    :key="event"
                    class="px-1.5 py-0.5 bg-indigo-50 text-blue-700 text-xs rounded font-mono"
                  >
                    {{ event }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="toggleActive(wh)"
                  :class="
                    wh.is_active
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                  "
                  class="px-2 py-0.5 rounded-full text-xs font-medium transition"
                >
                  {{ wh.is_active ? t('common.active') : t('common.inactive') }}
                </button>
              </td>
              <td class="px-4 py-3 text-xs text-gray-400">
                {{ wh.last_called_at ? timeAgo(wh.last_called_at) : '—' }}
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="(wh.error_count ?? 0) > 0 ? 'text-red-600 font-bold' : 'text-gray-400'">
                  {{ wh.error_count ?? 0 }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="testWebhook(wh)" class="text-purple-600 hover:text-purple-800 text-xs font-medium">
                    {{ t('manager.webhooks.index.test') }}
                  </button>
                  <button @click="openModal(wh)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                    {{ t('common.edit') }}
                  </button>
                  <button @click="destroy(wh)" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!webhooks.data?.length">
              <td colspan="6" class="text-center py-12 text-gray-400">{{ t('manager.webhooks.index.no_webhooks') }}</td>
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
      <div class="bg-white shadow rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="font-bold text-lg text-gray-900">
            {{ editing ? t('common.edit_webhook') : t('common.new_webhook') }}
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
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('manager.webhooks.index.url') }}</label>
            <input
              v-model="form.url"
              type="url"
              required
              placeholder="https://example.com/webhook"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('manager.webhooks.index.events') }}</label>
            <div class="space-y-2">
              <label v-for="event in availableEvents" :key="event.value" class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :value="event.value"
                  v-model="form.events"
                  class="h-4 w-4 text-blue-600 rounded"
                />
                <span class="text-sm text-gray-700">
                  <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded mr-2">{{ event.value }}</span>
                  {{ event.label }}
                </span>
              </label>
            </div>
          </div>
          <div v-if="!editing">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.webhooks.index.secret_optional')
            }}</label>
            <input
              v-model="form.secret"
              type="text"
              :placeholder="t('manager.webhooks.index.token_for_verifying_the_signature_leave')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
            />
          </div>
          <div v-else>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secret</label>
            <div class="flex items-center gap-2">
              <span
                class="flex-1 px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm font-mono text-gray-500"
                >{{ editing.secret_hint ?? '—' }}</span
              >
              <button
                type="button"
                @click="regenerateSecret(editing)"
                class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-xs font-medium whitespace-nowrap"
              >
                {{ t('manager.webhooks.index.regenerate') }}
              </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ t('manager.webhooks.index.for_safety_the_full_secret_is') }}</p>
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
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'

const { t } = useI18n()
const { relative } = useFormatting()

const props = defineProps({
  webhooks: { type: Object, required: true },
})

const showModal = ref(false)
const editing = ref(null)
const testResult = ref(null)

const availableEvents = [
  { value: 'order.created', label: t('manager.orders.index.new_order') },
  { value: 'order.shipped', label: t('common.order_dispatched') },
  { value: 'refund.requested', label: t('common.return_request') },
  { value: 'low_stock', label: t('manager.settings.index.low_stock') },
  { value: 'rma.created', label: t('common.new_rma') },
  { value: 'review.approved', label: t('manager.webhooks.index.review_approved') },
]

const form = useForm({ name: '', url: '', events: [], secret: '', is_active: true })

const openModal = (wh = null) => {
  editing.value = wh
  if (wh) {
    form.name = wh.name ?? ''
    form.url = wh.url ?? ''
    form.events = wh.events ?? []
    form.is_active = wh.is_active ?? true
  } else {
    form.reset()
    form.is_active = true
    form.events = []
  }
  showModal.value = true
}

const regenerateSecret = (wh) => {
  if (!confirm(t('common.generate_a_new_secret_the_old'))) return
  router.post(
    route('tenant.manager.webhooks.regenerate-secret', wh.id),
    {},
    {
      preserveScroll: true,
      onSuccess: (page) => {
        const newSecret = page.props.flash?.new_secret
        if (newSecret) alert(t('manager.webhooks.index.new_secret_shown_once', { a: newSecret }))
      },
    },
  )
}

const submit = () => {
  if (editing.value) {
    form.put(route('tenant.manager.webhooks.update', editing.value.id), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  } else {
    form.post(route('tenant.manager.webhooks.store'), {
      onSuccess: () => {
        showModal.value = false
      },
    })
  }
}

const destroy = (wh) => {
  if (!confirm(t('manager.webhooks.index.delete_the_webhook_a', { a: wh.name }))) return
  router.delete(route('tenant.manager.webhooks.destroy', wh.id))
}

const toggleActive = (wh) => {
  router.put(route('tenant.manager.webhooks.update', wh.id), { is_active: !wh.is_active }, { preserveScroll: true })
}

const testWebhook = async (wh) => {
  try {
    const res = await window.axios.post(route('tenant.manager.webhooks.test', wh.id))
    testResult.value = {
      success: true,
      message: t('manager.webhooks.index.test_sent_status_a', { a: res.data?.status ?? 200 }),
    }
  } catch (e) {
    testResult.value = {
      success: false,
      message: t('manager.webhooks.index.test_failed_a', { a: e?.response?.status ?? t('common.no_answer') }),
    }
  } finally {
    setTimeout(() => {
      testResult.value = null
    }, 5000)
  }
}

const timeAgo = (date) => {
  return relative.value(date)
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
