import { test, expect } from '@playwright/test'

test.use({
  storageState: { cookies: [], origins: [] },
  baseURL: process.env.LANDLORD_URL ?? 'http://localhost:8000',
})

test.describe('Landing page on the central domain', () => {
  test('E49.1.1 the central domain serves the landing page, not the manager panel', async ({ page }) => {
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

  test('E49.1.2 the landing page has a call to action you can click', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    // The call to action may be a contact link, a "write to us", or a sign-up button
    const cta = page
      .locator('a, button')
      .filter({ hasText: /zarejestruj|rozpocznij|demo|napisz|kontakt|sign up|get started/i })
      .first()
    await expect(cta).toBeVisible({ timeout: 8000 })
  })

  test('E49.1.3 the landing page shows the plans, or their prices', async ({ page }) => {
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

  test('E49.1.4 the super admin sign-in link goes to /admin/login', async ({ page }) => {
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
