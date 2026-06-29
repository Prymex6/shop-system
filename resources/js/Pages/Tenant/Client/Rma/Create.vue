<template>
  <ClientLayout :title="t('client.rma.create.return_request')">
    <div class="max-w-2xl mx-auto px-4 py-10">
      <!-- Header -->
      <div class="mb-6">
        <Link :href="backLink" class="text-indigo-600 hover:text-indigo-800 text-sm">
          ← {{ token ? t('client.ordertracking.order_tracking') : t('common.my_account') }}
        </Link>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ t('client.rma.create.return_request_rma') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ t('common.order_hash_a', { a: order.order_number }) }}</p>
      </div>

      <!-- Already requested -->
      <div v-if="already_requested" class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 text-center">
        <i class="fa-solid fa-circle-check text-indigo-500 text-3xl mb-3 block"></i>
        <p class="text-gray-700 font-medium">{{ t('client.rma.create.a_return_request_for_this_order') }}</p>
      </div>

      <!-- Not allowed -->
      <div
        v-else-if="order.fulfillment_status !== 'delivered'"
        class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center"
      >
        <i class="fa-solid fa-triangle-exclamation text-yellow-500 text-3xl mb-3 block"></i>
        <p class="text-gray-700 font-medium">{{ t('client.rma.create.only_delivered_orders_can_be_returned') }}</p>
        <p class="text-sm text-gray-500 mt-1">
          {{ t('client.rma.create.your_order_s_status') }}
          <strong>{{ fulfillmentLabel(order.fulfillment_status) }}</strong>
        </p>
      </div>

      <!-- Return window expired -->
      <div v-else-if="return_window_expired" class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
        <i class="fa-solid fa-clock text-yellow-500 text-3xl mb-3 block"></i>
        <p class="text-gray-700 font-medium">{{ t('client.rma.create.the_return_window_has_closed') }}</p>
        <p class="text-sm text-gray-500 mt-1">
          {{ t('common.returns_were_possible_until_a', { a: formatDeadline(return_deadline) }) }}
        </p>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-6">
        <p v-if="return_deadline" class="text-xs text-gray-400 -mt-2">
          {{ t('common.return_deadline_a', { a: formatDeadline(return_deadline) }) }}
        </p>

        <!-- Items -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
          <h2 class="font-bold text-gray-900 mb-4">{{ t('common.products_to_return') }}</h2>
          <p class="text-sm text-gray-500 mb-4">{{ t('client.rma.create.tick_the_products_you_want_to') }}</p>

          <div class="space-y-3">
            <label
              v-for="item in order.items"
              :key="item.id"
              class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
              :class="
                form.items.includes(item.id)
                  ? 'border-indigo-300 bg-indigo-50'
                  : 'border-gray-200 hover:border-gray-300'
              "
            >
              <input
                type="checkbox"
                :value="item.id"
                v-model="form.items"
                class="mt-0.5 h-4 w-4 text-indigo-600 rounded"
              />
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ item.quantity }}x {{ item.name }}
                  <span v-if="item.variant_name" class="text-gray-500">({{ item.variant_name }})</span>
                </p>
                <p class="text-xs text-gray-400">{{ formatPrice(item.price) }} / szt.</p>
              </div>
            </label>
          </div>
          <p v-if="form.errors.items" class="mt-2 text-sm text-red-600">{{ form.errors.items }}</p>
        </div>

        <!-- Reason -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
          <h2 class="font-bold text-gray-900">{{ t('common.reason_for_the_return') }}</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('client.rma.create.describe_the_reason')
            }}</label>
            <textarea
              v-model="form.reason"
              rows="4"
              required
              :placeholder="t('client.rma.create.tell_us_in_detail_why_you')"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:theme-primary-ring resize-none"
              :class="{ 'border-red-400': form.errors.reason }"
            ></textarea>
            <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('client.rma.create.product_condition_optional')
            }}</label>
            <textarea
              v-model="form.condition_notes"
              rows="2"
              :placeholder="t('client.rma.create.describe_the_condition_of_what_you')"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:theme-primary-ring resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between gap-4">
          <Link
            :href="backLink"
            class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </Link>
          <button
            type="submit"
            :disabled="form.processing || !form.items.length"
            class="px-6 py-2.5 theme-primary-bg hover:opacity-90 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-xl text-sm transition"
          >
            {{ form.processing ? t('common.sending_2') : t('common.raise_an_rma') }}
          </button>
        </div>
      </form>
    </div>
  </ClientLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  order: { type: Object, required: true },
  token: { type: String, default: '' },
  already_requested: { type: Boolean, default: false },
  return_deadline: { type: String, default: null },
  return_window_expired: { type: Boolean, default: false },
})

const formatDeadline = (dateStr) => (dateStr ? new Date(dateStr).toLocaleDateString(locale.value) : '')

const backLink = computed(() =>
  props.token
    ? route('tenant.order.tracking', props.order.order_number) + '?token=' + props.token
    : route('tenant.account'),
)

const form = useForm({
  items: [], // selected order_item ids, mapped to {name, qty} on submit
  reason: '',
  condition_notes: '',
})

const submit = () => {
  const selected = props.order.items.filter((i) => form.items.includes(i.id))

  form
    .transform((data) => ({
      ...data,
      items: selected.map((i) => ({ name: i.name, qty: i.quantity })),
      token: props.token || undefined,
    }))
    .post(route('tenant.rma.store', props.order.order_number))
}

const formatPrice = (price) =>
  new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'PLN' }).format(price ?? 0)

const fulfillmentLabel = (s) =>
  ({
    unfulfilled: t('common.awaiting_fulfilment'),
    processing: t('client.ordertracking.being_fulfilled'),
    shipped: t('manager.orders.index.sent'),
    delivered: t('manager.orders.index.delivered'),
  })[s] ?? s
</script>
