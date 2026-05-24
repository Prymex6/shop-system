<template>
  <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
    <div class="flex items-center gap-3 mb-3">
      <div
        class="w-10 h-10 rounded-full flex items-center justify-center text-xl"
        :style="{ backgroundColor: tierColor + '22' }"
      >
        <i class="fa-solid fa-crown" :style="{ color: tierColor }"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ t('common.loyalty_programme') }}</p>
        <p class="font-bold text-gray-900">
          <span :style="{ color: tierColor }">{{ tierName }}</span>
          · {{ customer.loyalty_points ?? 0 }} pkt
        </p>
      </div>
    </div>

    <!-- Progress bar -->
    <div v-if="customer.loyalty_next_tier_points" class="mb-3">
      <div class="flex justify-between text-xs text-gray-400 mb-1">
        <span>{{ customer.loyalty_points ?? 0 }} pkt</span>
        <span>{{ customer.loyalty_next_tier_points }} pkt → {{ nextTierName }}</span>
      </div>
      <div class="w-full bg-gray-100 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-500"
          :style="{ width: progressPercent + '%', backgroundColor: tierColor }"
        ></div>
      </div>
      <p class="text-xs text-gray-400 mt-1">
        Brakuje {{ Math.max(0, (customer.loyalty_next_tier_points ?? 0) - (customer.loyalty_points ?? 0)) }} pkt do
        awansu
      </p>
    </div>
    <div v-else class="text-xs text-green-600 font-medium mb-3">
      {{ t('client.loyaltywidget.top_tier_reached') }}
    </div>

    <a
      :href="route('tenant.loyalty')"
      class="block text-center w-full py-2 theme-primary-bg-light hover:opacity-80 theme-primary font-semibold text-sm rounded-xl transition"
    >
      {{ t('client.loyaltywidget.see_the_rewards') }}
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  customer: { type: Object, required: true },
})

const tierColor = computed(() => props.customer.loyalty_tier_color ?? '#cd7f32')
const tierName = computed(() => props.customer.loyalty_tier ?? t('common.bronze'))
const nextTierName = computed(() => props.customer.loyalty_next_tier_name ?? '')

const progressPercent = computed(() => {
  const current = props.customer.loyalty_points ?? 0
  const tierMin = props.customer.loyalty_tier_min ?? 0
  const nextMin = props.customer.loyalty_next_tier_points ?? 1
  const progress = ((current - tierMin) / (nextMin - tierMin)) * 100
  return Math.min(100, Math.max(0, Math.round(progress)))
})
</script>
