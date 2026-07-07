<template>
  <div
    class="cookie-wrapper"
    :style="{
      '--primary-color': primaryColor,
      '--primary-color-darker': primaryColor,
      '--primary-color-light': `rgba(${hexToRgb(primaryColor)}, 0.1)`,
    }"
  >
    <button
      v-if="!isOpen && !isModalOpen && !isPolicyModalOpen"
      class="cookie-reopen-btn"
      @click="openModal"
      :aria-label="t('components.cookieconsent.cookie_settings')"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5Z" />
        <path d="M8.5 8.5v.01" />
        <path d="M16 15.5v.01" />
        <path d="M12 12v.01" />
        <path d="M11 17v.01" />
        <path d="M7 14v.01" />
      </svg>
    </button>

    <transition name="slide-up">
      <div v-if="isOpen && !isModalOpen && !isPolicyModalOpen" class="cookie-banner">
        <div class="cookie-content">
          <h3>{{ t('components.cookieconsent.we_respect_your_privacy') }}</h3>
          <p>
            {{ t('components.cookieconsent.this_site_uses_cookies_some_are') }}
            <strong @click="openModal">"{{ t('common.customise') }}"</strong> {{ t('components.cookieconsent.or') }}
            <strong @click="acceptAll">"{{ t('common.accept_all') }}"</strong>
            {{ t('components.cookieconsent.to_carry_on') }}
          </p>
        </div>
        <div class="cookie-actions">
          <div class="cookie-actions-row">
            <button class="btn btn-outline" @click="openModal">{{ t('common.customise') }}</button>
            <button class="btn btn-secondary" @click="denyAll">{{ t('common.reject') }}</button>
          </div>
          <button class="btn btn-primary btn-full" @click="acceptAll">{{ t('common.accept_all') }}</button>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="isModalOpen" class="cookie-modal-backdrop" @click.self="isModalOpen = false">
        <div class="cookie-modal" role="dialog" aria-modal="true">
          <div class="modal-header">
            <h2 class="modal-title">{{ t('components.cookieconsent.adjust_your_consent_preferences') }}</h2>
            <button class="close-btn" @click="isModalOpen = false">&times;</button>
          </div>

          <div class="modal-body-wrapper">
            <div class="modal-description">
              <p>
                {{ t('components.cookieconsent.we_use_cookies_to_help_you') }}
              </p>
              <p>
                {{ t('components.cookieconsent.cookies_classified_as') }}
                <strong :style="{ color: 'var(--primary-color)' }">{{
                  t('components.cookieconsent.essential')
                }}</strong>
                {{ t('components.cookieconsent.are_kept_in_your_browser_because') }}
              </p>
            </div>

            <div class="dma-link">
              {{ t('components.cookieconsent.full_text') }}
              <a href="#" @click.prevent="openPolicyModal">{{ t('common.privacy_and_cookie_policy') }}</a>
            </div>

            <div class="horizontal-separator"></div>

            <div class="categories-list">
              <div
                v-for="(catData, catKey) in categories"
                :key="catKey"
                class="category-item"
                :class="{ 'is-open': activeAccordion === catKey }"
              >
                <div class="category-header" @click="toggleAccordion(catKey)">
                  <div class="accordion-header-main">
                    <span class="chevron" :class="{ rotated: activeAccordion === catKey }">›</span>
                    <button class="accordion-btn">
                      <span class="category-name">
                        {{ catData.label }}
                        <span v-if="catData.detectedItems.length > 0 && catKey !== 'necessary'" class="badge"
                          >({{ catData.detectedItems.length }})</span
                        >
                      </span>
                    </button>

                    <div class="category-controls">
                      <span v-if="catData.required" class="always-active">{{
                        t('components.cookieconsent.always_on')
                      }}</span>
                      <label v-else class="switch" :class="{ disabled: catData.required }" @click.stop>
                        <input type="checkbox" v-model="catData.enabled" :disabled="catData.required" />
                        <span class="slider round"></span>
                      </label>
                    </div>
                  </div>
                </div>

                <transition name="accordion-slide">
                  <div v-show="activeAccordion === catKey" class="category-body">
                    <p class="category-desc-full">{{ catData.description }}</p>

                    <div class="audit-table">
                      <div v-if="catData.detectedItems.length === 0" class="empty-text">
                        {{ t('components.cookieconsent.no_cookies_to_show_in_this') }}
                      </div>
                      <ul v-else class="cookie-table">
                        <li v-for="(item, index) in catData.detectedItems" :key="index">
                          <div class="table-row type-row">
                            <span class="table-label">{{ t('components.cookieconsent.cookie') }}</span>
                            <span class="table-value bold">{{ item.name }}</span>
                          </div>
                          <div class="table-row source-row">
                            <span class="table-label">{{ t('components.cookieconsent.source_time') }}</span>
                            <span class="table-value break-word">{{
                              item.src || item.duration || t('common.session')
                            }}</span>
                          </div>
                          <div class="table-row description-row">
                            <span class="table-label">{{ t('components.cookieconsent.description') }}</span>
                            <span class="table-value">{{
                              item.description || 'Automatycznie wykryty skrypt/ciasteczko.'
                            }}</span>
                          </div>
                          <div class="status-row">
                            <span class="status-badge" :class="catData.enabled ? 'status-allowed' : 'status-blocked'">
                              {{ catData.enabled ? t('common.active') : 'Zablokowany' }}
                            </span>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <div class="cky-footer-wrapper">
            <span class="cky-footer-shadow"></span>

            <div class="modal-footer">
              <button class="cky-btn cky-btn-reject" @click="denyAll">
                {{ t('components.cookieconsent.reject_all') }}
              </button>
              <button class="cky-btn cky-btn-preferences" @click="savePreferences">
                {{ t('components.cookieconsent.save_preferences') }}
              </button>
              <button class="cky-btn cky-btn-accept" @click="acceptAll">{{ t('common.accept_all') }}</button>
            </div>

            <div class="powered-by">
              <span class="powered-text">{{ t('components.cookieconsent.gdpr_compliance') }}</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="isPolicyModalOpen" class="cookie-modal-backdrop" @click.self="isPolicyModalOpen = false">
        <div class="cookie-modal policy-modal" role="dialog" aria-modal="true">
          <div class="modal-header">
            <h2 class="modal-title">{{ t('common.privacy_and_cookie_policy') }}</h2>
            <button class="close-btn" @click="isPolicyModalOpen = false">&times;</button>
          </div>
          <div class="modal-body-wrapper">
            <PolicyContent :shop-name="shopName || t('common.shop')" :shop-email="shopEmail" />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import PolicyContent from './PolicyContent.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  gaId: { type: String, default: '' },
  pixelId: { type: String, default: '' },
  tiktokPixelId: { type: String, default: '' },
  shopName: { type: String, default: '' },
  shopEmail: { type: String, default: '' },
  primaryColor: { type: String, default: '#4f46e5' },
})

