<template>
  <Head title="Marketing" />
  <ManagerLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.marketing.index.marketing_email_campaigns') }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ t('manager.marketing.index.customers_with_an_email_address') }} <strong>{{ customersCount }}</strong>
            {{ t('manager.marketing.index.newsletter_subscribers') }} <strong>{{ newsletterCount }}</strong>
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <i class="fa-solid fa-plus"></i>
          {{ t('manager.marketing.index.new_campaign') }}
        </button>
      </div>

      <!-- Campaigns list -->
      <div class="space-y-3">
        <div v-for="campaign in campaigns.data" :key="campaign.id" class="bg-white rounded-lg shadow-sm p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-1">
                <h3 class="font-semibold text-gray-900">{{ campaign.name }}</h3>
                <span :class="statusClass(campaign.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ statusLabel(campaign.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-600 mb-1">
                <strong>{{ t('manager.marketing.index.subject') }}</strong> {{ campaign.subject }}
              </p>
              <p class="text-sm text-gray-500">
                <strong>{{ t('manager.marketing.index.recipients') }}</strong> {{ targetLabel(campaign.target) }}
                <span v-if="campaign.status === 'sent'">
                  {{ t('manager.marketing.index.sent_to') }} <strong>{{ campaign.recipients_count }}</strong>
                  {{ t('manager.marketing.index.people') }}</span
                >
              </p>
              <p v-if="campaign.sent_at" class="text-xs text-gray-400 mt-1">
                {{ t('common.sent_a', { a: formatDate(campaign.sent_at) }) }}
              </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                v-if="campaign.status !== 'sent'"
                @click="openEditModal(campaign)"
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              >
                {{ t('common.edit') }}
              </button>
              <button
                v-if="campaign.status !== 'sent'"
                @click="confirmSend(campaign)"
                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                <i class="fa-solid fa-paper-plane mr-1"></i>
                {{ t('common.send') }}
              </button>
              <button
                v-if="campaign.status !== 'sent'"
                @click="deleteCampaign(campaign)"
                class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
              >
                {{ t('common.delete') }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="campaigns.data.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <i class="fa-solid fa-bullhorn text-5xl text-gray-300 mb-4 block"></i>
          <p class="text-gray-500">{{ t('manager.marketing.index.no_campaigns_yet_create_the_first') }}</p>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="formModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">
            {{ formModal.editing ? t('common.edit_campaign') : t('manager.marketing.index.new_campaign') }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.campaign_name') }}</label>
              <input
                v-model="campaignForm.name"
                name="name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                :placeholder="t('manager.marketing.index.e_g_weekend_promotion')"
              />
              <p v-if="campaignForm.errors.name" class="mt-1 text-sm text-red-600">{{ campaignForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.index.email_subject')
              }}</label>
              <input
                v-model="campaignForm.subject"
                name="subject"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                :placeholder="t('manager.marketing.index.e_g_this_weekend_only')"
              />
              <p v-if="campaignForm.errors.subject" class="mt-1 text-sm text-red-600">
                {{ campaignForm.errors.subject }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.index.recipients_2')
              }}</label>
              <select
                v-model="campaignForm.target"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">Wszyscy klienci ({{ customersCount }})</option>
                <option value="active">{{ t('manager.marketing.index.active_ordered_in_the_last_90') }}</option>
                <option value="inactive">{{ t('manager.marketing.index.inactive_no_orders_for_90_days') }}</option>
                <option value="newsletter">Subskrybenci newslettera ({{ newsletterCount }})</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.marketing.index.email_content')
              }}</label>
              <textarea
                v-model="campaignForm.content"
                name="content"
                rows="8"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-y"
                :placeholder="t('manager.marketing.index.write_the_campaign_s_content')"
              ></textarea>
              <p v-if="campaignForm.errors.content" class="mt-1 text-sm text-red-600">
                {{ campaignForm.errors.content }}
              </p>
              <p class="text-xs text-gray-400 mt-1">{{ t('manager.marketing.index.the_customer_s_first_name_is') }}</p>
            </div>

            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="formModal.open = false"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="campaignForm.processing"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
              >
                {{ campaignForm.processing ? 'Zapisywanie...' : t('common.save_draft') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Confirm Send Modal -->
    <div v-if="sendConfirm.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ t('manager.marketing.index.send_the_campaign') }}</h3>
        <p class="text-gray-600 mb-1">
          {{ t('manager.loyalty.campaigns.campaign') }} <strong>{{ sendConfirm.campaign?.name }}</strong>
          {{ t('manager.marketing.index.will_be_sent_to') }}
          <strong>{{ targetLabel(sendConfirm.campaign?.target) }}</strong
          >.
        </p>
        <p class="text-sm text-orange-600 bg-orange-50 rounded-lg px-3 py-2 mb-4">
          {{ t('manager.marketing.index.this_cannot_be_undone_the_email') }}
        </p>
        <div class="flex gap-3">
          <button
            @click="sendConfirm.open = false"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            @click="doSend"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700"
          >
            {{ t('manager.marketing.index.send_the_campaign_2') }}
          </button>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  campaigns: Object,
  customersCount: Number,
  newsletterCount: Number,
})

const formModal = reactive({ open: false, editing: false, campaignId: null })
const sendConfirm = reactive({ open: false, campaign: null })

const campaignForm = useForm({
  name: '',
  subject: '',
  content: '',
  target: 'all',
})

const openCreateModal = () => {
  campaignForm.reset()
  formModal.editing = false
  formModal.campaignId = null
  formModal.open = true
}

const openEditModal = (campaign) => {
  campaignForm.name = campaign.name
  campaignForm.subject = campaign.subject
  campaignForm.content = campaign.content
  campaignForm.target = campaign.target
  formModal.editing = true
  formModal.campaignId = campaign.id
  formModal.open = true
}

const submitForm = () => {
  if (formModal.editing) {
    campaignForm.put(route('tenant.manager.marketing.update', formModal.campaignId), {
      onSuccess: () => {
        formModal.open = false
      },
    })
  } else {
    campaignForm.post(route('tenant.manager.marketing.store'), {
      onSuccess: () => {
        formModal.open = false
      },
    })
  }
}

const confirmSend = (campaign) => {
  sendConfirm.campaign = campaign
  sendConfirm.open = true
}

const doSend = () => {
  router.post(
    route('tenant.manager.marketing.send', sendConfirm.campaign.id),
    {},
    {
      onSuccess: () => {
        sendConfirm.open = false
      },
    },
  )
}

const deleteCampaign = (campaign) => {
  if (confirm(t('manager.loyalty.campaigns.delete_the_campaign_a', { a: campaign.name }))) {
    router.delete(route('tenant.manager.marketing.destroy', campaign.id))
  }
}

const statusLabel = (s) =>
  ({
    draft: t('manager.articles.form.draft'),
    scheduled: t('manager.marketing.index.scheduled'),
    sending: t('common.sending_3'),
    sent: t('common.sent'),
    cancelled: t('manager.marketing.index.cancelled'),
  })[s] || s
const statusClass = (s) =>
  ({
    draft: 'bg-gray-100 text-gray-700',
    sent: 'bg-green-100 text-green-700',
    sending: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-red-100 text-red-700',
  })[s] || 'bg-gray-100 text-gray-700'

const targetLabel = (t) =>
  ({
    all: t('manager.marketing.index.all_customers'),
    active: t('manager.marketing.index.active_customers'),
    inactive: t('manager.marketing.index.inactive_customers'),
    newsletter: t('manager.marketing.index.newsletter_subscribers_2'),
  })[t] || t

const formatDate = (d) =>
  new Date(d).toLocaleDateString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
</script>
