<template>
  <ClientLayout :title="t('client.downloads.my_files')">
    <div class="max-w-3xl mx-auto px-4 py-10">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ t('client.downloads.my_downloads') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ t('client.downloads.digital_files_bought_from_the_shop') }}</p>
      </div>

      <!-- Empty state -->
      <div v-if="downloads.length === 0" class="text-center py-20 bg-white rounded-2xl border border-gray-200">
        <div class="text-5xl mb-4">📥</div>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">{{ t('common.no_files_to_download') }}</h2>
        <p class="text-gray-400 text-sm mb-6">{{ t('client.downloads.your_digital_files_appear_here_once') }}</p>
        <Link
          :href="route('tenant.shop')"
          class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-indigo-700"
        >
          {{ t('common.go_to_the_shop') }}
        </Link>
      </div>

      <!-- Downloads list -->
      <div v-else class="space-y-4">
        <div
          v-for="link in downloads"
          :key="link.id"
          class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4"
        >
          <!-- Icon -->
          <div class="flex-shrink-0 w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-2xl">
            {{ fileIcon(link.file?.mime_type) }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 truncate">{{ link.product?.name ?? 'Plik cyfrowy' }}</p>
            <p v-if="link.file" class="text-sm text-gray-500 truncate">
              {{ link.file.name }}
              <span v-if="link.file.size" class="ml-1 text-gray-400">({{ formatSize(link.file.size) }})</span>
            </p>

            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
              <!-- Expiry -->
              <span v-if="link.expires_at" class="text-xs text-gray-400">
                <span :class="isExpired(link.expires_at) ? 'text-red-500' : ''">
                  {{ isExpired(link.expires_at) ? t('common.expired_3') : t('manager.discounts.index.valid_until') }}:
                  {{ formatDate(link.expires_at) }}
                </span>
              </span>

              <!-- Download count -->
              <span class="text-xs text-gray-400">
                Pobrano: {{ link.downloads_count ?? 0 }}
                <template v-if="link.download_limit">/ {{ link.download_limit }}</template>
              </span>

              <!-- Order -->
              <span class="text-xs text-gray-400">
                {{ t('common.order_3') }} <span class="font-mono">#{{ link.order?.order_number ?? '—' }}</span>
              </span>
            </div>
          </div>

          <!-- Action -->
          <div class="flex-shrink-0">
            <a
              v-if="isAvailable(link)"
              :href="route('tenant.download', link.token)"
              class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
              </svg>
              {{ t('common.download') }}
            </a>
            <span v-else class="inline-block text-xs text-gray-400 border border-gray-200 px-3 py-2 rounded-lg">
              {{ linkUnavailableReason(link) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({ downloads: Array })

function isExpired(date) {
  return date && new Date(date) < new Date()
}

function isAvailable(link) {
  if (isExpired(link.expires_at)) return false
  if (link.download_limit && link.downloads_count >= link.download_limit) return false
  return true
}

function linkUnavailableReason(link) {
  if (isExpired(link.expires_at)) return t('common.expired_2')
  if (link.download_limit && link.downloads_count >= link.download_limit) return t('common.download_limit')
  return t('client.shop.product.out_of_stock')
}

function formatDate(date) {
  return new Date(date).toLocaleDateString(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function fileIcon(mime) {
  if (!mime) return '📄'
  if (mime.startsWith('image/')) return '🖼️'
  if (mime.includes('pdf')) return '📕'
  if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) return '🗜️'
  if (mime.includes('audio')) return '🎵'
  if (mime.includes('video')) return '🎬'
  if (mime.includes('word') || mime.includes('document')) return '📝'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return '📊'
  return '📦'
}
</script>
