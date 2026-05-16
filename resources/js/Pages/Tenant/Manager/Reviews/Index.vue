<template>
  <Head :title="t('common.product_reviews')" />

  <ManagerLayout>
    <!-- Approve toast -->
    <Transition name="slide-down">
      <div
        v-if="approveToast"
        class="fixed top-4 right-4 z-50 bg-green-600 text-white px-5 py-3 rounded-xl shadow-xl font-medium flex items-center gap-2"
      >
        <i class="fa-solid fa-check-circle"></i>
        {{ approveToast }}
      </div>
    </Transition>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.product_reviews') }}</h1>
        <div class="text-sm text-gray-500">{{ reviews.total }} recenzji</div>
      </div>

      <!-- Flash -->
      <div
        v-if="$page.props.flash?.success"
        class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-wrap gap-3">
        <select
          v-model="filters.status"
          @change="applyFilters"
          class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
        >
          <option value="">{{ t('common.all_statuses') }}</option>
          <option value="pending">{{ t('common.pending') }}</option>
          <option value="approved">{{ t('manager.refunds.index.approved') }}</option>
        </select>

        <select
          v-model="filters.rating"
          @change="applyFilters"
          class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
        >
          <option value="">{{ t('manager.reviews.index.all_ratings') }}</option>
          <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} ★</option>
        </select>

        <button
          v-if="filters.status || filters.rating"
          @click="clearFilters"
          class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          {{ t('common.clear') }}
        </button>
      </div>

      <!-- Reviews list -->
      <div class="space-y-4">
        <div
          v-for="review in reviews.data"
          :key="review.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200"
          :class="!review.is_approved ? 'border-l-4 border-l-yellow-400' : 'border-l-4 border-l-green-400'"
        >
          <div class="p-5">
            <!-- Header row -->
            <div class="flex items-start justify-between gap-4 mb-3">
              <div class="flex items-center gap-3 min-w-0">
                <div
                  class="flex-shrink-0 w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm"
                >
                  {{ authorInitial(review) }}
                </div>
                <div class="min-w-0">
                  <div class="font-semibold text-gray-900 text-sm">{{ authorName(review) }}</div>
                  <div class="text-xs text-gray-400">{{ formatDate(review.created_at) }}</div>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Verified purchase badge -->
                <span
                  v-if="review.is_verified_purchase"
                  class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium"
                >
                  {{ t('client.productreviews.verified_purchase') }}
                </span>
                <!-- Status badge -->
                <span
                  class="px-2 py-0.5 text-xs rounded-full font-medium"
                  :class="review.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                >
                  {{ review.is_approved ? 'Zatwierdzona' : t('manager.orders.index.pending') }}
                </span>
              </div>
            </div>

            <!-- Product + rating -->
            <div class="flex items-center gap-3 mb-2">
              <div class="flex gap-0.5 text-yellow-400">
                <i
                  v-for="n in 5"
                  :key="n"
                  class="fa-solid fa-star text-sm"
                  :class="n <= review.rating ? 'text-yellow-400' : 'text-gray-200'"
                ></i>
              </div>
              <span class="text-sm font-semibold text-gray-700">{{ review.rating }}/5</span>
              <span v-if="review.product" class="text-sm text-gray-500">· {{ review.product.name }}</span>
            </div>

            <!-- Title + body -->
            <div v-if="review.title" class="font-semibold text-gray-900 mb-1">{{ review.title }}</div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ review.body }}</p>

            <!-- Existing reply -->
            <div v-if="review.reply" class="mt-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
              <div class="text-xs font-semibold text-gray-500 mb-1">{{ t('common.shop_s_reply') }}</div>
              <p class="text-sm text-gray-700">{{ review.reply }}</p>
            </div>

            <!-- Reply form -->
            <div v-if="replyingTo === review.id" class="mt-3">
              <textarea
                v-model="replyText"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                :placeholder="t('common.write_a_reply')"
              ></textarea>
              <div class="flex gap-2 mt-2">
                <button
                  @click="submitReply(review)"
                  :disabled="!replyText.trim() || replying"
                  class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg disabled:opacity-50"
                >
                  {{ replying ? t('common.sending_5') : t('landlord.support.show.send_the_reply') }}
                </button>
                <button
                  @click="cancelReply"
                  class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg"
                >
                  {{ t('common.cancel') }}
                </button>
              </div>
            </div>

            <!-- Points info (pending reviews) -->
            <div
              v-if="!review.is_approved"
              class="mt-3 flex items-center gap-2 text-xs text-blue-600 bg-indigo-50 border border-blue-100 rounded-lg px-3 py-2"
            >
              <i class="fa-solid fa-coins"></i>
              {{ t('manager.reviews.index.once_approved_the_customer_receives') }}
              <strong>{{ t('common.a_points_loyalty', { a: reviewPoints(review) }) }}</strong>
              {{ t('manager.reviews.index.loyalty') }}
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-4 pt-3 border-t border-gray-100">
              <button
                v-if="!review.is_approved"
                @click="approveWithToast(review)"
                class="text-sm text-green-600 hover:text-green-800 font-medium"
              >
                <i class="fa-solid fa-check mr-1"></i>{{ t('common.approve') }}
              </button>
              <button v-else @click="reject(review)" class="text-sm text-yellow-600 hover:text-yellow-800 font-medium">
                <i class="fa-solid fa-eye-slash mr-1"></i>{{ t('manager.reviews.index.hide') }}
              </button>
              <button @click="startReply(review)" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                <i class="fa-solid fa-reply mr-1"></i>{{ review.reply ? t('common.edit_reply') : 'Odpowiedz' }}
              </button>
              <button @click="destroy(review)" class="text-sm text-red-600 hover:text-red-800 font-medium ml-auto">
                <i class="fa-solid fa-trash mr-1"></i>{{ t('common.delete') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="!reviews.data.length" class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-400">
          <i class="fa-solid fa-star text-4xl mb-3"></i>
          <p class="text-lg">{{ t('manager.reviews.index.no_reviews') }}</p>
          <p class="text-sm mt-1">{{ t('manager.reviews.index.reviews_appear_here_once_customers_write') }}</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="reviews.last_page > 1" class="flex justify-center gap-1 mt-6">
        <template v-for="link in reviews.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            v-html="link.label"
            :class="[
              'px-3 py-2 text-sm rounded transition-colors',
              link.active
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300',
            ]"
          />
        </template>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  reviews: Object,
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
  status: props.filters.status ?? '',
  rating: props.filters.rating ?? '',
})

