<template>
  <ManagerLayout :title="t('layout.managerlayout.catalogue')">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('layout.managerlayout.catalogue') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.catalog.index.manage_what_is_in_the_catalogue') }}</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link
          :href="route('tenant.manager.collections.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-violet-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-layer-group text-violet-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('layout.clientlayout.collections') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.catalog.index.group_products_into_collections') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.bundles.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-cubes text-teal-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">{{ t('common.product_bundles') }}</div>
              <div class="text-sm text-gray-500">{{ t('manager.catalog.index.bundle_several_products_together') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.gift-cards.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-gift text-pink-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">
                {{ t('pages.landing.gift_cards') }}
              </div>
              <div class="text-sm text-gray-500">{{ t('manager.catalog.index.manage_gift_cards') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.articles.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-newspaper text-blue-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">Blog</div>
              <div class="text-sm text-gray-500">{{ t('manager.catalog.index.articles_and_blog_posts') }}</div>
            </div>
          </div>
        </Link>
        <Link
          :href="route('tenant.manager.badges.index')"
          class="bg-white shadow rounded-lg p-5 hover:shadow-md transition group"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
              <i class="fa-solid fa-medal text-amber-600 text-xl"></i>
            </div>
            <div>
              <div class="font-semibold text-gray-900 group-hover:text-blue-600">{{ t('client.account.badges') }}</div>
              <div class="text-sm text-gray-500">{{ t('manager.catalog.index.product_labels_and_markers') }}</div>
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
