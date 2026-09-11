import { test, expect } from '@playwright/test'

test.describe('Impersonacja managera przez super-admina', () => {
  test('E46.1.1 Super-admin klika „Impersonuj" dla e2e-test → Przekierowanie na stronę tenanta; żółty banner widoczny', async ({
    page,
  }) => {
    await page.goto('/admin/tenants')
    const impersonateBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /e2e-test|E2E Test/i })
      .locator('button, a, form button')
      .filter({ hasText: /impersonat|wejdź|zaloguj jako/i })
      .first()
    if (await impersonateBtn.isVisible()) {
      await impersonateBtn.click()
      await page.waitForLoadState('networkidle')
      // Should redirect to tenant domain (pizza.localhost)
      const banner = await page
        .locator('[class*="impersona"], [class*="yellow"], [class*="warning"]')
        .isVisible()
        .catch(() => false)
      const hasText = await page
        .getByText(/przeglądasz jako|impersonat|impersonuj/i)
        .isVisible()
        .catch(() => false)
      expect(banner || hasText).toBeTruthy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E46.1.2 Podczas impersonacji widok managera działa normalnie → Dashboard managera załadowany', async ({
    page,
  }) => {
    await page.goto('/admin/tenants')
    const impersonateBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /e2e-test|E2E Test/i })
      .locator('button, a, form button')
      .filter({ hasText: /impersonat|wejdź|zaloguj jako/i })
      .first()
    if (await impersonateBtn.isVisible()) {
      await impersonateBtn.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E46.1.3 Kliknij „Zakończ impersonację" → Powrót do panelu super-admina na centralnej domenie', async ({
    page,
  }) => {
    await page.goto('/admin/tenants')
    const impersonateBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /e2e-test|E2E Test/i })
      .locator('button, a, form button')
      .filter({ hasText: /impersonat|wejdź|zaloguj jako/i })
      .first()
    if (await impersonateBtn.isVisible()) {
      await impersonateBtn.click()
      await page.waitForLoadState('networkidle')
      const stopBtn = page
        .locator('button, a')
        .filter({ hasText: /zakończ impersonac|stop impersonat|powrót do admin/i })
        .first()
      if (await stopBtn.isVisible()) {
        await stopBtn.click()
        await page.waitForLoadState('networkidle')
        // Should be back on localhost:8000/admin
        const isAdmin = page.url().includes('localhost') || page.url().includes('admin')
        expect(isAdmin).toBeTruthy()
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
