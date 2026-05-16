<template>
  <Head :title="t('common.my_account')" />

  <ClientLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ t('common.my_account') }}</h1>

      <!-- Flash messages -->
      <div
        v-if="$page.props.flash?.success"
        class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-8">
          <button
            @click="activeTab = 'profile'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'profile'
                ? 'border-red-500 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ t('client.account.personal_data') }}
          </button>
          <button
            @click="activeTab = 'orders'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'orders'
                ? 'border-red-500 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ t('client.account.order_history') }}
          </button>
          <button
            @click="activeTab = 'password'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'password'
                ? 'border-red-500 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ t('client.account.change_password') }}
          </button>
          <button
            @click="activeTab = 'downloads'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'downloads'
                ? 'theme-primary-border theme-primary'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ t('client.account.downloaded_files') }}
          </button>
          <button
            @click="activeTab = 'loyalty'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'loyalty'
                ? 'theme-primary-border theme-primary'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            Punkty ({{ loyalty?.points ?? 0 }})
          </button>
          <button
            @click="activeTab = 'badges'"
            :class="[
              'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'badges'
                ? 'theme-primary-border theme-primary'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            {{ t('client.account.badges') }}
          </button>
        </nav>
      </div>

      <!-- Profile Tab -->
      <div v-show="activeTab === 'profile'" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ t('client.account.personal_data') }}</h2>
        <p class="text-sm text-gray-700 mb-1 font-medium" data-customer-name>{{ customer.name }}</p>
        <p class="text-sm text-gray-500 mb-4" data-customer-email>{{ customer.email }}</p>

        <form @submit.prevent="submitProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name') }}</label>
              <input
                id="name"
                name="name"
                v-model="profileForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': profileForm.errors.name }"
              />
              <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('landlord.shopsearch.email')
              }}</label>
              <input
                id="email"
                name="email"
                v-model="profileForm.email"
                type="email"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': profileForm.errors.email }"
              />
              <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
              <input
                id="phone"
                v-model="profileForm.phone"
                type="tel"
                placeholder="123 456 789"
                @blur="profileForm.phone = formatPhone(profileForm.phone)"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
            </div>

            <div>
              <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1"
                >{{ t('client.account.date_of_birth') }}
                <span class="text-gray-400 text-xs font-normal">(opcjonalnie – bonus urodzinowy)</span></label
              >
              <input
                id="date_of_birth"
                v-model="profileForm.date_of_birth"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
              <p v-if="profileForm.errors.date_of_birth" class="mt-1 text-sm text-red-600">
                {{ profileForm.errors.date_of_birth }}
              </p>
            </div>
          </div>

          <hr class="my-4" />
          <h3 class="text-md font-medium text-gray-900">{{ t('common.delivery_address') }}</h3>

          <div>
            <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">{{
              t('client.account.address_street_number_city')
            }}</label>
            <input
              ref="addressInput"
              id="delivery_address"
              v-model="profileForm.delivery_address"
              type="text"
              :placeholder="t('client.account.15_3_kwiatowa_st_krakow')"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
            />
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="profileForm.processing"
              class="px-6 py-2.5 theme-primary-bg hover:opacity-90 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              {{ profileForm.processing ? 'Zapisywanie...' : t('common.save_changes') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Orders Tab -->
      <div v-show="activeTab === 'orders'">
        <div v-if="orders.data.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center">
          <i class="fa-solid fa-bag-shopping text-4xl text-gray-300 mb-3 block"></i>
          <p class="font-medium text-gray-500 mb-1">{{ t('client.account.you_have_no_orders_yet') }}</p>
          <p class="text-sm text-gray-400 mb-4">{{ t('client.account.visit_the_shop_and_place_your') }}</p>
          <Link
            :href="route('tenant.shop')"
            class="inline-block theme-primary-bg hover:opacity-90 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors"
          >
            {{ t('common.go_to_the_shop') }}
          </Link>
        </div>

        <div v-else class="space-y-4">
          <div v-for="order in orders.data" :key="order.id" class="bg-white rounded-lg shadow-md p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
              <div>
                <p class="font-semibold text-gray-900">{{ t('common.order_hash_a', { a: order.order_number }) }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
              </div>
              <div class="flex items-center gap-3">
                <span :class="statusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                  {{ statusLabel(order.status) }}
                </span>
                <span class="text-lg font-bold text-gray-900"
                  >{{ formatPrice(order.total) }} {{ t('common.currency_pln') }}</span
                >
              </div>
            </div>

            <div class="border-t pt-3">
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600 mb-2">
                <span>{{ order.shippingMethod?.name ?? t('common.delivery_2') }}</span>
                <span v-if="order.shipping_address?.city"
                  >{{ order.shipping_address?.street }}, {{ order.shipping_address?.city }}</span
                >
                <span>{{ paymentLabel(order.payment_method) }}</span>
              </div>

              <div class="space-y-1">
                <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
                  <span class="text-gray-700">
                    {{ item.quantity }}x {{ item.name }}
                    <span v-if="item.variant_name" class="text-gray-400">({{ item.variant_name }})</span>
                  </span>
                  <span class="text-gray-500"
                    >{{ formatPrice(item.price * item.quantity) }} {{ t('common.currency_pln') }}</span
                  >
                </div>
              </div>
            </div>

            <div class="mt-3 pt-3 border-t flex flex-wrap gap-4">
              <Link
                v-if="order.status !== 'completed' && order.status !== 'cancelled'"
                :href="route('tenant.order.tracking', order.order_number)"
                class="text-sm text-red-600 hover:text-red-700 font-medium"
              >
                {{ t('client.account.track_the_order') }}
              </Link>
              <a
                :href="route('tenant.order.invoice', order.order_number)"
                target="_blank"
                class="text-sm text-gray-500 hover:text-gray-700"
              >
                <i class="fa-solid fa-print mr-1"></i> {{ t('client.account.invoice_receipt') }}
              </a>
              <Link
                v-if="order.fulfillment_status === 'delivered'"
                :href="route('tenant.rma.create', order.order_number)"
                class="text-sm text-gray-500 hover:text-gray-700"
              >
                <i class="fa-solid fa-rotate-left mr-1"></i> {{ t('common.request_a_return') }}
              </Link>
              <!-- Review button (#26) -->
              <button
                v-if="order.fulfillment_status === 'delivered'"
                @click="openReview(order)"
                class="text-sm text-yellow-600 hover:text-yellow-700 font-medium"
              >
                <i class="fa-solid fa-star mr-1"></i> {{ t('client.account.rate_this_order') }}
              </button>
              <!-- Cancel order button -->
              <button
                v-if="canCancelOrder(order)"
                @click="cancelOrder(order.order_number)"
                class="text-xs text-red-600 hover:text-red-800 font-medium mt-1 block"
              >
                {{ t('client.account.cancel_the_order') }}
              </button>
            </div>
            <!-- Review form (inline) -->
            <div v-if="reviewOrderId === order.id" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <form @submit.prevent="submitReview">
                <p class="font-medium text-gray-900 mb-2">
                  {{ t('common.rate_a_product_from_order', { a: order.order_number }) }}
                </p>
                <div v-if="reviewableItems(order).length > 1" class="mb-3">
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{
                    t('client.account.which_product_are_you_reviewing')
                  }}</label>
                  <select
                    v-model="reviewForm.product_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  >
                    <option v-for="item in reviewableItems(order)" :key="item.product_id" :value="item.product_id">
                      {{ item.name }}
                    </option>
                  </select>
                </div>
                <div class="flex gap-2 mb-3">
                  <button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    @click="reviewForm.rating = star"
                    class="text-2xl"
                    :class="star <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-300'"
                  >
                    <i class="fa-solid fa-star"></i>
                  </button>
                </div>
                <textarea
                  v-model="reviewForm.body"
                  rows="3"
                  required
                  :placeholder="t('common.share_your_thoughts')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-400 mb-2"
                ></textarea>
                <p v-if="reviewError" class="text-red-600 text-sm mb-2" role="alert" aria-live="assertive">
                  {{ reviewError }}
                </p>
                <div class="flex gap-2">
                  <button
                    type="submit"
                    :disabled="reviewForm.processing"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                  >
                    {{ t('client.account.submit_the_rating') }}
                  </button>
                  <button
                    type="button"
                    @click="reviewOrderId = null"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm"
                  >
                    {{ t('common.cancel') }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="orders.links && orders.last_page > 1" class="flex justify-center gap-1 pt-4">
            <template v-for="link in orders.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-2 text-sm rounded-lg transition-colors',
                  link.active
                    ? 'theme-primary-bg text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300',
                ]"
              />
              <span v-else v-html="link.label" class="px-3 py-2 text-sm text-gray-400" />
            </template>
          </div>
        </div>
      </div>

      <!-- Downloads Tab -->
      <div v-show="activeTab === 'downloads'">
        <div v-if="!downloads || downloads.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center">
          <div class="text-4xl mb-3">📁</div>
          <p class="font-medium text-gray-500 mb-1">{{ t('common.no_files_to_download') }}</p>
          <p class="text-sm text-gray-400">{{ t('client.account.digital_purchases_appear_here') }}</p>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="link in downloads"
            :key="link.id"
            class="bg-white rounded-lg shadow-md p-5 flex items-center gap-4"
          >
            <div class="text-3xl">📦</div>
            <div class="flex-1">
              <p class="font-semibold text-gray-900">{{ link.file?.name ?? link.product_name }}</p>
              <p class="text-xs text-gray-500">
                {{
                  t('common.order_hash_a_downloads_b_of_c', {
                    a: link.order_number,
                    b: link.download_count,
                    c: link.max_downloads ?? '∞',
                  })
                }}
              </p>
              <p v-if="link.expires_at" class="text-xs text-gray-400">
                {{ t('common.valid_until_a', { a: formatDate(link.expires_at) }) }}
              </p>
            </div>
            <a
              v-if="!link.is_expired && (link.max_downloads === null || link.download_count < link.max_downloads)"
              :href="route('tenant.download', link.token)"
              class="flex-shrink-0 theme-primary-bg hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
            >
              <i class="fa-solid fa-download mr-1"></i> {{ t('common.download') }}
            </a>
            <span v-else class="flex-shrink-0 text-xs text-gray-400 bg-gray-100 px-3 py-2 rounded-lg">
              {{ link.is_expired ? t('common.expired_2') : t('common.download_limit') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Loyalty Tab -->
      <div v-show="activeTab === 'loyalty'" class="space-y-4">
        <!-- Not enabled -->
        <div v-if="!loyalty?.enabled" class="bg-white rounded-lg shadow-md p-8 text-center">
          <p class="text-gray-500">{{ t('client.account.this_shop_does_not_run_a') }}</p>
        </div>

        <template v-else>
          <!-- Tier card -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ t('common.loyalty_programme') }}</h2>
                <p class="text-sm text-gray-500">{{ t('client.account.collect_points_and_swap_them_for') }}</p>
              </div>
              <div class="flex items-center gap-3">
                <span
                  class="px-4 py-2 rounded-full text-white text-sm font-bold shadow"
                  :style="{ backgroundColor: loyalty?.tier_info?.color ?? '#cd7f32' }"
                >
                  {{ loyalty?.tier_info?.name ?? t('common.bronze') }}
                </span>
                <div class="text-right">
                  <div class="text-2xl font-bold text-gray-900">{{ loyalty?.points ?? 0 }} pkt</div>
                  <div class="text-xs text-gray-400">
                    {{ t('common.earned_in_total_a_pts', { a: loyalty?.points_earned_total ?? 0 }) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Tier perks -->
            <div class="grid grid-cols-3 gap-3 mb-5 text-center">
              <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ t('common.multiplier') }}</div>
                <div class="font-bold text-gray-800">×{{ loyalty?.tier_info?.multiplier ?? 1 }}</div>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ t('client.account.monthly_bonus') }}</div>
                <div class="font-bold text-gray-800">{{ loyalty?.tier_info?.monthly_bonus ?? 0 }} pkt</div>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ t('client.account.delivery_bonus') }}</div>
                <div class="font-bold text-gray-800">
                  {{
                    (loyalty?.tier_info?.delivery_bonus ?? 0) >= 999
                      ? 'gratis'
                      : (loyalty?.tier_info?.delivery_bonus ?? 0) + ' PLN'
                  }}
                </div>
              </div>
            </div>

            <div v-if="loyalty?.next_tier">
              <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>{{ loyalty.tier_info?.name }}</span>
                <span>{{ loyalty.next_tier?.name }} (brak {{ loyalty.points_to_next }} pkt)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="h-3 rounded-full transition-all"
                  :style="{
                    width: progressPercent + '%',
                    backgroundColor: loyalty.next_tier?.color ?? '#f59e0b',
                  }"
                ></div>
              </div>
              <p class="text-xs text-gray-400 mt-1">
                Brakuje {{ loyalty.points_to_next }} pkt do poziomu {{ loyalty.next_tier?.name }}
              </p>
            </div>
            <div v-else class="text-center py-3">
              <p class="text-green-600 font-semibold">
                {{ t('client.account.you_have_reached_the_top_tier') }} <i class="fa-solid fa-trophy"></i>
              </p>
            </div>
          </div>

          <!-- Loyalty program description -->
          <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">
              {{ t('client.account.how_does_the_loyalty_programme_work') }}
            </h4>
            <ul class="text-xs text-blue-700 space-y-1">
              <li>{{ t('client.account.every_order_earns_you_loyalty_points') }}</li>
              <li>{{ t('client.account.points_can_be_swapped_for_money') }}</li>
              <li>{{ t('client.account.the_higher_the_tier_the_more') }}</li>
              <li>{{ t('client.account.special_rewards_are_in_the_rewards') }}</li>
            </ul>
          </div>

          <!-- Available rewards -->
          <div v-if="loyalty?.available_rewards?.length" class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-md font-semibold text-gray-900 mb-4">{{ t('client.account.available_rewards') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="reward in loyalty.available_rewards"
                :key="reward.id"
                class="border rounded-lg p-4 transition-colors"
                :class="reward.affordable ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50 opacity-60'"
              >
                <div class="flex justify-between items-start gap-2">
                  <div>
                    <p class="font-medium text-sm text-gray-800">{{ reward.name }}</p>
                    <p v-if="reward.description" class="text-xs text-gray-500 mt-0.5">{{ reward.description }}</p>
                    <p class="text-xs mt-1">
                      <span v-if="reward.type === 'fixed_discount'" class="text-green-600 font-medium"
                        >-{{ reward.value }} PLN</span
                      >
                      <span v-else-if="reward.type === 'percent_discount'" class="text-green-600 font-medium"
                        >-{{ reward.value }}%</span
                      >
                      <span v-else-if="reward.type === 'free_delivery'" class="text-green-600 font-medium">{{
                        t('common.free_delivery')
                      }}</span>
                      <span v-else class="text-purple-600 font-medium"
                        >{{ reward.product_name }}{{ reward.variant_name ? ` (${reward.variant_name})` : '' }}</span
                      >
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <div class="text-sm font-bold text-blue-600">{{ reward.cost_points }} pkt</div>
                    <div v-if="!reward.affordable" class="text-xs text-gray-400">
                      brak {{ reward.cost_points - loyalty.points }} pkt
                    </div>
                    <a
                      v-else
                      :href="route('tenant.checkout') + '?reward_id=' + reward.id"
                      class="inline-block mt-1 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors"
                      >{{ t('common.order_now') }}</a
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Points history -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-md font-semibold text-gray-900 mb-4">{{ t('common.points_history') }}</h3>
            <div v-if="!loyalty?.history?.length" class="text-center py-6 text-gray-400">
              {{ t('common.no_points_history') }}
            </div>
            <div v-else class="divide-y divide-gray-100">
              <div v-for="(entry, idx) in loyalty.history" :key="idx" class="flex items-center justify-between py-3">
                <div>
                  <p class="text-sm text-gray-800">{{ entry.description }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(entry.created_at) }}</p>
                </div>
                <span :class="entry.points >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold text-sm">
                  {{ entry.points >= 0 ? '+' : '' }}{{ entry.points }} pkt
                </span>
              </div>
            </div>
          </div>

          <!-- Redemption history -->
          <div v-if="loyalty?.redemptions?.length" class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-md font-semibold text-gray-900 mb-4">
              {{ t('client.account.reward_redemption_history') }}
            </h3>
            <div class="divide-y divide-gray-100">
              <div v-for="(r, idx) in loyalty.redemptions" :key="idx" class="flex items-center justify-between py-3">
                <div>
                  <p class="text-sm text-gray-800">
                    {{ r.reward_name || r.description || t('common.points_redeemed') }}
                  </p>
                  <p class="text-xs text-gray-400">{{ formatDate(r.created_at) }}</p>
                </div>
                <div class="text-right">
                  <div class="text-sm font-semibold text-red-600">-{{ r.points_spent }} pkt</div>
                  <div v-if="r.discount_value > 0" class="text-xs text-green-600">-{{ r.discount_value }} PLN</div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Badges Tab -->
      <div v-show="activeTab === 'badges'" class="space-y-4">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('client.account.my_badges') }}</h2>

          <div v-if="!badges?.length" class="text-center py-8 text-gray-400">
            <div class="text-5xl mb-3">🏅</div>
            <p class="font-medium">{{ t('client.account.you_have_no_badges_yet') }}</p>
            <p class="text-sm mt-1">{{ t('client.account.shop_write_reviews_and_refer_friends') }}</p>
          </div>

          <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <div
              v-for="badge in badges"
              :key="badge.id"
              class="flex flex-col items-center gap-2 p-4 rounded-xl border transition-colors"
              :class="
                badge.earned_at
                  ? 'theme-primary-border theme-primary-bg-light'
                  : 'border-gray-200 bg-gray-50 opacity-50'
              "
              :title="badge.description"
            >
              <div class="text-4xl">{{ badge.icon ?? '🏅' }}</div>
              <p class="text-sm font-semibold text-center text-gray-800">{{ badge.name }}</p>
              <p v-if="badge.earned_at" class="text-xs theme-primary font-medium">
                {{ formatDate(badge.earned_at) }}
              </p>
              <p v-else class="text-xs text-gray-400 text-center">
                {{ badge.description ?? 'Jeszcze nie zdobyta' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Password Tab -->
      <div v-show="activeTab === 'password'" class="space-y-6">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('client.account.change_password') }}</h2>

          <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">
            <div>
              <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('client.account.current_password')
              }}</label>
              <input
                id="current_password"
                name="current_password"
                v-model="passwordForm.current_password"
                type="password"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': passwordForm.errors.current_password }"
              />
              <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                {{ passwordForm.errors.current_password }}
              </p>
            </div>

            <div>
              <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('common.new_password')
              }}</label>
              <input
                id="new_password"
                name="password"
                v-model="passwordForm.password"
                type="password"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                :class="{ 'border-red-500': passwordForm.errors.password }"
              />
              <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                {{ passwordForm.errors.password }}
              </p>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{
                t('common.repeat_new_password')
              }}</label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
            </div>

            <div class="flex justify-end pt-2">
              <button
                type="submit"
                :disabled="passwordForm.processing"
                class="px-6 py-2.5 theme-primary-bg hover:opacity-90 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
              >
                {{ passwordForm.processing ? 'Zapisywanie...' : t('common.change_password') }}
              </button>
            </div>
          </form>
        </div>

        <!-- GDPR: Export my data -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ t('client.account.your_data_gdpr') }}</h2>
          <p class="text-sm text-gray-600 mb-4">
            {{ t('client.account.download_a_copy_of_everything_we') }}
          </p>
          <a
            :href="route('tenant.gdpr.export')"
            class="inline-block px-5 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg"
          >
            <i class="fa-solid fa-download mr-1"></i> {{ t('client.account.download_my_data') }}
          </a>
        </div>

        <!-- GDPR: Delete account -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-red-100">
          <h2 class="text-lg font-semibold text-red-700 mb-2">{{ t('client.account.delete_account') }}</h2>
          <p class="text-sm text-gray-600 mb-4">
            {{ t('client.account.deleting_your_account_cannot_be_undone') }}
          </p>
          <button
            type="button"
            @click="showDeleteConfirm = true"
            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg"
          >
            {{ t('client.account.delete_my_account') }}
          </button>

          <!-- Confirm dialog -->
          <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
              <h3 class="text-lg font-bold text-gray-900 mb-2">{{ t('client.account.are_you_sure') }}</h3>
              <p class="text-sm text-gray-600 mb-6">{{ t('client.account.this_cannot_be_undone_your_account') }}</p>
              <div class="flex gap-3 justify-end">
                <button
                  @click="showDeleteConfirm = false"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm"
                >
                  {{ t('common.cancel') }}
                </button>
                <Link
                  :href="route('tenant.account.destroy')"
                  method="delete"
                  as="button"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium"
                >
                  {{ t('client.account.yes_delete_my_account') }}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  customer: Object,
  orders: Object,
  downloads: { type: Array, default: () => [] },
  loyalty: Object,
  badges: { type: Array, default: () => [] },
})

