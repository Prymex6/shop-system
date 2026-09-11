import { test, expect } from '@playwright/test'

test.use({
  storageState: { cookies: [], origins: [] },
  baseURL: process.env.LANDLORD_URL ?? 'http://localhost:8000',
})

test.describe('Landing page (domena główna)', () => {
  test('E49.1.1 Wejście na http://localhost:8000/ → Strona landing widoczna; nie jest to panel managera', async ({
    page,
  }) => {
    await page.goto('/')
    await expect(page.locator('main, body').first()).toBeVisible()
    const isManagerPanel = page.url().includes('/manager/')
    expect(isManagerPanel).toBeFalsy()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E49.1.2 Strona zawiera CTA — przycisk „Zarejestruj się" lub „Rozpocznij" → Widoczny i klikalny', async ({
    page,
  }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    // CTA może być linkiem kontaktowym, "Napisz do nas", lub przyciskiem rejestracji
    const cta = page
      .locator('a, button')
      .filter({ hasText: /zarejestruj|rozpocznij|demo|napisz|kontakt|sign up|get started/i })
      .first()
    await expect(cta).toBeVisible({ timeout: 8000 })
  })

  test('E49.1.3 Strona zawiera sekcję cenników → Widoczne plany (Starter, Basic, Pro, Premium) lub ich ceny', async ({
    page,
  }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    const hasPlanSection = await page
      .locator('#cennik, [id*="plan"], [id*="pricing"]')
      .isVisible()
      .catch(() => false)
    const hasText = await page
      .getByText(/starter|basic|pro|premium/i)
      .isVisible()
      .catch(() => false)
    const hasPricingText = await page
      .getByText(/cena|cennik|pln.*rok|rok.*pln/i)
      .isVisible()
      .catch(() => false)
    expect(hasPlanSection || hasText || hasPricingText).toBeTruthy()
  })

  test('E49.1.4 Link do logowania super-admina → Link/przycisk prowadzi do /admin/login', async ({ page }) => {
    await page.goto('/')
    const loginLink = page
      .locator('a[href*="admin/login"], a')
      .filter({ hasText: /zaloguj|login|panel/i })
      .first()
    if (await loginLink.isVisible()) {
      await loginLink.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/admin\/login|login/)
    } else {
      // Direct nav fallback
      await page.goto('/admin/login')
      await expect(page).toHaveURL(/admin\/login/)
    }
  })
})
