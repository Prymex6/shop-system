<template>
  <LandlordLayout :title="t('landlord.contacts.index.contact_enquiries')">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Stats -->
        <div class="flex items-center gap-4 mb-6">
          <span class="text-sm text-gray-500">
            {{ t('common.total_3') }} <strong>{{ inquiries.total }}</strong>
          </span>
          <span v-if="unread > 0" class="px-2.5 py-0.5 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
            {{ unread }} nieprzeczytanych
          </span>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('landlord.contacts.index.sender') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('pages.landing.subject') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('common.message') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.date') }}</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="inq in inquiries.data" :key="inq.id" :class="inq.read ? 'bg-white' : 'bg-blue-50'">
                <td class="px-4 py-4">
                  <div class="flex items-start gap-2">
                    <span v-if="!inq.read" class="mt-1.5 w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                      <div class="font-medium text-gray-900 text-sm">{{ inq.name }}</div>
                      <a :href="`mailto:${inq.email}`" class="text-xs text-blue-600 hover:underline">{{ inq.email }}</a>
                      <div v-if="inq.phone" class="text-xs text-gray-400">{{ inq.phone }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-700">{{ inq.subject || '—' }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 max-w-xs">
                  <div class="line-clamp-2">{{ inq.message }}</div>
                </td>
                <td class="px-4 py-4 text-xs text-gray-400 whitespace-nowrap">{{ formatDate(inq.created_at) }}</td>
                <td class="px-4 py-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="!inq.read"
                      @click="markRead(inq)"
                      class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                    >
                      {{ t('landlord.contacts.index.read') }}
                    </button>
                    <button @click="remove(inq)" class="text-xs text-red-500 hover:text-red-700 font-medium">
                      {{ t('common.delete') }}
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="inquiries.data.length === 0">
                <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                  {{ t('landlord.contacts.index.no_contact_enquiries') }}
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="inquiries.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex gap-2">
            <Link
              v-for="link in inquiries.links"
              :key="link.label"
              :href="link.url ?? '#'"
              :class="[
                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                'px-3 py-1 rounded border border-gray-300 text-sm',
                !link.url && 'opacity-40 pointer-events-none',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  inquiries: Object,
  unread: Number,
})

const formatDate = (date) => {
  return new Date(date).toLocaleString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const markRead = (inq) => {
  router.patch(route('landlord.contacts.read', inq.id))
}

const remove = (inq) => {
  if (confirm(t('common.delete_this_enquiry'))) {
    router.delete(route('landlord.contacts.destroy', inq.id))
  }
}
</script>
