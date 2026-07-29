<template>
  <ManagerLayout>
    <Head :title="t('common.settings')" />

    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.settings') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ t('manager.settings.index.shop_and_system_settings') }}</p>
      </div>

      <form @submit.prevent="saveSettings" novalidate>
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="flex gap-1 overflow-x-auto pb-px flex-wrap">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              @click="activeTab = tab.id"
              class="flex items-center gap-1.5 py-3 px-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
              :class="
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
            >
              <i :class="'fa-solid ' + tab.icon + ' text-xs'"></i>
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- General Tab -->
        <div v-show="activeTab === 'general'" class="bg-white shadow rounded-lg p-6 space-y-6">
          <h2 class="text-lg font-semibold text-gray-900">{{ t('common.about_the_shop') }}</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.shop_name') }}</label>
              <input
                v-model="form.shop_name"
                name="shop_name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.phone') }}</label>
              <input
                v-model="form.shop_phone"
                type="tel"
                placeholder="123 456 789"
                @blur="form.shop_phone = formatPhone(form.shop_phone)"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.email') }}</label>
              <input
                v-model="form.shop_email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.address') }}</label>
              <input
                v-model="form.shop_address"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >NIP <span class="text-gray-400 font-normal text-xs">(opcjonalnie, widoczny na fakturach)</span></label
              >
              <input
                v-model="form.shop_nip"
                type="text"
                maxlength="20"
                placeholder="0000000000"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.currency') }}</label>
              <select
                v-model="form.currency"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="PLN">{{ t('manager.settings.index.pln_polish_z_oty') }}</option>
                <option value="EUR">{{ t('manager.settings.index.eur_euro') }}</option>
                <option value="USD">{{ t('manager.settings.index.usd_us_dollar') }}</option>
                <option value="GBP">{{ t('manager.settings.index.gbp_pound_sterling') }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              t('manager.settings.index.shop_description')
            }}</label>
            <textarea
              v-model="form.shop_description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Place ID</label>
            <input
              v-model="form.google_place_id"
              type="text"
              :placeholder="t('manager.settings.index.e_g_chij')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="mt-1 text-xs text-gray-500">
              {{ t('manager.settings.index.your_google_business_profile_id_google') }}
              <a
                href="https://developers.google.com/maps/documentation/places/web-service/place-id"
                target="_blank"
                class="text-blue-600 hover:underline"
                >{{ t('manager.settings.index.how_do_i_find_my_place') }}</a
              >
            </p>
          </div>
        </div>

        <!-- Appearance Tab -->
        <div v-show="activeTab === 'appearance'" class="space-y-6">
          <div class="bg-white shadow rounded-lg p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.appearance') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.shop_logo')
                }}</label>
                <ImageUpload
                  v-model="form.logo_url"
                  field="logo_url"
                  :hint="t('manager.settings.index.logo_hint')"
                  preview-class="h-20 max-w-[200px] object-contain mx-auto"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                <ImageUpload
                  v-model="form.favicon_url"
                  field="favicon_url"
                  :hint="t('manager.settings.index.favicon_hint')"
                  preview-class="h-12 w-12 object-contain mx-auto"
                />
              </div>
            </div>

            <div class="border-t pt-6">
              <div
                class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700 flex items-start gap-3"
              >
                <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
                <div>
                  {{ t('manager.settings.index.the_hero_slider_images_heading_and') }}
                  <a :href="route('tenant.manager.homepage-builder.index')" class="underline font-medium">{{
                    t('manager.settings.index.the_homepage_builder_hero_block')
                  }}</a
                  >.
                </div>
              </div>
            </div>
          </div>

          <!-- Level B: Theme colors & font -->
          <div class="bg-white shadow rounded-lg p-6 space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.colours_and_typeface') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.set_the_storefront_s_main_colour') }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{
                  t('manager.settings.index.main_accent_colour')
                }}</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.theme_primary_color"
                    type="color"
                    class="h-10 w-16 rounded cursor-pointer border border-gray-300 p-0.5 bg-white"
                  />
                  <input
                    v-model="form.theme_primary_color"
                    type="text"
                    maxlength="7"
                    placeholder="#b91c1c"
                    class="w-28 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <div
                    class="h-8 w-8 rounded-full border border-gray-300 shadow-sm"
                    :style="{ backgroundColor: form.theme_primary_color }"
                  ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.used_in_navigation_buttons_icons_and') }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{
                  t('manager.settings.index.site_typeface')
                }}</label>
                <select
                  v-model="form.theme_font"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="inter">{{ t('manager.settings.index.inter_modern_easy_to_read') }}</option>
                  <option value="roboto">{{ t('manager.settings.index.roboto_classic_neutral') }}</option>
                  <option value="merriweather">{{ t('manager.settings.index.merriweather_elegant_serif') }}</option>
                  <option value="playfair">{{ t('manager.settings.index.playfair_display_luxurious_serif') }}</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.the_font_applies_to_the_whole') }}
                </p>
              </div>
            </div>

            <!-- Live preview -->
            <div class="border rounded-lg p-4 bg-gray-50">
              <p class="text-xs text-gray-500 mb-2 font-medium">{{ t('manager.settings.index.preview') }}</p>
              <div class="flex items-center gap-4 flex-wrap">
                <span class="text-2xl font-bold" :style="{ color: form.theme_primary_color }">{{
                  t('common.shop')
                }}</span>
                <button
                  type="button"
                  class="px-4 py-2 text-white text-sm rounded-lg font-medium"
                  :style="{ backgroundColor: form.theme_primary_color }"
                >
                  {{ t('common.order_now') }}
                </button>
                <span class="text-sm text-gray-600">{{ t('manager.settings.index.plain_text') }}</span>
              </div>
            </div>
          </div>

          <!-- Level A: Custom CSS -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.custom_css') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.advanced_this_css_loads_on_every') }}
              </p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-700">
              {{ t('manager.settings.index.broken_css_can_wreck_the_look') }}
              <code class="font-mono bg-amber-100 px-1 rounded">var(--color-primary)</code>
              {{ t('manager.settings.index.as_the_main_colour') }}
            </div>

            <textarea
              v-model="form.custom_css"
              rows="12"
              placeholder="/* Example: change the cart button's shape */&#10;.cart-btn { border-radius: 0 !important; }&#10;&#10;/* Example: give the hero more room */&#10;.hero-section { min-height: 90vh; }"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
              spellcheck="false"
            ></textarea>
          </div>
        </div>

        <!-- Orders Tab (includes announcement, urgency CTA, vacation) -->
        <div v-show="activeTab === 'orders'" class="space-y-6">
          <!-- Basic order settings -->
          <div class="bg-white shadow rounded-lg p-6 space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('common.orders') }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ t('manager.settings.index.the_basics_of_taking_orders') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.minimum_order_value')
                }}</label>
                <input
                  v-model="form.min_order_value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.0_no_limit') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.free_delivery_from_pln')
                }}</label>
                <input
                  v-model="form.free_shipping_threshold"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.0_off') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.low_stock_threshold')
                }}</label>
                <input
                  v-model="form.low_stock_threshold"
                  type="number"
                  min="0"
                  max="9999"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.warn_when_stock_falls_below_this') }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.add_on_product_at_checkout_order')
                }}</label>
                <select
                  v-model="form.order_bump_product_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option :value="null">{{ t('manager.settings.index.none') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.offered_to_the_customer_just_before') }}
                </p>
              </div>
            </div>
            <div class="space-y-3 pt-2 border-t border-gray-100">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.order_auto_accept" class="h-4 w-4 text-blue-600 rounded" />
                <div>
                  <span class="text-sm font-medium text-gray-700">{{
                    t('manager.settings.index.accept_orders_automatically')
                  }}</span>
                  <p class="text-xs text-gray-500">{{ t('manager.settings.index.once_paid_an_order_goes_into') }}</p>
                </div>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.orders_paused" class="h-4 w-4 text-red-500 rounded" />
                <div>
                  <span class="text-sm font-medium text-gray-700">{{
                    t('manager.settings.index.stop_taking_orders')
                  }}</span>
                  <p class="text-xs text-gray-500">
                    {{ t('manager.settings.index.temporarily_closed_customers_cannot_place_orders') }}
                  </p>
                </div>
              </label>
            </div>
          </div>

          <!-- Announcement Banner -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">
                  {{ t('manager.settings.index.announcement_banner') }}
                </h2>
                <p class="text-sm text-gray-500">{{ t('manager.settings.index.an_information_bar_at_the_top') }}</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.announcement_enabled" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div v-if="form.announcement_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.announcement_text')
                }}</label>
                <input
                  v-model="form.announcement_text"
                  type="text"
                  maxlength="200"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                  :placeholder="t('manager.settings.index.free_delivery_over_150_pln')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.background_colour') }}</label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.announcement_color"
                    type="color"
                    class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                  />
                  <input
                    v-model="form.announcement_color"
                    type="text"
                    maxlength="7"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Urgency CTA -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.urgency_ctas_conversion') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.elements_that_build_urgency_on_the') }}
              </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <!-- Countdown -->
              <div class="border border-gray-100 rounded-xl p-4 flex items-center justify-between gap-4">
                <div>
                  <p class="font-medium text-sm text-gray-800">
                    {{ t('manager.settings.index.countdown_to_the_end_of_the') }}
                  </p>
                  <p class="text-xs text-gray-500 mt-0.5">
                    {{ t('manager.settings.index.a_countdown_while_a_promotion_or') }}
                  </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                  <input type="checkbox" v-model="form.urgency_countdown_enabled" class="sr-only peer" />
                  <div
                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                  ></div>
                </label>
              </div>
              <!-- Low stock -->
              <div class="border border-gray-100 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <p class="font-medium text-sm text-gray-800">{{ t('manager.settings.index.low_stock') }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ t('manager.settings.index.only_x_left') }}</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" v-model="form.urgency_stock_enabled" class="sr-only peer" />
                    <div
                      class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                    ></div>
                  </label>
                </div>
                <div v-if="form.urgency_stock_enabled">
                  <label class="text-xs text-gray-600 block mb-1">{{
                    t('manager.settings.index.threshold_units')
                  }}</label>
                  <input
                    v-model.number="form.urgency_stock_threshold"
                    type="number"
                    min="1"
                    max="50"
                    class="w-24 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
              </div>
              <!-- Viewers -->
              <div class="border border-gray-100 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <p class="font-medium text-sm text-gray-800">{{ t('manager.settings.index.people_viewing') }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                      {{ t('manager.settings.index.x_people_are_viewing_this_product') }}
                    </p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" v-model="form.urgency_viewers_enabled" class="sr-only peer" />
                    <div
                      class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                    ></div>
                  </label>
                </div>
                <div v-if="form.urgency_viewers_enabled" class="flex gap-3">
                  <div>
                    <label class="text-xs text-gray-600 block mb-1">Min</label
                    ><input
                      v-model.number="form.urgency_viewers_min"
                      type="number"
                      min="1"
                      max="100"
                      class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs text-gray-600 block mb-1">Max</label
                    ><input
                      v-model.number="form.urgency_viewers_max"
                      type="number"
                      min="1"
                      max="200"
                      class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                    />
                  </div>
                </div>
              </div>
              <!-- Sold recently -->
              <div class="border border-gray-100 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <p class="font-medium text-sm text-gray-800">{{ t('manager.settings.index.sold_recently') }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                      {{ t('manager.settings.index.x_people_bought_this_in_the') }}
                    </p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" v-model="form.urgency_sold_enabled" class="sr-only peer" />
                    <div
                      class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                    ></div>
                  </label>
                </div>
                <div v-if="form.urgency_sold_enabled" class="flex gap-3">
                  <div>
                    <label class="text-xs text-gray-600 block mb-1">Min</label
                    ><input
                      v-model.number="form.urgency_sold_min"
                      type="number"
                      min="1"
                      max="500"
                      class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs text-gray-600 block mb-1">Max</label
                    ><input
                      v-model.number="form.urgency_sold_max"
                      type="number"
                      min="1"
                      max="1000"
                      class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                    />
                  </div>
                </div>
              </div>
              <!-- Same day shipping -->
              <div class="border border-gray-100 rounded-xl p-4 space-y-3 md:col-span-2">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <p class="font-medium text-sm text-gray-800">{{ t('manager.settings.index.same_day_dispatch') }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                      {{ t('manager.settings.index.counts_down_to_the_cut_off') }}
                    </p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" v-model="form.urgency_delivery_enabled" class="sr-only peer" />
                    <div
                      class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                    ></div>
                  </label>
                </div>
                <div v-if="form.urgency_delivery_enabled" class="flex items-center gap-3">
                  <input
                    v-model="form.urgency_delivery_cutoff"
                    type="time"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                  <span class="text-xs text-gray-500">{{ t('manager.settings.index.e_g_14_00_order_before') }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Vacation Mode -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.holiday_mode') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.pause_the_shop_and_show_customers') }}
              </p>
            </div>
            <div
              class="flex items-start gap-3 p-4 rounded-lg"
              :class="
                form.vacation_mode ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50 border border-gray-200'
              "
            >
              <input
                type="checkbox"
                v-model="form.vacation_mode"
                id="vacation_mode"
                class="h-5 w-5 text-orange-500 rounded mt-0.5"
              />
              <div>
                <label for="vacation_mode" class="font-semibold text-gray-800 text-sm cursor-pointer">{{
                  t('manager.settings.index.turn_holiday_mode_on')
                }}</label>
                <p class="text-xs text-gray-500 mt-1">
                  {{ t('manager.settings.index.customers_cannot_place_orders_they_see') }}
                </p>
              </div>
            </div>
            <div v-if="form.vacation_mode">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.message_for_customers')
              }}</label>
              <textarea
                v-model="form.vacation_message"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 text-sm"
                :placeholder="t('manager.settings.index.we_are_away_until_10_march')"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Payments Tab -->
        <div v-show="activeTab === 'payments'" class="bg-white shadow rounded-lg p-6 space-y-6">
          <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.payment_gateways') }}</h2>
          <p class="text-sm text-gray-500">{{ t('manager.settings.index.turn_payment_gateways_on_and_set') }}</p>

          <!-- Offline payments -->
          <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
              {{ t('manager.settings.index.offline_payments') }}
            </h3>
            <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.payment_cash_on_delivery_enabled"
                class="h-4 w-4 text-blue-600 rounded"
              />
              <span class="text-sm font-medium"
                ><i class="fa-solid fa-money-bill-wave mr-1 text-green-600"></i>
                {{ t('manager.settings.index.cash_on_delivery') }}</span
              >
            </label>
            <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.payment_bank_transfer_enabled"
                class="h-4 w-4 text-blue-600 rounded"
              />
              <span class="text-sm font-medium"
                ><i class="fa-solid fa-building-columns mr-1 text-blue-600"></i>
                {{ t('manager.manualordermodal.bank_transfer') }}</span
              >
            </label>
          </div>

          <!-- Online gateways (test edition only) -->
          <template v-if="$page.props.app_version === 'test'">
            <!-- Przelewy24 -->
            <div class="border rounded-xl overflow-hidden">
              <div class="flex items-center justify-between p-4 bg-gray-50">
                <div class="flex items-center gap-3">
                  <span class="text-xl font-bold text-red-600">P24</span>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">Przelewy24</p>
                    <p class="text-xs text-gray-500">
                      {{ t('manager.settings.index.transfer_blik_card_over_200_methods') }}
                    </p>
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input type="checkbox" v-model="form.payment_p24_enabled" class="h-4 w-4 text-blue-600 rounded" />
                  <span class="text-sm text-gray-700">{{ t('components.quickcontrols.active') }}</span>
                </label>
              </div>
              <div v-if="form.payment_p24_enabled" class="p-4 space-y-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                    <input
                      v-model="form.p24_merchant_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">POS ID</label>
                    <input
                      v-model="form.p24_pos_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">API Key</label>
                    <input
                      v-model="form.p24_api_key"
                      type="password"
                      :placeholder="secretPlaceholder('p24_api_key')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">CRC Key</label>
                    <input
                      v-model="form.p24_crc"
                      type="password"
                      :placeholder="secretPlaceholder('p24_crc')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input type="checkbox" v-model="form.p24_sandbox" class="h-4 w-4 text-blue-600 rounded" />
                  <span class="text-sm text-gray-700">{{ t('manager.settings.index.test_mode_sandbox') }}</span>
                </label>
              </div>
            </div>

            <!-- PayU -->
            <div class="border rounded-xl overflow-hidden">
              <div class="flex items-center justify-between p-4 bg-gray-50">
                <div class="flex items-center gap-3">
                  <span class="text-xl font-bold text-[#00b3e3]">PayU</span>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">PayU</p>
                    <p class="text-xs text-gray-500">{{ t('manager.settings.index.fast_transfer_blik_card') }}</p>
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input type="checkbox" v-model="form.payment_payu_enabled" class="h-4 w-4 text-blue-600 rounded" />
                  <span class="text-sm text-gray-700">{{ t('components.quickcontrols.active') }}</span>
                </label>
              </div>
              <div v-if="form.payment_payu_enabled" class="p-4 space-y-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">POS ID</label>
                    <input
                      v-model="form.payu_pos_id"
                      type="text"
                      placeholder="np. 300746"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">MD5 Signature Key</label>
                    <input
                      v-model="form.payu_signature_key"
                      type="password"
                      :placeholder="secretPlaceholder('payu_signature_key')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">OAuth Client ID</label>
                    <input
                      v-model="form.payu_client_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">OAuth Client Secret</label>
                    <input
                      v-model="form.payu_client_secret"
                      type="password"
                      :placeholder="secretPlaceholder('payu_client_secret')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :true-value="'sandbox'"
                    :false-value="'production'"
                    v-model="form.payu_mode"
                    class="h-4 w-4 text-blue-600 rounded"
                  />
                  <span class="text-sm text-gray-700">{{ t('manager.settings.index.test_mode_sandbox') }}</span>
                </label>
              </div>
            </div>

            <!-- Tpay -->
            <div class="border rounded-xl overflow-hidden">
              <div class="flex items-center justify-between p-4 bg-gray-50">
                <div class="flex items-center gap-3">
                  <span class="text-xl font-bold text-[#3d9bff]">Tpay</span>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">Tpay</p>
                    <p class="text-xs text-gray-500">{{ t('manager.settings.index.fast_transfers_and_blik') }}</p>
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input type="checkbox" v-model="form.payment_tpay_enabled" class="h-4 w-4 text-blue-600 rounded" />
                  <span class="text-sm text-gray-700">{{ t('components.quickcontrols.active') }}</span>
                </label>
              </div>
              <div v-if="form.payment_tpay_enabled" class="p-4 space-y-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Client ID</label>
                    <input
                      v-model="form.tpay_client_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Client Secret</label>
                    <input
                      v-model="form.tpay_client_secret"
                      type="password"
                      :placeholder="secretPlaceholder('tpay_client_secret')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{
                      t('manager.settings.index.notification_security_code')
                    }}</label>
                    <input
                      v-model="form.tpay_notification_secret"
                      type="password"
                      :placeholder="secretPlaceholder('tpay_notification_secret')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                      {{ t('manager.settings.index.find_it_in_the_tpay_panel') }}
                    </p>
                  </div>
                </div>
                <label class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :true-value="'sandbox'"
                    :false-value="'production'"
                    v-model="form.tpay_mode"
                    class="h-4 w-4 text-blue-600 rounded"
                  />
                  <span class="text-sm text-gray-700">{{ t('manager.settings.index.test_mode_sandbox') }}</span>
                </label>
              </div>
            </div>
          </template>

          <!-- Komunikat dla wersji stabilnej – tylko dla super admina -->
          <div
            v-else-if="$page.props.impersonating"
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
          >
            <p class="font-semibold mb-1">{{ t('manager.settings.index.online_payment_gateways_unavailable') }}</p>
            <p>
              {{ t('manager.settings.index.p24_payu_and_tpay_integrations_are') }} <strong>testowej</strong
              >{{ t('manager.settings.index.to_turn_it_on_set') }}
              <code class="bg-amber-100 px-1 rounded">APP_VERSION=test</code> w pliku
              <code class="bg-amber-100 px-1 rounded">.env</code>.
            </p>
          </div>
        </div>

        <!-- Notifications Tab (email + SMS) -->
        <div v-show="activeTab === 'notifications'" class="space-y-6">
          <!-- Email notifications -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.email_notifications') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.notifications_sent_to_the_shop_s') }}
              </p>
            </div>
            <div class="space-y-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.notification_sound_enabled"
                  class="h-4 w-4 text-blue-600 rounded"
                />
                <div>
                  <span class="text-sm font-medium text-gray-700">{{
                    t('manager.settings.index.sound_on_a_new_order')
                  }}</span>
                  <p class="text-xs text-gray-500">{{ t('manager.settings.index.a_sound_in_the_manager_panel') }}</p>
                </div>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.notification_email_enabled"
                  class="h-4 w-4 text-blue-600 rounded"
                />
                <div>
                  <span class="text-sm font-medium text-gray-700">{{
                    t('manager.settings.index.email_on_a_new_order')
                  }}</span>
                  <p class="text-xs text-gray-500">{{ t('manager.settings.index.sent_the_moment_an_order_is') }}</p>
                </div>
              </label>
            </div>
            <div v-if="form.notification_email_enabled">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.email_address_for_notifications')
              }}</label>
              <input
                v-model="form.notification_email_address"
                type="email"
                class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- SMS notifications -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.text_notifications') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.smsapi_pl_integration_customers_get_a') }}
              </p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.sms_enabled" class="h-4 w-4 text-blue-600 rounded" />
              <span class="text-sm font-medium text-gray-700">{{ t('manager.settings.index.text_messages_on') }}</span>
            </label>
            <div v-if="form.sms_enabled" class="space-y-4 pt-2 border-t border-gray-100">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{
                    t('manager.settings.index.smsapi_pl_api_token_oauth2')
                  }}</label>
                  <input
                    v-model="form.smsapi_token"
                    type="password"
                    :placeholder="secretPlaceholder('smsapi_token', 'Token Bearer z panelu SMSAPI → API → OAuth2')"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{
                    t('manager.settings.index.sender_name_11_characters_max')
                  }}</label>
                  <input
                    v-model="form.sms_sender_name"
                    type="text"
                    maxlength="11"
                    :placeholder="t('manager.settings.index.e_g_shop')"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    {{ t('manager.settings.index.letters_and_digits_only_no_spaces') }}
                  </p>
                </div>
              </div>
              <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-700">
                {{ t('manager.settings.index.texts_go_out_on_awaiting_payment') }}
              </div>
            </div>
          </div>
        </div>

        <!-- Integrations Tab -->
        <div v-show="activeTab === 'integrations'" class="space-y-6">
          <!-- Social Media -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.social_media') }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ t('manager.settings.index.the_links_appear_as_icons_in') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook
                </label>
                <input
                  v-model="form.facebook_url"
                  type="url"
                  :placeholder="t('manager.settings.index.https_facebook_com_your_shop')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <i class="fa-brands fa-instagram text-pink-500 mr-1"></i> Instagram
                </label>
                <input
                  v-model="form.instagram_url"
                  type="url"
                  :placeholder="t('manager.settings.index.https_instagram_com_your_shop')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <i class="fa-brands fa-tiktok mr-1 text-gray-700"></i> TikTok
                </label>
                <input
                  v-model="form.tiktok_url"
                  type="url"
                  :placeholder="t('manager.settings.index.https_tiktok_com_your_shop')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>

          <!-- Google Analytics -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Google Analytics (GA4)</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.track_traffic_and_behaviour_on_your') }}
              </p>
            </div>

            <div class="max-w-md">
              <label class="block text-sm font-medium text-gray-700 mb-1">Measurement ID</label>
              <input
                v-model="form.google_analytics_id"
                type="text"
                :placeholder="t('manager.settings.index.e_g_g_xxxxxxxxxx')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">
                {{ t('manager.settings.index.find_it_in_google_analytics_admin') }}
                <code class="bg-gray-100 px-1 rounded">G-XXXXXXXXXX</code>)
              </p>
            </div>
          </div>

          <!-- Facebook Pixel -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Facebook Pixel</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.measure_the_performance_of_meta_ads') }}
              </p>
            </div>

            <div class="max-w-md">
              <label class="block text-sm font-medium text-gray-700 mb-1">Pixel ID</label>
              <input
                v-model="form.facebook_pixel_id"
                type="text"
                placeholder="np. 123456789012345"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">
                {{ t('manager.settings.index.find_it_in_meta_ads_manager') }}
              </p>
            </div>
          </div>

          <!-- TikTok Pixel -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">TikTok Pixel</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.measure_tiktok_ad_performance_lets_campaigns') }}
              </p>
            </div>

            <div class="max-w-md">
              <label class="block text-sm font-medium text-gray-700 mb-1">Pixel ID</label>
              <input
                v-model="form.tiktok_pixel_id"
                type="text"
                :placeholder="t('manager.settings.index.e_g_c1a2b3c4d5e6f7g8h9i0')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">
                {{ t('manager.settings.index.find_it_in_tiktok_ads_manager') }}
              </p>
            </div>
          </div>

          <!-- InPost -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.inpost_parcel_lockers') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.inpost_api_integration_for_parcel_locker') }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-2xl">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.inpost_api_token')
                }}</label>
                <input
                  v-model="form.inpost_api_token"
                  type="password"
                  :placeholder="secretPlaceholder('inpost_api_token', 'Token z panelu InPost')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Organization ID</label>
                <input
                  v-model="form.inpost_organization_id"
                  type="text"
                  placeholder="np. 12345"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.find_it_in_the_inpost_panel') }}</p>
              </div>
            </div>
          </div>

          <!-- Weekly Reports -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.weekly_reports') }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ t('manager.settings.index.a_weekly_sales_summary_sent_by') }}</p>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.weekly_report_enabled" class="h-4 w-4 text-blue-600 rounded" />
              <span class="text-sm font-medium text-gray-700">{{ t('manager.settings.index.weekly_reports_on') }}</span>
            </label>
            <p class="text-xs text-gray-500">
              {{ t('manager.settings.index.the_report_goes_out_every_monday') }}
              <code class="bg-gray-100 px-1 rounded">php artisan reports:weekly</code>
              {{ t('manager.settings.index.as_a_cron_job_once_a') }}
            </p>
          </div>

          <!-- Trustpilot / Google Reviews -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.trustpilot_google_reviews') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.the_review_platform_shown_on_the') }}
              </p>
            </div>
            <div class="max-w-sm">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.trustpilot_business_id')
              }}</label>
              <input
                v-model="form.trustpilot_business_id"
                type="text"
                :placeholder="t('manager.settings.index.e_g_your_company_com')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">
                {{ t('manager.settings.index.find_it_in_trustpilot_settings_business') }}
              </p>
            </div>
          </div>

          <!-- Multi-currency -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.currencies') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.enabled_currencies_customers_can_switch_between') }}
              </p>
            </div>
            <div class="max-w-xl">
              <label class="block text-sm font-medium text-gray-700 mb-2">{{
                t('manager.settings.index.available_currencies')
              }}</label>
              <div class="flex flex-wrap gap-3">
                <label
                  v-for="c in ['PLN', 'EUR', 'USD', 'GBP', 'CZK']"
                  :key="c"
                  class="flex items-center gap-2 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="c"
                    v-model="form.enabled_currencies"
                    class="h-4 w-4 text-blue-600 rounded"
                  />
                  <span class="text-sm text-gray-700">{{ currencyFlag(c) }} {{ c }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Language -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.shop_language') }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ t('manager.settings.index.storefront_language') }}</p>
            </div>
            <div class="max-w-xs">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.default_language')
              }}</label>
              <select
                v-model="form.shop_language"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="pl">🇵🇱 Polski</option>
                <option value="en">🇬🇧 English</option>
              </select>
            </div>
          </div>
        </div>

        <!-- AI Tab -->
        <div v-show="activeTab === 'ai'" class="space-y-6">
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.ai_description_generation') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.api_setup_for_generating_product_descriptions') }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.ai_provider')
                }}</label>
                <select
                  v-model="form.ai_provider"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="openai">OpenAI (GPT-4o-mini)</option>
                  <option value="anthropic">Anthropic (Claude)</option>
                  <option value="gemini">Google Gemini</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.model_optional')
                }}</label>
                <input
                  v-model="form.ai_model"
                  type="text"
                  :placeholder="aiModelPlaceholder"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-400 mt-1">
                  {{ t('manager.settings.index.leave_empty_to_use_the_default') }}
                </p>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >{{ t('manager.settings.index.api_key') }} <span class="text-red-500">*</span></label
                >
                <input
                  v-model="form.ai_api_key"
                  type="password"
                  :placeholder="aiKeyPlaceholder"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                />
                <p class="text-xs text-gray-400 mt-1">
                  <template v-if="form.ai_provider === 'openai'">
                    {{ t('manager.settings.index.openai') }}
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline"
                      >platform.openai.com/api-keys</a
                    >
                  </template>
                  <template v-else-if="form.ai_provider === 'anthropic'">
                    Anthropic:
                    <a
                      href="https://console.anthropic.com/settings/keys"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                      >console.anthropic.com</a
                    >
                  </template>
                  <template v-else-if="form.ai_provider === 'gemini'">
                    {{ t('manager.settings.index.google_ai_studio') }}
                    <a
                      href="https://aistudio.google.com/app/apikey"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                      >aistudio.google.com/app/apikey</a
                    >
                  </template>
                </p>
              </div>
            </div>

            <div
              class="bg-violet-50 border border-violet-100 rounded-lg p-4 text-sm text-violet-700 flex items-start gap-3"
            >
              <i class="fa-solid fa-wand-magic-sparkles text-violet-500 mt-0.5 shrink-0"></i>
              <div>
                <p class="font-semibold mb-1">{{ t('manager.settings.index.how_does_it_work') }}</p>
                <p>
                  {{ t('manager.settings.index.once_an_api_key_is_set') }}
                  <strong>{{ t('manager.settings.index.generate_with_ai') }}</strong
                  >{{ t('manager.settings.index.click_it_to_generate_the_full') }}
                </p>
              </div>
            </div>
          </div>

          <!-- Kling AI Video (inside AI tab) -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.kling_ai_generating_ad_video') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">{{ t('manager.settings.index.free_plan_66_videos_a_month') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >{{ t('manager.settings.index.kling_ai_api_key') }}
                <span class="text-gray-400 font-normal">(opcjonalny)</span></label
              >
              <input
                v-model="form.kling_api_key"
                type="password"
                :placeholder="t('manager.settings.index.paste_the_key_from_platform_kling')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
              />
              <p class="text-xs text-gray-400 mt-1">
                {{ t('manager.settings.index.create_a_free_account_at') }}
                <a href="https://platform.kling.ai" target="_blank" class="text-blue-600 hover:underline"
                  >platform.kling.ai</a
                >
                → API Keys → Create API Key
              </p>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-700 flex items-start gap-3">
              <i class="fa-solid fa-video text-blue-500 mt-0.5 shrink-0"></i>
              <div>
                <p class="font-semibold mb-1">{{ t('manager.settings.index.banner_and_video_generator') }}</p>
                <p>{{ t('manager.settings.index.without_a_key_a_local_canvas') }}</p>
                <a
                  :href="route('tenant.manager.ai-media.banners')"
                  class="inline-block mt-2 text-blue-600 font-medium hover:underline text-xs"
                >
                  <i class="fa-solid fa-arrow-right mr-1"></i
                  >{{ t('manager.settings.index.open_the_banner_generator') }}
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Loyalty Tab (v2) -->
        <div v-show="activeTab === 'loyalty'" class="space-y-6">
          <div class="bg-white shadow rounded-lg p-6 space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('common.loyalty_programme') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.customers_collect_points_climb_tiers_and') }}
              </p>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.loyalty_enabled" class="h-4 w-4 text-blue-600 rounded" />
              <span class="text-sm font-medium text-gray-700">{{
                t('manager.settings.index.loyalty_programme_on')
              }}</span>
            </label>
          </div>

          <div v-if="form.loyalty_enabled" class="space-y-6">
            <!-- Earn mode -->
            <div class="bg-white shadow rounded-lg p-6 space-y-4">
              <h3 class="text-sm font-semibold text-gray-800">
                {{ t('manager.settings.index.how_points_are_earned') }}
              </h3>
              <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.loyalty_earn_mode" value="per_pln" class="h-4 w-4 text-blue-600" />
                  <span class="text-sm text-gray-700">{{ t('manager.settings.index.for_every_1_pln_of_the') }}</span>
                </label>
                <div v-if="form.loyalty_earn_mode === 'per_pln'" class="ml-6">
                  <label class="text-xs text-gray-500 block mb-1">{{
                    t('manager.settings.index.points_per_1_pln')
                  }}</label>
                  <input
                    v-model="form.loyalty_points_per_pln"
                    type="number"
                    min="1"
                    max="100"
                    class="w-24 px-3 py-1.5 border border-gray-300 rounded text-sm"
                  />
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    v-model="form.loyalty_earn_mode"
                    value="per_order"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">{{
                    t('manager.settings.index.a_fixed_number_of_points_per')
                  }}</span>
                </label>
                <div v-if="form.loyalty_earn_mode === 'per_order'" class="ml-6">
                  <label class="text-xs text-gray-500 block mb-1">{{
                    t('manager.settings.index.points_per_order')
                  }}</label>
                  <input
                    v-model="form.loyalty_points_per_order"
                    type="number"
                    min="1"
                    class="w-24 px-3 py-1.5 border border-gray-300 rounded text-sm"
                  />
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.loyalty_earn_mode" value="tiered" class="h-4 w-4 text-blue-600" />
                  <span class="text-sm text-gray-700">{{
                    t('manager.settings.index.tiered_the_larger_the_order_the')
                  }}</span>
                </label>
                <div
                  v-if="form.loyalty_earn_mode === 'tiered'"
                  class="ml-6 p-3 bg-gray-50 rounded-lg text-xs text-gray-500"
                >
                  &lt;50 PLN → 1 pkt/PLN · 50–100 PLN → 1.5 pkt/PLN · &gt;100 PLN → 2 pkt/PLN
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.what_moves_a_customer_up_a')
                }}</label>
                <select
                  v-model="form.loyalty_tier_basis"
                  class="w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                >
                  <option value="ever_earned">{{ t('manager.settings.index.points_earned_all_time') }}</option>
                  <option value="current_balance">{{ t('manager.settings.index.current_points_balance') }}</option>
                </select>
              </div>
            </div>

            <!-- Tiers config -->
            <div class="bg-white shadow rounded-lg p-6 space-y-4">
              <h3 class="text-sm font-semibold text-gray-800">{{ t('manager.settings.index.tiers') }}</h3>
              <p class="text-xs text-gray-500">{{ t('manager.settings.index.point_thresholds_and_perks_for_each') }}</p>
              <div class="space-y-3">
                <div v-for="tier in loyaltyTiersV2" :key="tier.key" class="border border-gray-200 rounded-lg p-3">
                  <div class="flex items-center gap-2 mb-3">
                    <span class="inline-block w-3 h-3 rounded-full" :style="{ background: tier.color }"></span>
                    <span class="font-semibold text-sm">{{ tier.name }}</span>
                  </div>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">{{
                        t('manager.settings.index.min_points')
                      }}</label>
                      <input
                        v-model="tier.min"
                        type="number"
                        min="0"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm"
                        :disabled="tier.key === 'bronze'"
                      />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">{{ t('common.points_multiplier') }}</label>
                      <input
                        v-model="tier.multiplier"
                        type="number"
                        min="1"
                        max="10"
                        step="0.25"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm"
                      />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">{{
                        t('manager.settings.index.monthly_bonus_pts')
                      }}</label>
                      <input
                        v-model="tier.monthly_bonus"
                        type="number"
                        min="0"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm"
                      />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">{{
                        t('manager.settings.index.delivery_bonus_pln_999_free')
                      }}</label>
                      <input
                        v-model="tier.delivery_bonus"
                        type="number"
                        min="0"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bonuses -->
            <div class="bg-white shadow rounded-lg p-6 space-y-4">
              <h3 class="text-sm font-semibold text-gray-800">{{ t('manager.settings.index.one_off_bonuses') }}</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.for_signing_up_pts')
                  }}</label>
                  <input
                    v-model="form.loyalty_bonus_registration"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.for_the_first_order_pts')
                  }}</label>
                  <input
                    v-model="form.loyalty_bonus_first_order"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.for_referring_a_new_customer_pts')
                  }}</label>
                  <input
                    v-model="form.loyalty_bonus_referral"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.birthday_bonus_pts')
                  }}</label>
                  <input
                    v-model="form.loyalty_bonus_birthday"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.birthday_month_multiplier')
                  }}</label>
                  <input
                    v-model="form.loyalty_bonus_birthday_multiplier"
                    type="number"
                    min="1"
                    max="10"
                    step="0.5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
              </div>
            </div>

            <!-- Expiry -->
            <div class="bg-white shadow rounded-lg p-6 space-y-4">
              <h3 class="text-sm font-semibold text-gray-800">{{ t('manager.settings.index.points_expiry') }}</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.points_expire_after_days_0_never')
                  }}</label>
                  <input
                    v-model="form.loyalty_points_expiry_days"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-1">{{
                    t('manager.settings.index.warning_days_before_expiry')
                  }}</label>
                  <input
                    v-model="form.loyalty_expiry_warning_days"
                    type="number"
                    min="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                  />
                </div>
              </div>
              <p class="text-xs text-gray-400">
                {{ t('manager.settings.index.run') }} <code>php artisan loyalty:expire-points</code>
                {{ t('manager.settings.index.as_a_cron_job_once_a_2') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Policies Tab -->
        <div v-show="activeTab === 'policies'" class="space-y-6">
          <!-- Reviews -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('common.product_reviews') }}</h2>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div>
                <p class="font-medium text-gray-800 text-sm">
                  {{ t('manager.settings.index.reviews_need_approving') }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ t('manager.settings.index.new_reviews_are_held_for_moderation') }}
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.reviews_require_approval" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800 text-sm">
                  {{ t('manager.settings.index.reviews_only_after_a_purchase') }}
                </p>
                <p class="text-xs text-gray-500">{{ t('manager.settings.index.a_customer_must_have_bought_the') }}</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.reviews_min_order_required" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
          </div>

          <!-- Refunds & RMA -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ t('manager.settings.index.returns_and_complaints_rma') }}
            </h2>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div>
                <p class="font-medium text-gray-800 text-sm">{{ t('manager.settings.index.returns_rma_on') }}</p>
                <p class="text-xs text-gray-500">
                  {{ t('manager.settings.index.customers_can_request_returns_from_their') }}
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.rma_enabled" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.return_window_days')
                }}</label>
                <input
                  v-model.number="form.refund_window_days"
                  type="number"
                  min="0"
                  max="365"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-400 mt-1">{{ t('manager.settings.index.from_the_purchase_date_0_no') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('manager.settings.index.time_to_raise_an_rma_days')
                }}</label>
                <input
                  v-model.number="form.rma_window_days"
                  type="number"
                  min="0"
                  max="365"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800 text-sm">
                  {{ t('manager.settings.index.approve_rmas_automatically') }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ t('manager.settings.index.return_requests_are_approved_automatically') }}
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.rma_auto_approve" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
          </div>

          <!-- Live Chat -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Live Chat</h2>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div>
                <p class="font-medium text-gray-800 text-sm">{{ t('manager.settings.index.live_chat_on') }}</p>
                <p class="text-xs text-gray-500">{{ t('manager.settings.index.a_floating_chat_widget_on_the') }}</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.chat_enabled" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.welcome_message')
              }}</label>
              <input
                v-model="form.chat_greeting"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('manager.settings.index.hello_how_can_we_help')"
              />
            </div>
          </div>

          <!-- Gift Cards -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('pages.landing.gift_cards') }}</h2>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div>
                <p class="font-medium text-gray-800 text-sm">{{ t('manager.settings.index.gift_cards_on') }}</p>
                <p class="text-xs text-gray-500">{{ t('manager.settings.index.customers_can_buy_and_redeem_gift') }}</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.gift_cards_enabled" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.card_validity_days')
              }}</label>
              <input
                v-model.number="form.gift_card_expiry_days"
                type="number"
                min="0"
                class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p class="text-xs text-gray-400 mt-1">0 = bezterminowo</p>
            </div>
          </div>

          <!-- Fraud Detection -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.fraud_detection') }}</h2>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div>
                <p class="font-medium text-gray-800 text-sm">
                  {{ t('manager.settings.index.automatic_fraud_detection') }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ t('manager.settings.index.orders_are_checked_for_suspicious_activity') }}
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.fraud_detection_enabled" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800 text-sm">
                  {{ t('manager.settings.index.automatically_block_suspicious_orders') }}
                </p>
                <p class="text-xs text-gray-500 text-amber-600">
                  {{ t('manager.settings.index.careful_this_can_block_legitimate_orders') }}
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.fraud_auto_block" class="sr-only peer" />
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                ></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Terms/Privacy Tab (#14) -->
        <div v-show="activeTab === 'terms'" class="space-y-6">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>{{ t('manager.settings.index.available_tokens') }}</strong>
            {{ t('manager.settings.index.these_are_replaced_with_the_shop') }}<br />
            <code class="bg-blue-100 px-1 rounded">{shop_name}</code>
            <code class="bg-blue-100 px-1 rounded ml-1">{shop_address}</code>
            <code class="bg-blue-100 px-1 rounded ml-1">{shop_phone}</code>
            <code class="bg-blue-100 px-1 rounded ml-1">{shop_email}</code>
            <code class="bg-blue-100 px-1 rounded ml-1">{shop_nip}</code>
            <code class="bg-blue-100 px-1 rounded ml-1">{year}</code>
          </div>
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('layout.clientlayout.terms_and_conditions') }}</h2>
            <p class="text-sm text-gray-500">
              {{ t('manager.settings.index.the_text_shown_on_the_page') }} <strong>/regulamin</strong>. Dozwolony HTML
              (h2, h3, p, ul, ol, li).
            </p>
            <textarea
              v-model="form.terms_content"
              rows="18"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('common.privacy_policy') }}</h2>
            <p class="text-sm text-gray-500">
              {{ t('manager.settings.index.the_text_shown_on_the_page') }} <strong>/polityka-prywatnosci</strong>.
              Dozwolony HTML.
            </p>
            <textarea
              v-model="form.privacy_content"
              rows="18"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('common.delivery_and_payment') }}</h2>
            <p class="text-sm text-gray-500">
              {{ t('manager.settings.index.the_text_shown_on_the_page') }}
              <strong>{{ t('manager.settings.index.delivery') }}</strong
              >. Dozwolony HTML.
            </p>
            <textarea
              v-model="form.shipping_content"
              rows="14"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('layout.clientlayout.returns_and_complaints') }}</h2>
            <p class="text-sm text-gray-500">
              {{ t('manager.settings.index.the_text_shown_on_the_page') }} <strong>/zwroty</strong>. Dozwolony HTML.
            </p>
            <textarea
              v-model="form.returns_content"
              rows="14"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('common.faq_frequently_asked_questions') }}</h2>
            <p class="text-sm text-gray-500">
              {{ t('manager.settings.index.the_text_shown_on_the_page') }} <strong>/faq</strong>. Dozwolony HTML.
            </p>
            <textarea
              v-model="form.faq_content"
              rows="14"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
        </div>

        <!-- SEO Tab -->
        <div v-show="activeTab === 'seo'" class="space-y-6">
          <!-- Meta tags -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.homepage_meta_tags') }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.shown_in_google_results_and_when') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('manager.settings.index.meta_title_page_title') }}
                <span class="font-normal text-gray-400 text-xs ml-1">{{
                  t('manager.settings.index.50_60_characters_recommended')
                }}</span>
              </label>
              <input
                v-model="form.seo_title"
                type="text"
                maxlength="120"
                :placeholder="t('manager.settings.index.e_g_xyz_shop_electronics_online')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.leave_empty_to_use_the_shop') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('manager.pagebuilder.editor.meta_description') }}
                <span class="font-normal text-gray-400 text-xs ml-1">{{
                  t('manager.settings.index.150_160_characters_recommended')
                }}</span>
              </label>
              <textarea
                v-model="form.seo_description"
                rows="3"
                maxlength="300"
                :placeholder="t('manager.settings.index.a_short_description_of_the_shop')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.leave_empty_to_use_the_shop_2') }}</p>
            </div>
          </div>

          <!-- Open Graph -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.open_graph_social_sharing') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.the_image_and_description_shown_when') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.og_image_the_sharing_image')
              }}</label>
              <ImageUpload
                v-model="form.seo_og_image"
                field="seo_og_image"
                :hint="t('manager.settings.index.1200_630_px_recommended_jpg_or')"
                preview-class="w-full h-32 object-cover rounded-lg"
              />
              <div class="mt-2">
                <input
                  v-model="form.seo_og_image"
                  type="url"
                  :placeholder="t('manager.settings.index.or_paste_an_image_url')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <p class="mt-1 text-xs text-gray-500">{{ t('manager.settings.index.leave_empty_to_use_the_logo') }}</p>
            </div>
          </div>

          <!-- Search Console verification -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('manager.settings.index.webmaster_tool_verification') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('manager.settings.index.verification_codes_added_to_the_page') }}
              </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <i class="fa-brands fa-google text-blue-500 mr-1"></i> Google Search Console
                </label>
                <input
                  v-model="form.google_site_verification"
                  type="text"
                  placeholder="np. abc123def456..."
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.attribute_content') }}
                  <code class="bg-gray-100 px-1 rounded">content</code> z tagu &lt;meta
                  name="google-site-verification"&gt;
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <i class="fa-brands fa-microsoft text-blue-600 mr-1"></i> Bing Webmaster Tools
                </label>
                <input
                  v-model="form.bing_site_verification"
                  type="text"
                  :placeholder="t('manager.settings.index.e_g_abc123def456')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">
                  {{ t('manager.settings.index.attribute_content') }}
                  <code class="bg-gray-100 px-1 rounded">content</code>
                  {{ t('manager.settings.index.from_the_lt_meta_name_msvalidate') }}
                </p>
              </div>
            </div>
          </div>

          <!-- Robots / Sitemap -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ t('manager.settings.index.robots_and_sitemap') }}</h2>
            </div>
            <div class="space-y-3">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" v-model="form.seo_noindex" class="h-4 w-4 text-blue-600 rounded" />
                <div>
                  <span class="text-sm font-medium text-gray-800">{{
                    t('manager.settings.index.keep_google_from_indexing_the_site')
                  }}</span>
                  <p class="text-xs text-gray-500">{{ t('manager.settings.index.use_this_only_while_the_shop') }}</p>
                </div>
              </label>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.settings.index.sitemap_xml_url')
              }}</label>
              <div class="flex items-center gap-2">
                <input
                  :value="($page.props.ziggy?.url ?? '') + '/sitemap.xml'"
                  type="text"
                  readonly
                  class="flex-1 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-500 font-mono"
                />
                <a
                  :href="($page.props.ziggy?.url ?? '') + '/sitemap.xml'"
                  target="_blank"
                  class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800 border border-blue-200 rounded-lg hover:bg-blue-50"
                  >{{ t('common.open') }}</a
                >
              </div>
              <p class="mt-1 text-xs text-gray-500">
                {{ t('manager.settings.index.submit_this_url_to_google_search') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="mt-6 flex justify-end">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Zapisywanie...' : t('common.save_settings') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ImageUpload from '@/Components/ImageUpload.vue'
import { formatPhone } from '@/utils/phone'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  settings: Object,
  secretsConfigured: Object,
  products: { type: Array, default: () => [] },
})

