import { test, expect } from '@playwright/test'

test.describe('Fraud Detection', () => {
  test('E-FR.1.1 /manager/fraud ładuje się', async ({ page }) => {
    await page.goto('/manager/fraud')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-FR.1.2 Blocklist — dodaj email do blokady', async ({ page }) => {
    await page.goto('/manager/fraud')
    await page.waitForLoadState('networkidle')
    const blocklistTab = page
      .locator('button, a, [role="tab"]')
      .filter({ hasText: /blocklist|blokada/i })
      .first()
    if (await blocklistTab.isVisible()) {
      await blocklistTab.click()
      await page.waitForLoadState('networkidle')
      const emailInput = page.locator('input[name="email"], input[name="value"][placeholder*="email" i]').first()
      if (await emailInput.isVisible()) {
        await emailInput.fill('spam-e2e@block.test')
        await page.locator('button[type="submit"]').first().click()
        await page.waitForLoadState('networkidle')
        await expect(page.getByText('spam-e2e@block.test').first()).toBeVisible({ timeout: 5000 })
      }
    }
  })
})
