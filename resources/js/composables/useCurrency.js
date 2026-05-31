import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const SYMBOLS = { PLN: 'zł', EUR: '€', USD: '$', GBP: '£', CZK: 'Kč' }
const SYMBOL_BEFORE = ['USD', 'GBP']

/**
 * CurrencyService (real NBP-rate conversion) and the currency switcher
 * (CurrencyController::set()) already existed and worked server-side, but
 * every storefront price was formatted by a local, hardcoded
 * `new Intl.NumberFormat('pl-PL', { currency: 'PLN' })` — switching
 * currency changed session state with no visible effect anywhere. This
 * mirrors CurrencyService::formatAmount() using the rate the backend
 * already shares via Inertia (`currency_rate`, HandleInertiaRequests),
 * so every price in the storefront actually reflects the chosen currency.
 *
 * Checkout intentionally does NOT use this — the customer is actually
 * charged in PLN there regardless of browsing currency (see Checkout.vue).
 */
export function useCurrency() {
  const page = usePage()
  const { locale } = useI18n()

  const currentCurrency = computed(() => page.props.current_currency ?? 'PLN')
  const currencyRate = computed(() => page.props.currency_rate ?? 1)
  const enabledCurrencies = computed(() => page.props.enabled_currencies ?? ['PLN'])

  function formatPrice(plnAmount) {
    const amount = (plnAmount ?? 0) * currencyRate.value
    const currency = currentCurrency.value
    const symbol = SYMBOLS[currency] ?? currency
    const formatted = new Intl.NumberFormat(locale.value, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)

    return SYMBOL_BEFORE.includes(currency) ? `${symbol}${formatted}` : `${formatted} ${symbol}`
  }

  return { currentCurrency, currencyRate, enabledCurrencies, formatPrice }
}
