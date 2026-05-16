<template>
  <ClientLayout :title="t('common.customer_reviews')">
    <div class="container mx-auto px-4 py-10 max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ t('common.customer_reviews') }}</h1>
        <p class="text-gray-500 text-sm">{{ t('client.reviews.what_our_customers_say_about_the') }}</p>
      </div>

      <!-- Google Reviews CTA -->
      <div v-if="googleUrl" class="mb-8 bg-blue-50 border border-blue-200 rounded-2xl p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center shrink-0">
          <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none">
            <path
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              fill="#4285F4"
            />
            <path
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              fill="#34A853"
            />
            <path
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"
              fill="#FBBC05"
            />
            <path
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              fill="#EA4335"
            />
          </svg>
        </div>
        <div class="flex-1">
          <p class="font-semibold text-gray-900 text-sm">{{ t('client.reviews.google_reviews') }}</p>
          <p class="text-gray-500 text-xs mt-0.5">{{ t('client.reviews.see_our_rating_on_google_maps') }}</p>
        </div>
        <a
          :href="googleUrl"
          target="_blank"
          rel="noopener"
          class="shrink-0 inline-flex items-center gap-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-xl hover:bg-gray-50 transition shadow-sm"
        >
          <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
          {{ t('common.open') }}
        </a>
      </div>

      <!-- Summary stats -->
      <div v-if="reviews.data.length" class="mb-8 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-6 flex-wrap">
          <div class="text-center">
            <p class="text-4xl font-bold text-gray-900">{{ avgRating }}</p>
            <div class="flex justify-center gap-0.5 mt-1">
              <i
                v-for="n in 5"
                :key="n"
                :class="
                  n <= Math.round(avgRating) ? 'fa-solid fa-star text-amber-400' : 'fa-regular fa-star text-gray-300'
                "
                class="text-sm"
              ></i>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ reviews.total }} {{ reviewWord(reviews.total) }}</p>
          </div>
          <div class="flex-1 space-y-1.5 min-w-[180px]">
            <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2">
              <span class="text-xs text-gray-500 w-4">{{ star }}</span>
              <i class="fa-solid fa-star text-amber-400 text-xs shrink-0"></i>
              <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-400 rounded-full" :style="{ width: starPercent(star) + '%' }"></div>
              </div>
              <span class="text-xs text-gray-400 w-6 text-right">{{ starCount(star) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Reviews list -->
      <div v-if="reviews.data.length" class="space-y-4">
        <div
          v-for="review in reviews.data"
          :key="review.id"
          class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div
              class="w-10 h-10 rounded-full theme-primary-bg flex items-center justify-center text-white font-bold text-sm shrink-0"
            >
              {{ (review.customer_name || review.reviewer_name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <!-- Name + date -->
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-900 text-sm">
                  {{ review.customer_name || review.reviewer_name || t('common.customer') }}
                </span>
                <span class="text-xs text-gray-400">
                  {{ formatDate(review.created_at) }}
                </span>
                <span
                  v-if="review.verified_purchase"
                  class="inline-flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full"
                >
                  <i class="fa-solid fa-circle-check text-[10px]"></i> {{ t('client.reviews.verified_purchase') }}
                </span>
              </div>
              <!-- Stars -->
              <div class="flex gap-0.5 mt-1">
                <i
                  v-for="n in 5"
                  :key="n"
                  :class="n <= review.rating ? 'fa-solid fa-star text-amber-400' : 'fa-regular fa-star text-gray-200'"
                  class="text-xs"
                ></i>
              </div>
              <!-- Product link -->
              <div v-if="review.product" class="mt-1">
                <Link
                  :href="route('tenant.product.show', review.product.slug)"
                  class="text-xs text-blue-600 hover:underline"
                >
                  {{ review.product.name }}
                </Link>
              </div>
              <!-- Title -->
              <p v-if="review.title" class="font-semibold text-gray-800 text-sm mt-2">{{ review.title }}</p>
              <!-- Body -->
              <p class="text-gray-600 text-sm mt-1 leading-relaxed">{{ review.body || review.content }}</p>
              <!-- Shop reply -->
              <div v-if="review.reply" class="mt-3 bg-gray-50 border border-gray-200 rounded-xl p-3">
                <p class="text-xs font-semibold text-gray-700 mb-1">
                  <i class="fa-solid fa-store mr-1 text-gray-400"></i>{{ t('common.shop_s_reply') }}
                </p>
                <p class="text-sm text-gray-600">{{ review.reply }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="text-center py-20 text-gray-400">
        <i class="fa-regular fa-star text-5xl mb-4 block text-gray-200"></i>
        <p class="font-medium text-gray-500">{{ t('client.reviews.no_reviews') }}</p>
        <p class="text-sm mt-1">{{ t('client.reviews.the_first_reviews_will_appear_here') }}</p>
      </div>

      <!-- Pagination -->
      <div v-if="reviews.last_page > 1" class="mt-8 flex justify-center gap-2">
        <Link
          v-for="page in reviews.last_page"
          :key="page"
          :href="route('tenant.trust.index') + '?page=' + page"
          class="w-9 h-9 rounded-lg text-sm font-medium flex items-center justify-center transition"
          :class="
            page === reviews.current_page
              ? 'theme-primary-bg text-white shadow'
              : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
          "
        >
          {{ page }}
        </Link>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  reviews: { type: Object, required: true },
  googleUrl: { type: String, default: null },
  schema: { type: Object, default: null },
})

const avgRating = computed(() => {
  if (!props.reviews.data.length) return 0
  const sum = props.reviews.data.reduce((acc, r) => acc + (r.rating || 0), 0)
  return (sum / props.reviews.data.length).toFixed(1)
})

const starCount = (star) => props.reviews.data.filter((r) => r.rating === star).length

const starPercent = (star) => {
  if (!props.reviews.data.length) return 0
  return Math.round((starCount(star) / props.reviews.data.length) * 100)
}

const reviewWord = (n) => {
  if (n === 1) return 'opinia'
  if (n >= 2 && n <= 4) return 'opinie'
  return 'opinii'
}

const formatDate = (d) =>
  new Date(d).toLocaleDateString(locale.value, { year: 'numeric', month: 'long', day: 'numeric' })
</script>