const hexToRgb = (hex) => {
  const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return r ? `${parseInt(r[1], 16)}, ${parseInt(r[2], 16)}, ${parseInt(r[3], 16)}` : '79, 70, 229'
}

const STORAGE_KEY = 'shop_cookie_consent_v1'
const isOpen = ref(false)
const isModalOpen = ref(false)
const isPolicyModalOpen = ref(false)
const activeAccordion = ref('necessary')

const previousConsents = ref({})

const gtagBaseSrc = 'https://www.googletagmanager.com/gtag'

const COOKIES_TO_CLEAR = {
  analytics: ['_ga', '_gid', '_gat', '_gat_.*', '_ga_.*', '_hjSession', '_hjSessionUser'],
  marketing: ['_fbp', '_fbc', 'IDE', 'li_dvs'],
  functional: ['intercom-id', 'zd-client-id'],
}

const PATTERNS = {
  analytics: [/google-analytics/, /googletagmanager/, /gtag/, /hotjar/, /matomo/, /segment\.com/],
  marketing: [
    /facebook\.net/,
    /connect\.facebook/,
    /doubleclick/,
    /googleadservices/,
    /ads\.twitter/,
    /linkedin/,
    /tiktok/,
  ],
  functional: [/intercom/, /zendesk/, /chat/],
}

