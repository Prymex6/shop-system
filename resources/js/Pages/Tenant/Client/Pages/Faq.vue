<template>
  <Head :title="t('common.frequently_asked_questions')" />
  <ClientLayout>
    <StaticPage
      :title="t('common.faq_frequently_asked_questions')"
      icon="fa-solid fa-circle-question"
      :content="content"
    />
  </ClientLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import StaticPage from '@/Components/Client/StaticPage.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
defineProps({ content: { type: String, default: '' } })
</script>
