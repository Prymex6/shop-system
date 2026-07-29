<template>
  <Head :title="title ? t('layout.managerlayout.a_management_panel', { a: title }) : t('common.management_panel')" />
  <div class="min-h-screen bg-gray-100">
    <FlashMessage />

    <!-- Toast -->
    <Transition name="slide-down">
      <div
        v-if="newOrderAlert"
        class="fixed top-4 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl font-medium flex items-center gap-2"
      >
        <i class="fa-solid fa-bell"></i>
        {{ newOrderAlert }}
      </div>
    </Transition>

    <!-- Baner impersonacji -->
    <div
      v-if="impersonating"
      class="bg-yellow-400 text-yellow-900 px-4 py-2 flex items-center justify-between text-sm font-medium"
    >
      <span><i class="fa-solid fa-eye mr-2"></i>{{ t('layout.managerlayout.you_are_viewing_the_panel_as') }}</span>
      <Link
        :href="route('tenant.manager.impersonate.stop')"
        method="post"
        as="button"
        class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700"
      >
        {{ t('layout.managerlayout.end_the_preview') }}
      </Link>
    </div>

    <!-- ═══ TOP NAV ═══════════════════════════════════════════════════════ -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-bold text-blue-600">{{ t('common.manager_panel') }}</h1>
          </div>
          <div class="flex items-center gap-4">
            <LocaleSwitcher />
            <NotificationCenter />
            <QuickControls />
            <span class="text-sm text-gray-600">{{ auth?.name }}</span>
            <a
              :href="route('tenant.shop')"
              target="_blank"
              class="text-gray-600 hover:text-gray-900 text-sm flex items-center gap-1"
            >
              <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
              {{ t('common.preview_the_shop') }}
            </a>
            <Link
              :href="route('tenant.logout')"
              method="post"
              as="button"
              class="text-red-600 hover:text-red-700 text-sm font-medium"
            >
              {{ t('common.sign_out') }}
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex">
      <!-- ═══ SIDEBAR ═══════════════════════════════════════════════════ -->
      <aside class="w-64 bg-white shadow-sm min-h-screen">
        <nav class="mt-5 px-4 pb-8">
          <!-- Dashboard – zawsze -->
          <div class="space-y-1">
            <Link :href="route('tenant.manager.dashboard')" :class="sideClass('dashboard')">
              <i class="fa-solid fa-chart-line mr-3 w-5 text-center text-indigo-500"></i>
              Dashboard
            </Link>
          </div>

          <!-- ═══ Simple mode: accordions ═══════════════════════════ -->
          <template v-if="simpleMode">
            <div class="space-y-1 mt-1">
              <!-- Sales -->
              <button @click="toggleGroup('sprzedaz')" :class="groupBtnClass('sprzedaz')">
                <i class="fa-solid fa-bag-shopping mr-3 w-5 text-center text-indigo-400"></i>
                <span class="flex-1 text-left">{{ t('layout.managerlayout.sales') }}</span>
                <span
                  v-if="newOrderCount > 0"
                  class="mr-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
                  >{{ newOrderCount > 9 ? '9+' : newOrderCount }}</span
                >
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'sprzedaz' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'sprzedaz'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.orders.index')" :class="subClass('orders')">
                  <span class="flex-1">{{ t('common.orders') }}</span>
                  <span
                    v-if="newOrderCount > 0"
                    class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse"
                    >{{ newOrderCount > 9 ? '9+' : newOrderCount }}</span
                  >
                  <span
                    v-if="hasNewOrders && newOrderCount === 0"
                    class="ml-2 bg-orange-500 text-white text-xs font-bold rounded px-1.5 py-0.5 animate-pulse"
                    >{{ t('layout.managerlayout.new') }}</span
                  >
                </Link>
                <Link :href="route('tenant.manager.customers.index')" :class="subClass('customers')">{{
                  t('common.customers')
                }}</Link>
                <Link :href="route('tenant.manager.reviews.index')" :class="subClass('reviews')">{{
                  t('common.reviews')
                }}</Link>
              </div>

              <!-- Produkty -->
              <button @click="toggleGroup('produkty')" :class="groupBtnClass('produkty')">
                <i class="fa-solid fa-box-open mr-3 w-5 text-center text-orange-400"></i>
                <span class="flex-1 text-left">{{ t('common.products') }}</span>
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'produkty' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'produkty'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.products.index')" :class="subClass('products')">{{
                  t('layout.managerlayout.product_list')
                }}</Link>
                <Link :href="route('tenant.manager.categories.index')" :class="subClass('categories')">{{
                  t('common.categories')
                }}</Link>
                <Link :href="route('tenant.manager.attributes.index')" :class="subClass('attributes')">{{
                  t('layout.managerlayout.attributes_colour_size')
                }}</Link>
                <Link :href="route('tenant.manager.inventory.index')" :class="subClass('inventory')">{{
                  t('common.warehouse')
                }}</Link>
                <Link :href="route('tenant.manager.catalog.index')" :class="subClass('catalog')">{{
                  t('layout.managerlayout.catalogue')
                }}</Link>
                <Link :href="route('tenant.manager.badges.index')" :class="subClass('badges')">{{
                  t('layout.managerlayout.product_badges')
                }}</Link>
              </div>

              <!-- Promocje -->
              <button @click="toggleGroup('promocje')" :class="groupBtnClass('promocje')">
                <i class="fa-solid fa-tag mr-3 w-5 text-center text-purple-500"></i>
                <span class="flex-1 text-left">{{ t('layout.managerlayout.promotions') }}</span>
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'promocje' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'promocje'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.discounts.index')" :class="subClass('discounts')">{{
                  t('layout.managerlayout.discount_codes')
                }}</Link>
                <Link :href="route('tenant.manager.sales.index')" :class="subClass('sales')">{{
                  t('layout.managerlayout.price_promotions')
                }}</Link>
                <Link :href="route('tenant.manager.loyalty.index')" :class="subClass('loyalty')">{{
                  t('layout.managerlayout.loyalty')
                }}</Link>
              </div>

              <!-- Logistyka -->
              <button @click="toggleGroup('logistyka')" :class="groupBtnClass('logistyka')">
                <i class="fa-solid fa-truck mr-3 w-5 text-center text-blue-600"></i>
                <span class="flex-1 text-left">{{ t('layout.managerlayout.logistics') }}</span>
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'logistyka' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'logistyka'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.shipping.index')" :class="subClass('shipping')">{{
                  t('common.delivery_2')
                }}</Link>
                <Link :href="route('tenant.manager.supply.index')" :class="subClass('supply')">{{
                  t('common.delivery_returns')
                }}</Link>
                <a :href="route('tenant.staff.fulfillment')" target="_blank" :class="subClass('fulfillment')">{{
                  t('common.order_fulfilment')
                }}</a>
              </div>

              <!-- Komunikacja -->
              <button @click="toggleGroup('komunikacja')" :class="groupBtnClass('komunikacja')">
                <i class="fa-solid fa-comments mr-3 w-5 text-center text-green-600"></i>
                <span class="flex-1 text-left">{{ t('layout.managerlayout.communication') }}</span>
                <span
                  v-if="newSupportCount + newChatCount > 0"
                  class="mr-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
                  >{{ newSupportCount + newChatCount }}</span
                >
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'komunikacja' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'komunikacja'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.chat.index')" :class="subClass('chat')">
                  <span class="flex-1">Live Chat</span>
                  <span
                    v-if="newChatCount > 0"
                    class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse"
                    >{{ newChatCount }}</span
                  >
                </Link>
                <Link :href="route('tenant.manager.support.index')" :class="subClass('support')">
                  <span class="flex-1">{{ t('layout.landlordlayout.support') }}</span>
                  <span
                    v-if="newSupportCount > 0"
                    class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"
                    >{{ newSupportCount }}</span
                  >
                </Link>
              </div>

              <!-- Management -->
              <button @click="toggleGroup('zarzadzanie')" :class="groupBtnClass('zarzadzanie')">
                <i class="fa-solid fa-users mr-3 w-5 text-center text-indigo-500"></i>
                <span class="flex-1 text-left">{{ t('layout.managerlayout.management') }}</span>
                <span
                  v-if="newStaffReportCount > 0"
                  class="mr-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
                  >{{ newStaffReportCount }}</span
                >
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'zarzadzanie' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'zarzadzanie'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.staff.index')" :class="subClass('staff')">{{
                  t('layout.managerlayout.staff')
                }}</Link>
                <Link :href="route('tenant.manager.staff-reports.index')" :class="subClass('staff-reports')">
                  <span class="flex-1">{{ t('layout.managerlayout.staff_reports') }}</span>
                  <span
                    v-if="newStaffReportCount > 0"
                    class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse"
                    >{{ newStaffReportCount }}</span
                  >
                </Link>
                <Link :href="route('tenant.manager.role-permissions.index')" :class="subClass('role-permissions')">{{
                  t('layout.managerlayout.permissions')
                }}</Link>
                <Link :href="route('tenant.manager.tools.index')" :class="subClass('tools')">{{
                  t('common.tools')
                }}</Link>
              </div>

              <!-- Ustawienia -->
              <button @click="toggleGroup('ustawienia')" :class="groupBtnClass('ustawienia')">
                <i class="fa-solid fa-gear mr-3 w-5 text-center text-gray-500"></i>
                <span class="flex-1 text-left">{{ t('common.settings') }}</span>
                <i
                  class="fa-solid text-[10px] text-gray-400"
                  :class="openGroup === 'ustawienia' ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></i>
              </button>
              <div v-show="openGroup === 'ustawienia'" class="ml-4 border-l-2 border-gray-100 pl-2 space-y-0.5">
                <Link :href="route('tenant.manager.homepage-builder.index')" :class="subClass('homepage-builder')">{{
                  t('layout.managerlayout.page_builder')
                }}</Link>
                <Link :href="route('tenant.manager.settings.index')" :class="subClass('settings')">{{
                  t('common.settings')
                }}</Link>
                <Link :href="route('tenant.manager.license')" :class="subClass('license')">{{
                  t('layout.managerlayout.licence')
                }}</Link>
              </div>
            </div>
          </template>

          <!-- ═══ Expanded mode: one flat list rather than accordions ═════ -->
          <template v-else>
            <div class="space-y-1 mt-1">
              <Link :href="route('tenant.manager.orders.index')" :class="sideClass('orders')">
                <i class="fa-solid fa-box mr-3 w-5 text-center text-indigo-400"></i>
                <span class="flex-1">{{ t('common.orders') }}</span>
                <span
                  v-if="newOrderCount > 0"
                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse"
                  >{{ newOrderCount > 9 ? '9+' : newOrderCount }}</span
                >
                <span
                  v-if="hasNewOrders && newOrderCount === 0"
                  class="ml-2 bg-orange-500 text-white text-xs font-bold rounded px-1.5 py-0.5 animate-pulse"
                  >{{ t('layout.managerlayout.new') }}</span
                >
              </Link>
              <Link :href="route('tenant.manager.customers.index')" :class="sideClass('customers')">
                <i class="fa-solid fa-address-book mr-3 w-5 text-center text-indigo-500"></i>
                {{ t('common.customers') }}
              </Link>
              <Link :href="route('tenant.manager.reviews.index')" :class="sideClass('reviews')">
                <i class="fa-solid fa-star mr-3 w-5 text-center text-yellow-500"></i>
                {{ t('common.reviews') }}
              </Link>

              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('layout.managerlayout.range') }}
              </p>

              <Link :href="route('tenant.manager.products.index')" :class="sideClass('products')">
                <i class="fa-solid fa-box-open mr-3 w-5 text-center text-orange-400"></i>
                {{ t('common.products') }}
              </Link>
              <Link :href="route('tenant.manager.categories.index')" :class="sideClass('categories')">
                <i class="fa-solid fa-folder-tree mr-3 w-5 text-center text-amber-500"></i>
                {{ t('common.categories') }}
              </Link>
              <Link :href="route('tenant.manager.attributes.index')" :class="sideClass('attributes')">
                <i class="fa-solid fa-sliders mr-3 w-5 text-center text-lime-600"></i>
                {{ t('layout.managerlayout.attributes') }}
              </Link>
              <Link :href="route('tenant.manager.inventory.index')" :class="sideClass('inventory')">
                <i class="fa-solid fa-warehouse mr-3 w-5 text-center text-stone-500"></i>
                {{ t('common.warehouse') }}
              </Link>
              <Link :href="route('tenant.manager.catalog.index')" :class="sideClass('catalog')">
                <i class="fa-solid fa-layer-group mr-3 w-5 text-center text-cyan-600"></i>
                {{ t('layout.managerlayout.catalogue') }}
              </Link>
              <Link :href="route('tenant.manager.badges.index')" :class="sideClass('badges')">
                <i class="fa-solid fa-certificate mr-3 w-5 text-center text-rose-500"></i>
                {{ t('layout.managerlayout.product_badges') }}
              </Link>
              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('layout.managerlayout.promotions') }}
              </p>

              <Link :href="route('tenant.manager.discounts.index')" :class="sideClass('discounts')">
                <i class="fa-solid fa-tag mr-3 w-5 text-center text-purple-500"></i>
                {{ t('layout.managerlayout.discount_codes') }}
              </Link>
              <Link :href="route('tenant.manager.sales.index')" :class="sideClass('sales')">
                <i class="fa-solid fa-bolt mr-3 w-5 text-center text-yellow-400"></i>
                {{ t('layout.managerlayout.price_promotions') }}
              </Link>
              <Link :href="route('tenant.manager.loyalty.index')" :class="sideClass('loyalty')">
                <i class="fa-solid fa-heart mr-3 w-5 text-center text-pink-500"></i>
                {{ t('common.loyalty_programme') }}
              </Link>

              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('layout.managerlayout.logistics') }}
              </p>

              <Link :href="route('tenant.manager.shipping.index')" :class="sideClass('shipping')">
                <i class="fa-solid fa-truck mr-3 w-5 text-center text-blue-600"></i>
                {{ t('common.delivery_2') }}
              </Link>
              <Link :href="route('tenant.manager.supply.index')" :class="sideClass('supply')">
                <i class="fa-solid fa-rotate-left mr-3 w-5 text-center text-violet-500"></i>
                {{ t('common.delivery_returns') }}
              </Link>
              <a :href="route('tenant.staff.fulfillment')" target="_blank" :class="sideClass('fulfillment')">
                <i class="fa-solid fa-boxes-packing mr-3 w-5 text-center text-blue-400"></i>
                {{ t('common.order_fulfilment') }}
              </a>

              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('layout.managerlayout.communication') }}
              </p>
              <Link :href="route('tenant.manager.chat.index')" :class="sideClass('chat')">
                <i class="fa-solid fa-comments mr-3 w-5 text-center text-green-600"></i>
                <span class="flex-1">Live Chat</span>
                <span
                  v-if="newChatCount > 0"
                  class="ml-auto bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse"
                  >{{ newChatCount > 9 ? '9+' : newChatCount }}</span
                >
              </Link>
              <Link :href="route('tenant.manager.support.index')" :class="sideClass('support')">
                <i class="fa-solid fa-headset mr-3 w-5 text-center text-blue-500"></i>
                <span class="flex-1">{{ t('layout.managerlayout.technical_support') }}</span>
                <span
                  v-if="newSupportCount > 0"
                  class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center"
                  >{{ newSupportCount }}</span
                >
              </Link>

              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('layout.managerlayout.management') }}
              </p>
              <Link :href="route('tenant.manager.staff.index')" :class="sideClass('staff')">
                <i class="fa-solid fa-users mr-3 w-5 text-center text-indigo-500"></i>
                {{ t('layout.managerlayout.staff') }}
              </Link>
              <Link :href="route('tenant.manager.staff-reports.index')" :class="sideClass('staff-reports')">
                <i class="fa-solid fa-flag mr-3 w-5 text-center text-red-500"></i>
                <span class="flex-1">{{ t('common.staff_reports') }}</span>
                <span
                  v-if="newStaffReportCount > 0"
                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse"
                  >{{ newStaffReportCount > 9 ? '9+' : newStaffReportCount }}</span
                >
              </Link>
              <Link :href="route('tenant.manager.role-permissions.index')" :class="sideClass('role-permissions')">
                <i class="fa-solid fa-shield-halved mr-3 w-5 text-center text-blue-600"></i>
                {{ t('common.role_permissions') }}
              </Link>

              <div class="border-t border-gray-200 my-3"></div>
              <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                {{ t('common.tools') }}
              </p>

              <Link :href="route('tenant.manager.homepage-builder.index')" :class="sideClass('homepage-builder')">
                <i class="fa-solid fa-wand-magic-sparkles mr-3 w-5 text-center text-fuchsia-500"></i>
                {{ t('layout.managerlayout.page_builder') }}
              </Link>
              <Link :href="route('tenant.manager.reports.index')" :class="sideClass('reports')">
                <i class="fa-solid fa-chart-bar mr-3 w-5 text-center text-indigo-500"></i>
                {{ t('layout.managerlayout.reports') }}
              </Link>
              <Link :href="route('tenant.manager.tools.index')" :class="sideClass('tools')">
                <i class="fa-solid fa-screwdriver-wrench mr-3 w-5 text-center text-gray-500"></i>
                {{ t('common.tools') }}
              </Link>

              <div class="border-t border-gray-200 my-3"></div>

              <Link :href="route('tenant.manager.settings.index')" :class="sideClass('settings')">
                <i class="fa-solid fa-gear mr-3 w-5 text-center text-gray-500"></i>
                {{ t('common.settings') }}
              </Link>
              <Link :href="route('tenant.manager.license')" :class="sideClass('license')">
                <i class="fa-solid fa-id-card mr-3 w-5 text-center text-indigo-500"></i>
                {{ t('layout.managerlayout.licence') }}
              </Link>
            </div>
          </template>

          <!-- Mode switch -->
          <div class="mt-6">
            <button
              @click="toggleMode"
              class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-400 hover:text-gray-600 border border-dashed border-gray-200 rounded-lg transition-colors"
            >
              <i class="fa-solid w-4 text-center" :class="simpleMode ? 'fa-expand' : 'fa-compress'"></i>
              <span class="flex-1 text-left">{{
                simpleMode ? t('layout.managerlayout.expanded_mode') : t('layout.managerlayout.simple_mode')
              }}</span>
              <span
                class="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                :class="simpleMode ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'"
              >
                {{ simpleMode ? t('layout.managerlayout.simple') : t('common.full') }}
              </span>
            </button>
          </div>
        </nav>
      </aside>

      <!-- ═══ CONTENT ═══════════════════════════════════════════════════ -->
      <main class="flex-1 p-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { Howl } from 'howler'
