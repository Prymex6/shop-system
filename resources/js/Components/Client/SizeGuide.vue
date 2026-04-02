<template>
  <div>
    <button
      type="button"
      @click="open = true"
      class="inline-flex items-center gap-1.5 text-sm theme-primary hover:opacity-80 font-medium underline underline-offset-2 transition-colors"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 7h16a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1z"
        />
      </svg>
      {{ t('client.sizeguide.size_guide') }}
    </button>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="open"
          class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
          @click.self="open = false"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
              <h2 class="text-xl font-bold text-gray-900">{{ t('client.sizeguide.size_guide') }}</h2>
              <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="p-6 space-y-6">
              <!-- Size table -->
              <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">
                  {{ t('client.sizeguide.size_chart_cm_in') }}
                </h3>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">
                          {{ t('manager.products.form.spec_size') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.chest_cm') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.waist_cm') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.hips_cm') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.chest_in') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.waist_in') }}
                        </th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600">
                          {{ t('client.sizeguide.hips_in') }}
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="row in sizeTable" :key="row.size" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-bold theme-primary">{{ row.size }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ row.chestCm }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ row.waistCm }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ row.hipsCm }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ row.chestIn }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ row.waistIn }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ row.hipsIn }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Measurement guide -->
              <div class="theme-primary-bg-light rounded-xl p-5 border theme-primary-border">
                <h3 class="text-sm font-semibold theme-primary mb-3">{{ t('client.sizeguide.how_is_it_measured') }}</h3>
                <ul class="space-y-2 text-sm theme-primary">
                  <li class="flex items-start gap-2">
                    <span class="font-bold shrink-0 mt-0.5">{{ t('client.sizeguide.chest') }}</span>
                    {{ t('client.sizeguide.measure_horizontally_around_the_fullest_part') }}
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="font-bold shrink-0 mt-0.5">{{ t('client.sizeguide.waist') }}</span>
                    {{ t('client.sizeguide.measure_around_the_narrowest_part_of') }}
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="font-bold shrink-0 mt-0.5">{{ t('client.sizeguide.hips') }}</span>
                    {{ t('client.sizeguide.measure_horizontally_around_the_widest_part') }}
                  </li>
                </ul>
              </div>

              <!-- Tips -->
              <div class="text-sm text-gray-500 space-y-1">
                <p>{{ t('client.sizeguide.if_your_measurements_fall_between_two') }}</p>
                <p>{{ t('client.sizeguide.measurements_vary_slightly_by_model_and') }}</p>
              </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex justify-end">
              <button
                type="button"
                @click="open = false"
                class="px-5 py-2.5 theme-primary-bg hover:opacity-90 text-white text-sm font-semibold rounded-xl transition-colors"
              >
                {{ t('common.close') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  categorySlug: { type: String, default: null },
})

const open = ref(false)

const sizeTable = [
  {
    size: 'XS',
    chestCm: '76–81',
    waistCm: '58–63',
    hipsCm: '83–88',
    chestIn: '30–32',
    waistIn: '23–25',
    hipsIn: '33–35',
  },
  {
    size: 'S',
    chestCm: '82–87',
    waistCm: '64–69',
    hipsCm: '89–94',
    chestIn: '32–34',
    waistIn: '25–27',
    hipsIn: '35–37',
  },
  {
    size: 'M',
    chestCm: '88–93',
    waistCm: '70–75',
    hipsCm: '95–100',
    chestIn: '34–37',
    waistIn: '27–30',
    hipsIn: '37–39',
  },
  {
    size: 'L',
    chestCm: '94–99',
    waistCm: '76–81',
    hipsCm: '101–106',
    chestIn: '37–39',
    waistIn: '30–32',
    hipsIn: '40–42',
  },
  {
    size: 'XL',
    chestCm: '100–107',
    waistCm: '82–89',
    hipsCm: '107–114',
    chestIn: '39–42',
    waistIn: '32–35',
    hipsIn: '42–45',
  },
  {
    size: 'XXL',
    chestCm: '108–117',
    waistCm: '90–99',
    hipsCm: '115–124',
    chestIn: '42–46',
    waistIn: '35–39',
    hipsIn: '45–49',
  },
]
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