const replyingTo = ref(null)
const replyText = ref('')
const replying = ref(false)
const approveToast = ref(null)

// Points awarded per rating (configurable logic)
const reviewPoints = (review) => {
  const base = 10
  const bonus = (review.rating ?? 3) >= 4 ? 5 : 0
  return base + bonus
}

const approveWithToast = (review) => {
  const pts = reviewPoints(review)
  approve(review)
  approveToast.value = t('manager.reviews.index.approved_a_points_for_the_customer', { a: pts })
  setTimeout(() => {
    approveToast.value = null
  }, 4000)
}

const cancelReply = () => {
  replyingTo.value = null
  replyText.value = ''
}

const applyFilters = () => {
  router.get(
    route('tenant.manager.reviews.index'),
    {
      status: filters.status || undefined,
      rating: filters.rating || undefined,
    },
    { preserveState: true, replace: true },
  )
}

const clearFilters = () => {
  filters.status = ''
  filters.rating = ''
  applyFilters()
}

const approve = (review) => router.patch(route('tenant.manager.reviews.approve', review.id))
const reject = (review) => router.patch(route('tenant.manager.reviews.reject', review.id))

const destroy = (review) => {
  if (confirm(t('common.are_you_sure_you_want_to_3'))) {
    router.delete(route('tenant.manager.reviews.destroy', review.id))
  }
}

const startReply = (review) => {
  replyingTo.value = review.id
  replyText.value = review.reply ?? ''
}

const submitReply = (review) => {
  if (!replyText.value.trim()) return
  replying.value = true
  router.post(
    route('tenant.manager.reviews.reply', review.id),
    {
      reply: replyText.value,
    },
    {
      onSuccess: () => {
        replyingTo.value = null
        replyText.value = ''
      },
      onFinish: () => {
        replying.value = false
      },
    },
  )
}

const authorName = (r) => r.reviewer_name ?? r.customer?.name ?? t('client.productreviews.anonymous')
const authorInitial = (r) => authorName(r).charAt(0).toUpperCase()
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString(locale.value, { year: 'numeric', month: 'short', day: 'numeric' }) : '–'
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
