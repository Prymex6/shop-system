/**
 * Format a phone number for display: strips +48 prefix, formats 9 digits as "123 456 789".
 */
export function formatPhone(value) {
  if (!value) return ''

  // Keep only digits and a possible leading +
  let clean = value.replace(/[^\d+]/g, '')

  // Strip +48 or 0048
  if (clean.startsWith('+48')) {
    clean = clean.slice(3)
  } else if (clean.startsWith('0048')) {
    clean = clean.slice(4)
  } else if (clean.startsWith('+')) {
    // Non-Polish international — just strip the + and return digits
    clean = clean.slice(1)
    return clean
  }

  // Strip a leading 0 (old Polish format: 0 123 456 789)
  if (clean.startsWith('0') && clean.length === 10) {
    clean = clean.slice(1)
  }

  // Format 9 digits as XXX XXX XXX
  if (clean.length === 9) {
    return clean.slice(0, 3) + ' ' + clean.slice(3, 6) + ' ' + clean.slice(6)
  }

  return clean
}