import FlashMessage from '@/Components/FlashMessage.vue'
import QuickControls from '@/Components/QuickControls.vue'
import NotificationCenter from '@/Components/Manager/NotificationCenter.vue'
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({ title: { type: String, default: '' } })

const page = usePage()
const auth = page.props.auth?.user
const impersonating = computed(() => page.props.impersonating)

// ── Tryb (persystowany) ────────────────────────────────────────────────────
const simpleMode = ref(localStorage.getItem('manager_mode') !== 'advanced')
function toggleMode() {
  simpleMode.value = !simpleMode.value
  localStorage.setItem('manager_mode', simpleMode.value ? 'simple' : 'advanced')
}

// ── Klasy nawigacji ────────────────────────────────────────────────────────
function isActive(section) {
  const url = page.url
  if (section === 'dashboard') return url === '/manager' || url === '/manager/'
  if (section === 'staff') return url.includes('/manager/staff') && !url.includes('/manager/staff-reports')
  return url.includes(`/manager/${section}`)
}

// Styl pizza-system: border-l-4
function sideClass(section) {
  const base = 'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
  return isActive(section)
    ? base + ' bg-blue-50 border-blue-500 text-blue-700'
    : base + ' border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'
}

// Styling for the accordion headings
function groupBtnClass(name) {
  const base =
    'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors w-full text-left bg-transparent cursor-pointer'
  return groupActive(name)
    ? base + ' bg-blue-50 border-blue-500 text-blue-700'
    : base + ' border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'
}

