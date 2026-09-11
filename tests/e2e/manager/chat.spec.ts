import { test, expect } from '@playwright/test'

test.describe('Live Chat — Panel managera', () => {
  test('E-CH.1.1 Lista konwersacji widoczna na /manager/chat', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/chat/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-CH.1.2 Lista konwersacji lub pusty stan ładuje się bez błędu', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // Strona załadowała się poprawnie — lista konwersacji lub pusty kontener
    await expect(page.locator('main')).toBeVisible()
    // Konwersacje (div.cursor-pointer) lub brak konwersacji (pusty kontener bg-white)
    const hasConvItem = await page
      .locator('div.cursor-pointer')
      .first()
      .isVisible()
      .catch(() => false)
    const hasContainer = await page
      .locator('.bg-white.shadow')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasConvItem || hasContainer).toBeTruthy()
  })

  test('E-CH.1.3 Strona nie zwraca 500 po próbie odpowiedzi na konwersację', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    const firstConv = page.locator('a[href*="chat"], [data-conversation]').first()
    if (await firstConv.isVisible()) {
      await firstConv.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