const categories = reactive({
  necessary: {
    label: t('common.essential_required'),
    description: t('common.essential_cookies_are_what_make_the'),
    required: true,
    enabled: true,
    detectedItems: [
      {
        name: 'shop_cookie_consent_v1',
        duration: t('landlord.tenants.create.1_year'),
        description: t('common.remembers_which_cookies_you_agreed_to'),
      },
    ],
  },
  functional: {
    label: t('components.cookieconsent.functional'),
    description: t('common.functional_cookies_power_extras_such_as'),
    required: false,
    enabled: false,
    detectedItems: [],
  },
  analytics: {
    label: t('components.cookieconsent.analytics_and_statistics'),
    description: t('common.analytics_cookies_google_analytics_for_instance'),
    required: false,
    enabled: false,
    detectedItems: [],
  },
  marketing: {
    label: t('components.cookieconsent.advertising_and_profiling'),
    description: t('common.marketing_cookies_follow_what_you_do'),
    required: false,
    enabled: false,
    detectedItems: [],
  },
})

const blockedQueue = []

const toggleAccordion = (key) => {
  activeAccordion.value = activeAccordion.value === key ? null : key
}

const deleteCookie = (name) => {
  if (name.includes('.*')) {
    const regex = new RegExp('^' + name.replace('.*', '.*') + '$')
    const allCookies = document.cookie.split(';')
    allCookies.forEach((cookie) => {
      const cookieName = cookie.trim().split('=')[0]
      if (regex.test(cookieName)) {
        document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/'
        document.cookie =
          cookieName + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=' + window.location.hostname
      }
    })
    return
  }

  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=' + window.location.hostname
  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/'

  const parts = window.location.hostname.split('.')
  if (parts.length > 1) {
    const domain = parts.slice(parts.length - 2).join('.')
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=.' + domain
  }
}

const clearCookies = (categoryKey) => {
  const cookies = COOKIES_TO_CLEAR[categoryKey]
  if (cookies && cookies.length > 0) {
    cookies.forEach((cookieName) => deleteCookie(cookieName))
  }
  if (categoryKey === 'analytics') {
    window.dataLayer = []
  }
}

const activateAnalytics = () => {
  const gaId = props.gaId?.trim()
  const isEnabled = categories.analytics.enabled
  const gtagFullSrc = gaId ? `${gtagBaseSrc}/js?id=${gaId}` : null

  if (window.gtag) {
    window.gtag('consent', 'update', {
      analytics_storage: isEnabled ? 'granted' : 'denied',
      ad_storage: categories.marketing.enabled ? 'granted' : 'denied',
    })
  }

  if (!isEnabled || !gaId) return

  const existingScript = document.querySelector(`script[src*="${gaId}"]`)
  if (!existingScript) {
    const script = document.createElement('script')
    script.async = true
    script.src = gtagFullSrc
    document.head.appendChild(script)

    script.onload = () => {
      window.dataLayer = window.dataLayer || []
      function gtag() {
        dataLayer.push(arguments)
      }
      gtag('js', new Date())
      gtag('config', gaId, {
        anonymize_ip: true,
        consent_mode: {
          analytics_storage: 'granted',
          ad_storage: categories.marketing.enabled ? 'granted' : 'denied',
        },
      })
    }
  }

  const alreadyDetected = categories.analytics.detectedItems.some((item) => item.src && item.src.includes(gtagBaseSrc))
  if (!alreadyDetected && gtagFullSrc) {
    categories.analytics.detectedItems.push({
      name: 'gtag.js',
      duration: '2 lata',
      description: t('common.the_main_script_behind_google_analytics'),
      src: gtagFullSrc,
    })
  }
}