const progressPercent = computed(() => {
  if (!props.loyalty?.next_tier) return 100
  const tierMin = props.loyalty.tier_info?.min ?? 0
  const nextMin = props.loyalty.next_tier?.min ?? 1
  const current = props.loyalty.points ?? 0
  const progress = ((current - tierMin) / (nextMin - tierMin)) * 100
  return Math.min(100, Math.max(0, Math.round(progress)))
})

const activeTab = ref('profile')
const showDeleteConfirm = ref(false)

// Reviews (#26)
const page = usePage()
const reviewOrderId = ref(null)
const reviewLocalError = ref('')
const reviewForm = useForm({ product_id: null, order_id: null, rating: 5, body: '' })

// An order can contain several products — only distinct, still-existing
// products (product_id survives a soft reference even if the product was
// later deleted, in which case it can't be reviewed) are offered.
const reviewableItems = (order) => {
  const seen = new Set()
  return (order.items ?? []).filter((item) => {
    if (!item.product_id || seen.has(item.product_id)) return false
    seen.add(item.product_id)
    return true
  })
}

const openReview = (order) => {
  reviewOrderId.value = order.id
  reviewLocalError.value = ''
  reviewForm.reset()
  reviewForm.clearErrors()
  reviewForm.order_id = order.id
  reviewForm.product_id = reviewableItems(order)[0]?.product_id ?? null
}

