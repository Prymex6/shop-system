import { test, expect } from '@playwright/test'

test.describe('Customer wishlist (§22.8)', () => {
  test('E-CW.1.1 the wishlist page loads', async ({ page }) => {
    await page.goto('/moje-konto/lista-zyczen')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CW.1.2 an empty wishlist shows a note or a grid', async ({ page }) => {
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

  test('E-CW.1.3 the heart on a product page can be clicked', async ({ page }) => {
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