const activateMarketing = () => {
  const pixelId = props.pixelId?.trim()
  const isEnabled = categories.marketing.enabled

  if (!isEnabled || !pixelId || !/^\d{10,20}$/.test(pixelId)) return

  if (!document.getElementById('fb-pixel')) {
    const s = document.createElement('script')
    s.id = 'fb-pixel'
    s.textContent = `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${pixelId}');fbq('track','PageView');`
    document.head.appendChild(s)
  }

  const alreadyDetected = categories.marketing.detectedItems.some((item) => item.name === 'Facebook Pixel')
  if (!alreadyDetected) {
    categories.marketing.detectedItems.push({
      name: 'Facebook Pixel',
      duration: t('common.3_months'),
      description: t('common.tracks_conversions_from_facebook_ads_and'),
    })
  }
}

const activateTikTok = () => {
  const pixelId = props.tiktokPixelId?.trim()
  const isEnabled = categories.marketing.enabled

  if (!isEnabled || !pixelId || !/^[A-Za-z0-9]{15,30}$/.test(pixelId)) return

  if (!document.getElementById('ttq-pixel')) {
    const s = document.createElement('script')
    s.id = 'ttq-pixel'
    // Official TikTok Pixel base code.
    s.textContent = `!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<e.length;n++)ttq.setAndDefer(e,e[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src=i+"?sdkid="+e+"&lib="+t;var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(a,s)};ttq.load('${pixelId}');ttq.page();}(window,document,'ttq');`
    document.head.appendChild(s)
  }

  const alreadyDetected = categories.marketing.detectedItems.some((item) => item.name === 'TikTok Pixel')
  if (!alreadyDetected) {
    categories.marketing.detectedItems.push({
      name: 'TikTok Pixel',
      duration: t('common.13_months'),
      description: t('common.tracks_conversions_from_tiktok_ads_and'),
    })
  }
}

const setupAutoBlocker = () => {
  const classifyScript = (src) => {
    if (!src) return null
    const lowerSrc = src.toLowerCase()
    for (const [catKey, regexArray] of Object.entries(PATTERNS)) {
      if (regexArray.some((rx) => rx.test(lowerSrc))) {
        return catKey
      }
    }
    return null
  }

  document.querySelectorAll('script').forEach((script) => {
    const src = script.src
    const category = classifyScript(src)
    const gaId = props.gaId?.trim()

    if (category && categories[category]) {
      const isStaticAnalytics = src && gaId && src.includes(gaId)

      if (!categories[category].enabled && !isStaticAnalytics) {
        if (!script.getAttribute('data-allow')) {
          script.type = 'text/plain'
          blockedQueue.push({ element: script, src: src, category: category })
        }
      }
      if (!categories[category].detectedItems.some((i) => i.src === src)) {
        categories[category].detectedItems.push({
          name: src.split('/').pop().split('?')[0] || t('common.external_script'),
          duration: t('common.session_no_data'),
          description: categories[category].description.substring(0, 100) + '...',
          src: src,
          originalElement: script,
        })
      }
    }
  })

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.tagName === 'SCRIPT' && node.src) {
          const category = classifyScript(node.src)
          if (category) {
            if (!categories[category].enabled) {
              node.type = 'text/plain'
              blockedQueue.push({ element: node, src: node.src, category: category })
              if (!categories[category].detectedItems.some((i) => i.src === node.src)) {
                categories[category].detectedItems.push({
                  name: node.src.split('/').pop().split('?')[0],
                  duration: t('common.session_no_data'),
                  description: categories[category].description.substring(0, 100) + '...',
                  src: node.src,
                })
              }
              node.parentElement?.removeChild(node)
            }
          }
        }
      })
    })
  })

  observer.observe(document.documentElement, { childList: true, subtree: true })
}

const saveToStorage = () => {
  const consents = {}
  Object.keys(categories).forEach((k) => (consents[k] = categories[k].enabled))
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ consents, timestamp: Date.now() }))
}

