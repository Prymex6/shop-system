<template>
  <ManagerLayout :title="t('manager.backups.index.backups')">
    <div class="space-y-6">
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ t('manager.backups.index.backups') }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ t('manager.backups.index.manage_backups_of_the_shop_s') }}</p>
        </div>
        <button
          @click="createBackup"
          :disabled="creating"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition flex items-center gap-2 disabled:opacity-50"
        >
          <svg v-if="creating" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <i v-else class="fa-solid fa-database"></i>
          {{ creating ? 'Tworzenie...' : t('common.create_a_backup') }}
        </button>
      </div>

      <!-- Flash message -->
      <div
        v-if="$page.props.flash?.success"
        class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2"
      >
        <i class="fa-solid fa-check-circle text-green-500"></i>
        {{ $page.props.flash.success }}
      </div>

      <!-- Backups table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.backups.index.available_backups') }}</h2>
        </div>

        <div v-if="backups.length === 0" class="px-6 py-12 text-center text-gray-500">
          <i class="fa-solid fa-database text-4xl text-gray-300 mb-3 block"></i>
          <p>{{ t('manager.backups.index.no_backups_yet_create_the_first') }}</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.backups.index.file') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('common.date') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('manager.products.form.spec_size') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="backup in backups" :key="backup.filename" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-file-zipper text-gray-400"></i>
                  <span class="text-sm font-mono text-gray-900">{{ backup.filename }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                {{ backup.last_modified }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ backup.size_human }}
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <a
                    :href="`/manager/backups/${encodeURIComponent(backup.filename)}/download`"
                    class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200 transition"
                  >
                    <i class="fa-solid fa-download mr-1"></i>
                    {{ t('common.download') }}
                  </a>
                  <button
                    @click="deleteBackup(backup.filename)"
                    class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200 transition"
                  >
                    <i class="fa-solid fa-trash mr-1"></i>
                    {{ t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Info box -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <div class="flex gap-2">
          <i class="fa-solid fa-circle-info mt-0.5 text-blue-500"></i>
          <div>
            <strong>{{ t('manager.backups.index.note') }}</strong>
            {{ t('manager.backups.index.backups_are_made_in_the_background') }}
            <code class="bg-blue-100 px-1 rounded">storage/app/private/backups/</code>.
          </div>
        </div>
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
  backups: { type: Array, default: () => [] },
})

const creating = ref(false)

const createBackup = () => {
  creating.value = true
  router.post(
    '/manager/backups',
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        creating.value = false
      },
    },
  )
}

const deleteBackup = (filename) => {
  if (!confirm(t('manager.backups.index.delete_the_backup_a', { a: filename }))) return
  router.delete(`/manager/backups/${encodeURIComponent(filename)}`, {
    preserveScroll: true,
  })
}
</script>
