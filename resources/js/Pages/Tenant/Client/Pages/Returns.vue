<template>
  <Head :title="t('layout.clientlayout.returns_and_complaints')" />
  <ClientLayout>
    <StaticPage
      :title="t('layout.clientlayout.returns_and_complaints')"
      icon="fa-solid fa-rotate-left"
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