const blockAnalyticsImmediately = () => {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push({
    consent: {
      ad_storage: 'denied',
      analytics_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
    },
  })
  const gaId = props.gaId?.trim()
  if (gaId) {
    window[`ga-disable-${gaId}`] = true
  }
}

const applySettings = () => {
  activateAnalytics()
  activateMarketing()
  activateTikTok()

  blockedQueue.forEach((item, index) => {
    const isEnabled = categories[item.category].enabled
    if (isEnabled && item.element && item.element.type === 'text/plain') {
      const script = document.createElement('script')
      script.src = item.src
      script.type = 'text/javascript'
      script.async = true
      document.head.appendChild(script)
      blockedQueue[index].element.type = 'text/javascript'
    }
  })

  isOpen.value = false
  isModalOpen.value = false
}

const acceptAll = () => {
  Object.keys(categories).forEach((k) => {
    if (!categories[k].required) categories[k].enabled = true
  })
  saveToStorage()
  applySettings()
}

const denyAll = () => {
  Object.keys(categories).forEach((k) => {
    if (!categories[k].required) categories[k].enabled = false
  })
  saveToStorage()
  Object.keys(categories).forEach((catKey) => {
    if (!categories[catKey].required) clearCookies(catKey)
  })
  blockAnalyticsImmediately()
  clearCookies('analytics')
  isOpen.value = false
  isModalOpen.value = false
  isPolicyModalOpen.value = false
  setTimeout(() => {
    window.location.reload()
  }, 300)
}

const savePreferences = () => {
  let shouldReload = false
  const keyCategories = ['analytics', 'marketing', 'functional']

  keyCategories.forEach((catKey) => {
    if (previousConsents.value[catKey] === true && categories[catKey].enabled === false) {
      shouldReload = true
    }
    if (catKey === 'analytics' && categories[catKey].enabled === false) {
      shouldReload = true
    }
  })

  saveToStorage()
  Object.keys(categories).forEach((catKey) => {
    if (!categories[catKey].required && !categories[catKey].enabled) {
      clearCookies(catKey)
    }
  })

  if (shouldReload) {
    blockAnalyticsImmediately()
    clearCookies('analytics')
    setTimeout(() => {
      window.location.reload()
    }, 300)
    return
  }

  applySettings()
}

const openModal = () => {
  Object.keys(categories).forEach((k) => {
    previousConsents.value[k] = categories[k].enabled
  })
  isModalOpen.value = true
  isPolicyModalOpen.value = false
}

const openPolicyModal = () => {
  isPolicyModalOpen.value = true
  isModalOpen.value = false
  isOpen.value = false
}

onMounted(() => {
  if (window.gtag) {
    window.gtag('consent', 'default', {
      analytics_storage: 'denied',
      ad_storage: 'denied',
      wait_for_update: 500,
    })
  }

  setupAutoBlocker()

  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved) {
    const parsed = JSON.parse(saved)
    Object.keys(categories).forEach((k) => {
      if (parsed.consents[k] !== undefined && !categories[k].required) {
        categories[k].enabled = parsed.consents[k]
      }
    })
    applySettings()
    isOpen.value = false
  } else {
    isOpen.value = true
  }
})
</script>

<style scoped>
.cookie-wrapper {
  --primary-color: #dc2626;
  --primary-color-darker: #b91c1c;
  --primary-color-light: rgba(220, 38, 38, 0.1);
  --success-color: #28a745;
  --danger-color: #dc3545;
  --secondary-color: #6c757d;
  --text-color: #343a40;
  --text-color-white: #ffffff;
  --text-color-muted: #6c757d;
  --bg-white: #ffffff;
  --bg-light: #f8f9fa;
  --bg-medium: #e9ecef;
  --bg-dark: #212529;
  --border-color: #dee2e6;
  --border-radius: 8px;
  --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  --box-shadow-dark: 0 10px 30px rgba(0, 0, 0, 0.15);
  --transition-speed: 0.3s;
  --modal-bg: #ffffff;
  --audit-bg: #f8f9fa;
  color: #343a40;
  line-height: 1.5;
}