// Styling for the entries inside an accordion
function subClass(section) {
  const base = 'group flex items-center px-3 py-1.5 text-sm border-l-4 transition-colors'
  return isActive(section)
    ? base + ' bg-blue-50 border-blue-500 text-blue-700 font-medium'
    : base + ' border-transparent text-gray-500 hover:bg-gray-50 hover:text-gray-900'
}

// ── Akordeony (tryb prosty) ────────────────────────────────────────────────
const groupMap = {
  sprzedaz: ['orders', 'customers', 'reviews'],
  produkty: ['products', 'categories', 'attributes', 'inventory', 'catalog', 'badges'],
  promocje: ['discounts', 'sales', 'loyalty'],
  logistyka: ['shipping', 'supply', 'fulfillment'],
  komunikacja: ['chat', 'support'],
  zarzadzanie: ['staff', 'staff-reports', 'role-permissions', 'reports', 'homepage-builder', 'page-builder', 'tools'],
  ustawienia: ['settings', 'license'],
}

function detectOpenGroup() {
  const url = page.url
  for (const [group, sections] of Object.entries(groupMap)) {
    for (const s of sections) {
      const match =
        s === 'staff'
          ? url.includes('/manager/staff') && !url.includes('/manager/staff-reports')
          : url.includes(`/manager/${s}`)
      if (match) return group
    }
  }
  return null
}

