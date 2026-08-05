import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

/**
 * Money, dates and "three days ago", in the language the page is being read in.
 *
 * Sixty components were formatting these with 'pl-PL' written into the call,
 * so an English page still showed "1 756,95 zł" and "3 dni temu". The locale
 * the interface is rendered in is the one the browser should format with, and
 * Intl already knows how to say the rest.
 *
 * The currency is a separate question from the language: a shop selling in
 * złoty still sells in złoty to an English-speaking customer, so it is passed
 * in rather than derived from the locale.
 */
export function useFormatting() {
  const { locale } = useI18n()

  const money = computed(
    () =>
      (amount, currency = 'PLN') =>
        new Intl.NumberFormat(locale.value, { style: 'currency', currency }).format(Number(amount ?? 0)),
  )

  const number = computed(
    () =>
      (value, options = {}) =>
        new Intl.NumberFormat(locale.value, options).format(Number(value ?? 0)),
  )

  const date = computed(
    () =>
      (value, options = { dateStyle: 'medium' }) =>
        value ? new Intl.DateTimeFormat(locale.value, options).format(new Date(value)) : '',
  )

  const dateTime = computed(
    () => (value) =>
      value
        ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
        : '',
  )

  /**
   * "just now", "3 minutes ago", "2 days ago" — and the same in Polish,
   * with its three plural forms, without a table of them here.
   */
  const relative = computed(() => {
    const formatter = new Intl.RelativeTimeFormat(locale.value, { numeric: 'auto' })

    return (value) => {
      if (!value) return ''

      const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000)
      const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
      ]

      for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
          return formatter.format(Math.round(seconds / size), unit)
        }
      }

      return formatter.format(0, 'second')
    }
  })

  return { money, number, date, dateTime, relative }
}
