<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ t('client.productreviews.product_reviews') }}</h2>

    <!-- Reviews list -->
    <div v-if="reviews.length" class="space-y-4 mb-8">
      <div v-for="review in reviews" :key="review.id" class="bg-gray-50 rounded-2xl p-5">
        <div class="flex items-start justify-between mb-2">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-gray-900 text-sm">{{
                review.reviewer_name ?? review.customer?.name ?? t('client.productreviews.anonymous')
              }}</span>
              <span
                v-if="review.is_verified_purchase"
                class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full"
                >{{ t('client.productreviews.verified_purchase') }}</span
              >
            </div>
            <StarRating :rating="review.rating" class="mt-1" />
          </div>
          <span class="text-xs text-gray-400">{{ formatDate(review.created_at) }}</span>
        </div>
        <p v-if="review.title" class="font-semibold text-gray-800 mb-1">{{ review.title }}</p>
        <p class="text-gray-700 text-sm">{{ review.body }}</p>
        <!-- Manager reply -->
        <div v-if="review.reply" class="mt-3 pl-4 border-l-2 theme-primary-border text-sm text-gray-600">
          <p class="font-semibold theme-primary mb-1">{{ t('client.productreviews.shop_s_reply') }}</p>
          <p>{{ review.reply }}</p>
        </div>
      </div>
    </div>

    <div v-else class="text-gray-500 text-sm mb-8">
      {{ t('client.productreviews.no_reviews_yet_be_the_first') }}
    </div>

    <!-- Add review form (only for logged in customers) -->
    <div v-if="$page.props.auth?.customer" class="bg-white border border-gray-100 rounded-2xl p-6">
      <h3 class="font-bold text-gray-900 mb-4">{{ t('client.productreviews.write_a_review') }}</h3>
      <form @submit.prevent="submitReview" class="space-y-4">
        <!-- Rating -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('client.filtersidebar.rating') }}</label>
          <div class="flex gap-1">
            <button
              v-for="i in 5"
              :key="i"
              type="button"
              @click="form.rating = i"
              class="text-2xl transition"
              :class="i <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
            >
              ★
            </button>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{
            t('client.productreviews.title_optional')
          }}</label>
          <input
            v-model="form.title"
            type="text"
            maxlength="200"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm"
            :placeholder="t('client.productreviews.a_short_summary')"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{
            t('client.productreviews.review_content')
          }}</label>
          <textarea
            v-model="form.body"
            rows="3"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm resize-none"
            :placeholder="t('common.share_your_thoughts')"
          />
        </div>
        <button
          type="submit"
          :disabled="!form.rating"
          class="theme-primary-bg text-white px-6 py-2 rounded-xl font-semibold hover:opacity-90 transition disabled:opacity-50"
        >
          {{ t('client.productreviews.submit_the_review') }}
        </button>
      </form>
    </div>

    <p v-else class="text-sm text-gray-500">
      <Link :href="route('tenant.client.login')" class="theme-primary font-medium hover:underline">{{
        t('common.sign_in')
      }}</Link
      >{{ t('client.productreviews.to_write_a_review') }}
    </p>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import StarRating from './StarRating.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  reviews: { type: Array, default: () => [] },
  productId: { type: Number, required: true },
  orderId: { type: Number, default: null },
})

const form = reactive({ rating: 0, title: '', body: '' })

function formatDate(dt) {
  return new Date(dt).toLocaleDateString(locale.value)
}

function submitReview() {
  useForm({ ...form, product_id: props.productId, order_id: props.orderId }).post(route('tenant.review.store'), {
    onSuccess: () => {
      form.rating = 0
      form.title = ''
      form.body = ''
    },
  })
}
</script>