const secretPlaceholder = (key, fallback = '') => {
  return props.secretsConfigured?.[key] ? t('common.set_type_a_new_value_to') : fallback
}

const page = usePage()
const isTestVersion = computed(() => page.props.app_version === 'test')

const activeTab = ref('general')

const allTabs = [
  { id: 'general', label: t('common.general'), icon: 'fa-store' },
  { id: 'appearance', label: t('common.appearance'), icon: 'fa-palette' },
  { id: 'orders', label: t('common.shop'), icon: 'fa-bag-shopping' },
  { id: 'payments', label: t('common.payments'), icon: 'fa-credit-card' },
  { id: 'notifications', label: t('manager.notificationcenter.notifications'), icon: 'fa-bell' },
  { id: 'ai', label: 'AI', icon: 'fa-wand-magic-sparkles' },
  { id: 'loyalty', label: t('common.loyalty'), icon: 'fa-star' },
  { id: 'policies', label: t('manager.settings.index.policies'), icon: 'fa-shield-halved' },
  { id: 'terms', label: t('manager.settings.index.terms'), icon: 'fa-file-lines' },
  { id: 'seo', label: 'SEO', icon: 'fa-chart-line' },
  { id: 'integrations', label: t('manager.settings.index.integrations'), icon: 'fa-plug' },
]