// reviewError also surfaces back()->with('error', ...) flashes (e.g. "already
// reviewed this product") — those aren't validation errors, so useForm's own
// .errors never catches them; they only show up in the shared flash prop
// after the redirect this POST triggers.
const reviewError = computed(
  () => reviewForm.errors.product_id || reviewForm.errors.rating || reviewForm.errors.body || reviewLocalError.value,
)

const submitReview = () => {
  if (!reviewForm.product_id) {
    reviewLocalError.value = t('common.nothing_in_this_order_can_be')
    return
  }
  reviewLocalError.value = ''
  reviewForm.post(route('tenant.review.store'), {
    preserveScroll: true,
    onSuccess: () => {
      if (page.props.flash?.error) {
        reviewLocalError.value = page.props.flash.error
      } else {
        reviewOrderId.value = null
      }
    },
  })
}

const canCancelOrder = (order) => {
  if (!['pending', 'accepted'].includes(order.status)) return false
  const created = new Date(order.created_at)
  const diffMinutes = (Date.now() - created.getTime()) / 60000
  return diffMinutes <= 15
}

const cancelOrder = (orderNumber) => {
  if (!confirm(t('common.are_you_sure_you_want_to'))) return
  router.delete(route('tenant.account.order.cancel', { orderNumber }))
}

