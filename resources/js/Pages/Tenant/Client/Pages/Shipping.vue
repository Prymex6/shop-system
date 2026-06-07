<template>
  <Head :title="t('common.delivery_and_payment')" />
  <ClientLayout>
    <StaticPage :title="t('common.delivery_and_payment')" icon="fa-solid fa-truck-fast" :content="content" />
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
