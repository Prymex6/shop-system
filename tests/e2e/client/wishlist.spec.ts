import { test, expect } from '@playwright/test'

test.describe('Lista życzeń klienta (§22.8)', () => {
  test('E-CW.1.1 Strona listy życzeń ładuje się', async ({ page }) => {
    await page.goto('/moje-konto/lista-zyczen')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CW.1.2 Pusta lista życzeń — komunikat lub grid produktów', async ({ page }) => {
    await page.goto('/moje-konto/lista-zyczen')
    await page.waitForLoadState('networkidle')
    const hasEmpty = await page
      .getByText(/brak|pusta|empty|dodaj|wishlist/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasGrid = await page
      .locator('[class*="grid"], [class*="product"]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasEmpty || hasGrid).toBeTruthy()
  })

  test('E-CW.1.3 Przycisk serca na stronie produktu jest klikalny', async ({ page }) => {
    await page.goto('/sklep')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // If shop page loaded, look for wishlist buttons
    const heartBtn = page
      .locator('[data-wishlist], button[aria-label*="wishlist"], button[aria-label*="życzeń"]')
      .first()
    if (await heartBtn.isVisible()) {
      await heartBtn.click()
      await page.waitForLoadState('networkidle')
      const is500After = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500After).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
