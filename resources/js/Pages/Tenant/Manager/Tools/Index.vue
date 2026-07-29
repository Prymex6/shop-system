<template>
  <ManagerLayout :title="t('common.tools')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.tools') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.tools.index.advanced_administration_tools') }}</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link
          :href="route('tenant.manager.staff.performance')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-sky-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-chart-line text-sky-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.tools.index.staff_performance') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.staff_statistics') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.knowledge-base.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-book-open text-emerald-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.knowledgebase.index.knowledge_base') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.help_articles_for_customers') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.webhooks.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-webhook text-gray-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.tools.index.webhooks') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.external_integrations') }}</div>
            </div>
          </div>
        </Link>
        <Link href="/manager/fraud" class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-shield-halved text-red-500 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">Fraud Detection</div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.spotting_suspicious_orders') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.audit-log.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-scroll text-gray-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.auditlog.index.audit_log') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.a_history_of_changes_in_the') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.gdpr.requests')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-user-shield text-red-500 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">GDPR</div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.data_and_deletion_requests') }}</div>
            </div>
          </div>
        </Link>
        <Link href="/manager/backups" class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-database text-gray-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('manager.backups.index.backups') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.tools.index.shop_data_backup') }}</div>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