.accordion-slide-enter-active,
.accordion-slide-leave-active {
  transition:
    max-height 0.4s ease-in-out,
    padding 0.4s ease-in-out;
  overflow: hidden;
}
.accordion-slide-enter-from,
.accordion-slide-leave-to {
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
  opacity: 0;
}
.accordion-slide-enter-to,
.accordion-slide-leave-from {
  max-height: 1000px;
  opacity: 1;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition:
    transform 0.3s ease,
    opacity 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.cookie-banner {
  position: fixed;
  bottom: 20px;
  left: 20px;
  width: calc(100% - 40px);
  max-width: 420px;
  background: var(--bg-white);
  padding: 24px;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow-dark);
  z-index: 9998;
  border-left: 5px solid var(--primary-color);
}
.cookie-content h3 {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--text-color);
}
.cookie-content p {
  font-size: 14px;
  color: var(--text-color-muted);
  margin-bottom: 20px;
}
.cookie-content p strong {
  color: var(--primary-color);
  cursor: pointer;
  text-decoration: underline;
}

.cookie-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.cookie-actions-row {
  display: flex;
  gap: 8px;
}
.cookie-actions-row .btn {
  flex: 1;
}
.btn {
  padding: 8px 12px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: 0.2s;
  border: 1px solid transparent;
}
.btn-full {
  width: 100%;
}
.btn-primary {
  background: var(--primary-color);
  color: var(--text-color-white);
  border-color: var(--primary-color);
}
.btn-primary:hover {
  background: var(--primary-color-darker);
  border-color: var(--primary-color-darker);
}
.btn-secondary {
  background: var(--secondary-color);
  color: var(--text-color-white);
  border-color: var(--secondary-color);
}
.btn-secondary:hover {
  background: var(--bg-dark);
  border-color: var(--bg-dark);
}
.btn-outline {
  background: var(--bg-white);
  color: var(--text-color);
  border-color: var(--border-color);
}
.btn-outline:hover {
  background: var(--bg-light);
  border-color: var(--text-color-muted);
}

.cookie-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cookie-modal {
  background: var(--modal-bg);
  width: 95%;
  max-width: 750px;
  max-height: 90vh;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-light);
}
.modal-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
  color: var(--text-color);
}
.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--text-color-muted);
  line-height: 1;
}

.modal-body-wrapper {
  padding: 24px;
  overflow-y: auto;
  color: var(--text-color);
}

.modal-description p {
  font-size: 14px;
  margin-bottom: 10px;
}
.dma-link {
  font-size: 14px;
  margin-top: 15px;
}
.dma-link a {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  cursor: pointer;
}
.horizontal-separator {
  height: 1px;
  background: var(--border-color);
  margin: 20px 0;
}

.category-item {
  border: 1px solid var(--border-color);
  border-radius: 6px;
  margin-bottom: 10px;
  overflow: hidden;
  transition: var(--transition-speed);
}
.category-item.is-open {
  border-color: var(--primary-color);
  box-shadow: 0 0 5px var(--primary-color-light);
}

.category-header {
  padding: 16px 24px;
  cursor: pointer;
}
.accordion-header-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.accordion-btn {
  background: none;
  border: none;
  padding: 0;
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 15px;
  color: var(--text-color);
  flex-grow: 1;
  text-align: left;
  cursor: pointer;
}

.chevron {
  font-size: 18px;
  color: var(--primary-color);
  transition: var(--transition-speed) transform;
  margin-right: 15px;
  line-height: 1;
}
.chevron.rotated {
  transform: rotate(90deg);
}

.badge {
  background: var(--bg-medium);
  color: var(--text-color-muted);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  margin-left: 5px;
  font-weight: 400;
}

.category-controls {
  display: flex;
  align-items: center;
}
.always-active {
  font-size: 12px;
  font-weight: 700;
  color: var(--success-color);
  text-transform: uppercase;
  margin-left: 15px;
  flex-shrink: 0;
}