const openGroup = ref(detectOpenGroup())

function toggleGroup(name) {
  openGroup.value = openGroup.value === name ? null : name
}

function groupActive(name) {
  const url = page.url
  return (
    groupMap[name]?.some((s) =>
      s === 'staff'
        ? url.includes('/manager/staff') && !url.includes('/manager/staff-reports')
        : url.includes(`/manager/${s}`),
    ) ?? false
  )
}

// ── Powiadomienia ──────────────────────────────────────────────────────────
const newOrderAlert = ref(null)
const newOrderCount = ref(0)
const hasNewOrders = ref(false)
const newSupportCount = ref(0)
const newStaffReportCount = ref(0)
const newChatCount = ref(0)

let echo = null
let newOrderTimer = null
let pollInterval = null

const startPolling = () => {
  if (pollInterval) return
  pollInterval = setInterval(() => {
    if (page.url.includes('/manager/orders')) router.reload({ only: ['orders'] })
  }, 60000)
}
const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const notificationSound = new Howl({ src: ['/sounds/notification.mp3'], volume: 0.7, preload: true })

watch(
  () => page.url,
  (url) => {
    if (url.includes('/manager/orders')) {
      newOrderCount.value = 0
      hasNewOrders.value = false
      if (newOrderTimer) {
        clearTimeout(newOrderTimer)
        newOrderTimer = null
      }
    }
    if (url.includes('/manager/support')) newSupportCount.value = 0
    if (url.includes('/manager/staff-reports')) newStaffReportCount.value = 0
    if (url.includes('/manager/chat')) newChatCount.value = 0
  },
)

