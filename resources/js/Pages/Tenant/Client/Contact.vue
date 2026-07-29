<template>
  <Head :title="t('layout.clientlayout.contact')" />

  <ClientLayout>
    <div class="bg-gray-50 py-16">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="text-center mb-14">
          <span class="theme-primary font-semibold text-sm uppercase tracking-[0.25em]">{{
            t('common.get_in_touch')
          }}</span>
          <h1 class="text-4xl md:text-5xl font-bold mt-3">{{ t('layout.clientlayout.contact') }}</h1>
        </div>

        <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-12">
          <!-- Contact Info -->
          <div class="space-y-8">
            <div v-if="tenant?.address" class="flex items-start gap-4">
              <div class="shrink-0 w-12 h-12 theme-primary-bg-light rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-location-dot text-xl theme-primary"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 text-lg">{{ t('common.address') }}</h3>
                <p class="text-gray-600 mt-1">{{ tenant.address }}</p>
              </div>
            </div>

            <div v-if="tenant?.phone" class="flex items-start gap-4">
              <div class="shrink-0 w-12 h-12 theme-primary-bg-light rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-phone text-xl theme-primary"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 text-lg">{{ t('common.phone') }}</h3>
                <a :href="'tel:' + tenant.phone" class="theme-primary hover:opacity-80 mt-1 block text-lg font-medium">
                  {{ tenant.phone }}
                </a>
              </div>
            </div>

            <div v-if="tenant?.email" class="flex items-start gap-4">
              <div class="shrink-0 w-12 h-12 theme-primary-bg-light rounded-2xl flex items-center justify-center">
                <i class="fa-solid fa-envelope text-xl theme-primary"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 text-lg">{{ t('landlord.shopsearch.email') }}</h3>
                <a :href="'mailto:' + tenant.email" class="theme-primary hover:opacity-80 mt-1 block">
                  {{ tenant.email }}
                </a>
              </div>
            </div>

            <!-- Opening hours -->
          </div>

          <!-- Contact form (#35) -->
          <div class="bg-white rounded-3xl shadow-lg p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">{{ t('client.contact.send_message') }}</h2>

            <div v-if="contactSent" class="text-center py-8">
              <div class="text-5xl mb-4 text-green-500"><i class="fa-solid fa-circle-check"></i></div>
              <p class="font-semibold text-gray-900 text-lg">{{ t('client.contact.message_sent') }}</p>
              <p class="text-gray-500 mt-1 text-sm">{{ t('client.contact.we_will_reply_to_the_email') }}</p>
              <button @click="contactSent = false" class="mt-4 text-sm theme-primary hover:underline">
                {{ t('client.contact.send_another') }}
              </button>
            </div>

            <form v-else @submit.prevent="submitContact" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.full_name') }}</label>
                <input
                  v-model="contactForm.name"
                  name="name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  placeholder="Jan Kowalski"
                />
                <p v-if="contactErrors.name" class="mt-1 text-sm text-red-600">{{ contactErrors.name[0] }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('landlord.shopsearch.email') }}</label>
                <input
                  v-model="contactForm.email"
                  type="email"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  :placeholder="t('manager.manualordermodal.john_example_com')"
                />
                <p v-if="contactErrors.email" class="mt-1 text-sm text-red-600">{{ contactErrors.email[0] }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.message') }}</label>
                <textarea
                  v-model="contactForm.message"
                  name="message"
                  required
                  rows="5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:theme-primary-ring focus:border-transparent"
                  :placeholder="t('client.contact.how_can_we_help')"
                ></textarea>
                <p v-if="contactErrors.message" class="mt-1 text-sm text-red-600">{{ contactErrors.message[0] }}</p>
              </div>
              <input
                v-model="contactForm.website"
                type="text"
                tabindex="-1"
                autocomplete="off"
                style="position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0"
                aria-hidden="true"
              />
              <p v-if="contactErrors.general" class="text-sm text-red-600">{{ contactErrors.general }}</p>
              <button
                type="submit"
                :disabled="contactSending"
                class="w-full py-3 theme-primary-bg hover:opacity-90 text-white font-semibold rounded-xl transition-colors disabled:opacity-50"
              >
                {{ contactSending ? t('common.sending_2') : t('client.contact.send_message') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Map section below if Google Place ID set -->
    <div v-if="tenant?.google_place_id" class="container mx-auto px-4 lg:px-8 pb-16 max-w-5xl">
      <div class="bg-white rounded-3xl shadow-lg overflow-hidden h-80">
        <iframe
          :src="mapEmbedUrl"
          width="100%"
          height="100%"
          style="border: 0"
          allowfullscreen
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const page = usePage()
const tenant = computed(() => page.props.tenant)

const mapEmbedUrl = computed(() => {
  if (!tenant.value?.google_place_id) return ''
  const apiKey = page.props.google_maps_api_key || ''
  return `https://www.google.com/maps/embed/v1/place?key=${apiKey}&q=place_id:${tenant.value.google_place_id}`
})

// Contact form (#35)
const contactForm = reactive({ name: '', email: '', message: '', website: '' })
const contactErrors = reactive({})
const contactSending = ref(false)
const contactSent = ref(false)

const submitContact = async () => {
  Object.keys(contactErrors).forEach((k) => delete contactErrors[k])
  contactSending.value = true
  try {
    await axios.post(route('tenant.contact.send'), contactForm)
    contactSent.value = true
    contactForm.name = ''
    contactForm.email = ''
    contactForm.message = ''
  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(contactErrors, e.response.data.errors || {})
    } else {
      contactErrors.general = t('client.contact.message_could_not_be_sent')
    }
  } finally {
    contactSending.value = false
  }
}
</script>