const addressInput = ref(null)

const profileForm = useForm({
  name: props.customer.name || '',
  email: props.customer.email || '',
  phone: props.customer.phone || '',
  delivery_address: props.customer.delivery_address
    ? `${props.customer.delivery_address}${props.customer.delivery_city ? ', ' + props.customer.delivery_city : ''}`
    : '',
  delivery_city: '',
  delivery_postal_code: '',
  date_of_birth: props.customer.date_of_birth ? props.customer.date_of_birth.substring(0, 10) : '',
})

onMounted(() => {
  if (!props.googleMapsConfigured || !addressInput.value) return
  const checkMaps = () => {
    if (!window.google?.maps?.places) {
      setTimeout(checkMaps, 200)
      return
    }
    const ac = new window.google.maps.places.Autocomplete(addressInput.value, {
      componentRestrictions: { country: 'pl' },
      fields: ['formatted_address'],
      types: ['address'],
    })
    ac.addListener('place_changed', () => {
      const place = ac.getPlace()
      if (place.formatted_address) profileForm.delivery_address = place.formatted_address
    })
  }
  if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
    const s = document.createElement('script')
    s.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&libraries=places&language=pl`
    s.async = true
    s.onload = checkMaps
    document.head.appendChild(s)
  } else {
    checkMaps()
  }
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const submitProfile = () => {
  profileForm.put(route('tenant.account.update'))
}

const submitPassword = () => {
  passwordForm.put(route('tenant.account.password'), {
    onSuccess: () => passwordForm.reset(),
  })
}

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2).replace('.', ',')
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const statusLabel = (status) => {
  const labels = {
    pending: t('common.pending'),
    awaiting_payment: t('manager.orders.index.awaiting_payment'),
    paid: t('manager.orders.index.paid_2'),
    completed: t('client.account.fulfilled'),
    cancelled: t('manager.orders.index.cancelled'),
    refunded: t('manager.orders.index.refunded_2'),
  }
  return labels[status] || status
}

const statusClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-700',
    awaiting_payment: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const typeLabel = (shippingMethodName) => shippingMethodName || t('common.delivery_2')

const paymentLabel = (method) => {
  const labels = {
    przelewy24: 'Przelewy24',
    payu: 'PayU',
    tpay: 'Tpay',
    stripe: 'Stripe',
    bank_transfer: t('manager.manualordermodal.bank_transfer'),
    cash_on_delivery: t('common.cash_on_delivery'),
  }
  return labels[method] || method
}
</script>