onMounted(() => {
  if (!import.meta.env.VITE_REVERB_APP_KEY) return
  window.Pusher = Pusher
  if (!window.Echo) {
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT,
      wssPort: import.meta.env.VITE_REVERB_PORT,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      auth: { headers: { 'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '') } },
    })
  }

  const tenantId = page.props.tenant?.id
  if (!tenantId) return

  echo = window.Echo.private(`orders.${tenantId}`).listen('.order.created', (e) => {
    notificationSound.play()
    newOrderAlert.value = t('layout.managerlayout.new_order_a', { a: e.order?.order_number || '' })
    setTimeout(() => {
      newOrderAlert.value = null
    }, 5000)
    if (page.url.includes('/manager/orders')) {
      hasNewOrders.value = true
      if (newOrderTimer) clearTimeout(newOrderTimer)
      newOrderTimer = setTimeout(
        () => {
          hasNewOrders.value = false
        },
        5 * 60 * 1000,
      )
      router.reload({ only: ['orders'] })
    } else {
      newOrderCount.value++
    }
  })

  window.Echo.private(`support.${tenantId}`).listen('.support.message', (e) => {
    notificationSound.play()
    newOrderAlert.value = t('layout.managerlayout.support_replied_a', { a: e.subject })
    setTimeout(() => {
      newOrderAlert.value = null
    }, 7000)
    if (page.url.includes('/manager/support')) router.reload()
    else newSupportCount.value++
  })

  window.Echo.private(`staff-reports.${tenantId}`).listen('.staff-report.created', (e) => {
    notificationSound.play()
    newOrderAlert.value = `Raport od ${e.report?.staff_user?.name || ''}: ${e.report?.title || ''}`
    setTimeout(() => {
      newOrderAlert.value = null
    }, 6000)
    if (page.url.includes('/manager/staff-reports')) router.reload({ only: ['reports', 'newCount'] })
    else newStaffReportCount.value++
  })

  window.Echo.private(`chat-manager.${tenantId}`).listen('.chat.activity', (e) => {
    notificationSound.play()
    newOrderAlert.value = `Czat — ${e.guest_name || t('common.guest')}: ${e.preview}`
    setTimeout(() => {
      newOrderAlert.value = null
    }, 6000)
    if (page.url.includes('/manager/chat')) router.reload({ only: ['conversations'] })
    else newChatCount.value++
  })

  window.Echo.connector?.pusher?.connection?.bind('connected', stopPolling)
  window.Echo.connector?.pusher?.connection?.bind('unavailable', startPolling)
  window.Echo.connector?.pusher?.connection?.bind('disconnected', startPolling)
})

onUnmounted(() => {
  if (newOrderTimer) clearTimeout(newOrderTimer)
  if (echo) {
    const tenantId = page.props.tenant?.id
    if (tenantId) window.Echo?.leave(`orders.${tenantId}`)
  }
  stopPolling()
})
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