const tabs = computed(() => allTabs)

const currencyFlag = (c) => ({ PLN: '🇵🇱', EUR: '🇪🇺', USD: '🇺🇸', GBP: '🇬🇧', CZK: '🇨🇿' })[c] ?? c

const form = useForm({
  ...props.settings,
  theme_primary_color: props.settings?.theme_primary_color || '#4f46e5',
  theme_font: props.settings?.theme_font || 'inter',
  custom_css: props.settings?.custom_css || '',
  // Social media
  facebook_url: props.settings?.facebook_url || '',
  instagram_url: props.settings?.instagram_url || '',
  tiktok_url: props.settings?.tiktok_url || '',
  // Analytics
  google_analytics_id: props.settings?.google_analytics_id || '',
  facebook_pixel_id: props.settings?.facebook_pixel_id || '',
  tiktok_pixel_id: props.settings?.tiktok_pixel_id || '',
  // Payment gateways
  payment_cash_on_delivery_enabled: props.settings?.payment_cash_on_delivery_enabled ?? false,
  payment_bank_transfer_enabled: props.settings?.payment_bank_transfer_enabled ?? false,
  payment_p24_enabled: props.settings?.payment_p24_enabled ?? props.settings?.payment_online_enabled ?? false,
  p24_api_key: props.settings?.p24_api_key || '',
  payment_payu_enabled: props.settings?.payment_payu_enabled ?? false,
  payu_pos_id: props.settings?.payu_pos_id || '',
  payu_signature_key: props.settings?.payu_signature_key || '',
  payu_client_id: props.settings?.payu_client_id || '',
  payu_client_secret: props.settings?.payu_client_secret || '',
  payu_mode: props.settings?.payu_mode || 'sandbox',
  payment_tpay_enabled: props.settings?.payment_tpay_enabled ?? false,
  tpay_client_id: props.settings?.tpay_client_id || '',
  tpay_client_secret: props.settings?.tpay_client_secret || '',
  tpay_notification_secret: props.settings?.tpay_notification_secret || '',
  tpay_mode: props.settings?.tpay_mode || 'sandbox',
  // Loyalty v2
  loyalty_enabled: props.settings?.loyalty_enabled ?? false,
  loyalty_earn_mode: props.settings?.loyalty_earn_mode || 'per_pln',
  loyalty_points_per_pln: props.settings?.loyalty_points_per_pln || 1,
  loyalty_points_per_order: props.settings?.loyalty_points_per_order || 10,
  loyalty_tiers: props.settings?.loyalty_tiers || '',
  loyalty_tier_basis: props.settings?.loyalty_tier_basis || 'ever_earned',
  loyalty_points_expiry_days: props.settings?.loyalty_points_expiry_days ?? 0,
  loyalty_expiry_warning_days: props.settings?.loyalty_expiry_warning_days ?? 7,
  loyalty_bonus_registration: props.settings?.loyalty_bonus_registration ?? 50,
  loyalty_bonus_first_order: props.settings?.loyalty_bonus_first_order ?? 100,
  loyalty_bonus_referral: props.settings?.loyalty_bonus_referral ?? 200,
  loyalty_bonus_birthday: props.settings?.loyalty_bonus_birthday ?? 150,
  loyalty_bonus_birthday_multiplier: props.settings?.loyalty_bonus_birthday_multiplier ?? 2.0,
  // Terms / Privacy / Legal
  terms_content: props.settings?.terms_content || '',
  privacy_content: props.settings?.privacy_content || '',
  shipping_content: props.settings?.shipping_content || '',
  returns_content: props.settings?.returns_content || '',
  faq_content: props.settings?.faq_content || '',
  // Vacation
  vacation_mode: props.settings?.vacation_mode ?? false,
  vacation_message: props.settings?.vacation_message || '',
  // Orders
  orders_paused: props.settings?.orders_paused ?? false,
  low_stock_threshold: props.settings?.low_stock_threshold ?? 5,
  free_shipping_threshold: props.settings?.free_shipping_threshold ?? 0,
  order_bump_product_id: props.settings?.order_bump_product_id ?? null,
  currency: props.settings?.currency || 'PLN',
  // InPost
  inpost_api_token: props.settings?.inpost_api_token || '',
  inpost_organization_id: props.settings?.inpost_organization_id || '',
  // Reports
  weekly_report_enabled: props.settings?.weekly_report_enabled ?? false,
  // Trustpilot / Google
  trustpilot_business_id: props.settings?.trustpilot_business_id || '',
  google_place_id: props.settings?.google_place_id || '',
  // Multi-currency
  enabled_currencies: (() => {
    const raw = props.settings?.enabled_currencies
    if (!raw) return ['PLN']
    if (Array.isArray(raw)) return raw
    try {
      return JSON.parse(raw)
    } catch {
      return ['PLN']
    }
  })(),
  // Language
  shop_language: props.settings?.shop_language || 'pl',
  available_languages: (() => {
    const raw = props.settings?.available_languages
    if (!raw) return ['pl']
    if (Array.isArray(raw)) return raw
    try {
      return JSON.parse(raw)
    } catch {
      return ['pl']
    }
  })(),
  // Cookie consent (always active — required by EU law)
  cookie_consent_text: props.settings?.cookie_consent_text || t('common.this_site_uses_cookies_so_that'),
  cookie_consent_accept_label: props.settings?.cookie_consent_accept_label || t('common.i_accept'),
  cookie_consent_policy_url: props.settings?.cookie_consent_policy_url || '/polityka-prywatnosci',
  // SEO
  seo_title: props.settings?.seo_title || '',
  seo_description: props.settings?.seo_description || '',
  seo_og_image: props.settings?.seo_og_image || '',
  google_site_verification: props.settings?.google_site_verification || '',
  bing_site_verification: props.settings?.bing_site_verification || '',
  seo_noindex: props.settings?.seo_noindex ?? false,
  // Announcement banner
  announcement_enabled: props.settings?.announcement_enabled ?? false,
  announcement_text: props.settings?.announcement_text || '',
  announcement_color: props.settings?.announcement_color || '#4f46e5',
  // Reviews moderation
  reviews_require_approval: props.settings?.reviews_require_approval ?? true,
  reviews_min_order_required: props.settings?.reviews_min_order_required ?? true,
  // Refund & RMA
  refund_window_days: props.settings?.refund_window_days ?? 14,
  rma_enabled: props.settings?.rma_enabled ?? true,
  rma_window_days: props.settings?.rma_window_days ?? 30,
  rma_auto_approve: props.settings?.rma_auto_approve ?? false,
  // Live Chat
  chat_enabled: props.settings?.chat_enabled ?? true,
  chat_greeting: props.settings?.chat_greeting || t('manager.settings.index.hello_how_can_we_help'),
  // Gift Cards
  gift_cards_enabled: props.settings?.gift_cards_enabled ?? false,
  gift_card_expiry_days: props.settings?.gift_card_expiry_days ?? 365,
  // Fraud
  fraud_detection_enabled: props.settings?.fraud_detection_enabled ?? true,
  fraud_auto_block: props.settings?.fraud_auto_block ?? false,
  // AI
  ai_provider: props.settings?.ai_provider || 'openai',
  ai_api_key: props.settings?.ai_api_key || '',
  ai_model: props.settings?.ai_model || '',
  kling_api_key: props.settings?.kling_api_key || '',
  // Urgency CTA
  urgency_countdown_enabled: props.settings?.urgency_countdown_enabled ?? true,
  urgency_stock_enabled: props.settings?.urgency_stock_enabled ?? true,
  urgency_stock_threshold: props.settings?.urgency_stock_threshold ?? 5,
  urgency_viewers_enabled: props.settings?.urgency_viewers_enabled ?? false,
  urgency_viewers_min: props.settings?.urgency_viewers_min ?? 5,
  urgency_viewers_max: props.settings?.urgency_viewers_max ?? 24,
  urgency_sold_enabled: props.settings?.urgency_sold_enabled ?? false,
  urgency_sold_min: props.settings?.urgency_sold_min ?? 12,
  urgency_sold_max: props.settings?.urgency_sold_max ?? 84,
  urgency_delivery_enabled: props.settings?.urgency_delivery_enabled ?? false,
  urgency_delivery_cutoff: props.settings?.urgency_delivery_cutoff || '14:00',
})