.category-body {
  border-top: 1px solid var(--border-color);
  background: var(--audit-bg);
  padding: 15px 24px;
}
.category-desc-full {
  font-size: 13px;
  color: var(--text-color);
  margin-bottom: 15px;
  line-height: 1.6;
}

.audit-table {
  background: var(--bg-white);
  border: 1px solid var(--border-color);
  border-radius: 4px;
  overflow: hidden;
}
.empty-text {
  padding: 15px;
  text-align: center;
  font-size: 14px;
  color: var(--text-color-muted);
}

.cookie-table {
  list-style: none;
  padding: 0;
  margin: 0;
}
.cookie-table li {
  padding: 12px 15px;
  border-bottom: 1px solid var(--border-color);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px 15px;
}
.cookie-table li:last-child {
  border-bottom: none;
}

.table-row {
  display: flex;
  font-size: 13px;
}
.table-label {
  font-weight: 600;
  color: var(--text-color-muted);
  flex-shrink: 0;
  text-align: right;
  padding-right: 5px;
}
.table-value {
  color: var(--text-color);
  word-break: break-word;
  flex-grow: 1;
}
.table-value.bold {
  font-weight: 700;
}
.description-row {
  grid-column: 1 / -1;
  margin-top: 5px;
}
.status-row {
  grid-column: 1 / -1;
  margin-top: 10px;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.status-allowed {
  background: rgba(40, 167, 69, 0.1);
  color: var(--success-color);
}
.status-blocked {
  background: rgba(220, 53, 69, 0.1);
  color: var(--danger-color);
}

.switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
  margin-left: 15px;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 34px;
}
.slider:before {
  position: absolute;
  content: '';
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}
input:checked + .slider {
  background-color: var(--primary-color);
}
input:checked + .slider:before {
  transform: translateX(18px);
}

.cky-footer-wrapper {
  position: relative;
}
.cky-footer-shadow {
  content: '';
  position: absolute;
  top: -20px;
  left: 0;
  right: 0;
  height: 20px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, var(--bg-white) 100%);
  z-index: 10;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: var(--bg-white);
  position: relative;
  z-index: 11;
}

.cky-btn {
  padding: 10px 18px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-speed);
  border: 1px solid transparent;
}
.cky-btn-accept {
  background: var(--primary-color);
  color: var(--text-color-white);
  border-color: var(--primary-color);
}
.cky-btn-accept:hover {
  background: var(--primary-color-darker);
  border-color: var(--primary-color-darker);
}
.cky-btn-preferences {
  background: var(--secondary-color);
  color: var(--text-color-white);
  border-color: var(--secondary-color);
}
.cky-btn-preferences:hover {
  background: var(--bg-dark);
  border-color: var(--bg-dark);
}
.cky-btn-reject {
  background: var(--bg-light);
  color: var(--text-color);
  border-color: var(--border-color);
}
.cky-btn-reject:hover {
  background: var(--bg-medium);
  border-color: var(--text-color-muted);
}

.powered-by {
  font-size: 12px;
  text-align: right;
  padding: 8px 24px;
  color: var(--text-color-muted);
  background-color: var(--bg-light);
  font-weight: 400;
  border-top: 1px solid var(--border-color);
}

.cookie-reopen-btn {
  position: fixed;
  bottom: 20px;
  left: 20px;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: var(--bg-white);
  border: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 9990;
  display: flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color);
  cursor: pointer;
}

@media (max-width: 600px) {
  .modal-footer {
    flex-direction: column-reverse;
  }
  .modal-footer .cky-btn {
    width: 100%;
  }
  .cookie-banner {
    left: 0;
    bottom: 0;
    width: 100%;
    max-width: none;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
  }
  .cookie-table li {
    grid-template-columns: 1fr;
    gap: 5px 0;
  }
  .table-label {
    width: auto;
    text-align: left;
    padding-right: 0;
    margin-bottom: 2px;
  }
  .type-row,
  .source-row {
    grid-column: 1 / -1;
  }
}
</style>
