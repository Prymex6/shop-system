<template>
  <Head :title="t('layout.clientlayout.terms_and_conditions')" />
  <ClientLayout>
    <StaticPage
      :title="t('client.pages.terms.terms_and_conditions')"
      icon="fa-solid fa-file-contract"
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