const aiModelPlaceholder = computed(() => {
  const defaults = { openai: 'gpt-4o-mini', anthropic: 'claude-haiku-4-5-20251001', gemini: 'gemini-2.0-flash' }
  return t('common.default_3') + (defaults[form.ai_provider] ?? 'auto')
})

const aiKeyPlaceholder = computed(() => {
  const hints = { openai: 'sk-...', anthropic: 'sk-ant-...', gemini: 'AIza...' }
  return hints[form.ai_provider] ?? t('manager.settings.index.api_key')
})

// Loyalty tiers v2
const initLoyaltyTiersV2 = () => {
  const raw = props.settings?.loyalty_tiers
  const defaults = [
    {
      key: 'bronze',
      name: t('common.bronze'),
      color: '#cd7f32',
      min: 0,
      multiplier: 1.0,
      monthly_bonus: 0,
      delivery_bonus: 0,
    },
    {
      key: 'silver',
      name: t('manager.settings.index.silver'),
      color: '#9ca3af',
      min: 500,
      multiplier: 1.25,
      monthly_bonus: 50,
      delivery_bonus: 10,
    },
    {
      key: 'gold',
      name: t('common.gold_2'),
      color: '#f59e0b',
      min: 1500,
      multiplier: 1.5,
      monthly_bonus: 150,
      delivery_bonus: 20,
    },
    {
      key: 'platinum',
      name: t('manager.settings.index.platinum'),
      color: '#60a5fa',
      min: 4000,
      multiplier: 2.0,
      monthly_bonus: 400,
      delivery_bonus: 999,
    },
    {
      key: 'diamond',
      name: t('manager.settings.index.diamond'),
      color: '#a78bfa',
      min: 10000,
      multiplier: 3.0,
      monthly_bonus: 1000,
      delivery_bonus: 999,
    },
  ]
  const data = typeof raw === 'object' && raw !== null ? raw : {}
  return defaults.map((d) => ({ ...d, ...data[d.key] }))
}
const loyaltyTiersV2 = reactive(initLoyaltyTiersV2())

// Legacy tiers (kept for backward compat)
const initLoyaltyTiers = () => {
  const raw = props.settings?.loyalty_tiers
  const defaults = [
    { key: 'bronze', label: t('common.bronze'), min_points: 0, reward: '' },
    { key: 'silver', label: t('manager.settings.index.silver'), min_points: 100, reward: '' },
    { key: 'gold', label: t('common.gold_2'), min_points: 300, reward: '' },
    { key: 'platinum', label: t('manager.settings.index.platinum'), min_points: 600, reward: '' },
  ]
  if (!raw) return defaults
  const data = typeof raw === 'string' ? JSON.parse(raw) : raw
  return defaults.map((d) => ({ ...d, ...data[d.key] }))
}
const loyaltyTiers = reactive(initLoyaltyTiers())

const saveSettings = () => {
  // Serialize loyalty tiers v2 as keyed object
  const tiersObj = {}
  loyaltyTiersV2.forEach((t) => {
    tiersObj[t.key] = {
      min: Number(t.min),
      name: t.name,
      color: t.color,
      multiplier: Number(t.multiplier),
      monthly_bonus: Number(t.monthly_bonus),
      delivery_bonus: Number(t.delivery_bonus),
    }
  })
  form.loyalty_tiers = tiersObj
  form.put(route('tenant.manager.settings.update'))
}
</script>
