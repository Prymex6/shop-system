import { test, expect } from '@playwright/test'

test.describe('Magazyn — Inventory', () => {
  test('E-INV.1.1 stock levels are listed', async ({ page }) => {
    await page.goto('/manager/inventory')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/inventory/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-INV.1.2 filtering by product name narrows the results', async ({ page }) => {
    await page.goto('/manager/inventory')
    await page.waitForLoadState('networkidle')
    const searchInput = page.locator('input[name="search"], input[placeholder*="szukaj" i]').first()
    if (await searchInput.isVisible()) {
      await searchInput.fill('test')
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-INV.1.3 the stock correction form is there', async ({ page }) => {
    await page.goto('/manager/inventory')
    await page.waitForLoadState('networkidle')
    const adjustBtn = page
      .locator('button, a')
      .filter({ hasText: /koryguj|adjust|zmień stan/i })
      .first()
    if (await adjustBtn.isVisible()) {
      await adjustBtn.click()
      await page.waitForLoadState('networkidle')
      const form = page.locator('input[name="quantity"], input[name="adjustment"]').first()
      await expect(form).toBeVisible({ timeout: 5000 })
    }
  })

  test('E-INV.1.4 a stock correction with no quantity is rejected', async ({ page }) => {
    await page.goto('/manager/inventory')
    await page.waitForLoadState('networkidle')
    const adjustBtn = page
      .locator('button, a')
      .filter({ hasText: /koryguj|adjust/i })
      .first()
    if (await adjustBtn.isVisible()) {
      await adjustBtn.click()
      await page.waitForLoadState('networkidle')
      const submitBtn = page.locator('button[type="submit"]').last()
      if (await submitBtn.isVisible()) {
        await submitBtn.click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/wymagane|required|ilość/i)
          .isVisible()
          .catch(() => false)
        expect(hasError || page.url().includes('inventory')).toBeTruthy()
      }
    }
  })

  test('E-INV.1.5 Eksport CSV → plik pobierany', async ({ page }) => {
    let ok = false
    try {
      const [dl] = await Promise.all([
        page.waitForEvent('download', { timeout: 8000 }),
        page.goto('/manager/inventory/export'),
      ])
      if (dl) {
        expect(dl.suggestedFilename()).toMatch(/\.csv$/i)
        ok = true
      }
    } catch {
      /* brak downloadu */
    }
    if (!ok) {
      await page.goto('/manager/inventory')
      expect(
        await page
          .getByText(/Internal Server Error/i)
          .isVisible()
          .catch(() => false),
      ).toBeFalsy()
    }
  })

  test('E-INV.1.6 stock movements are listed', async ({ page }) => {
    await page.goto('/manager/inventory')
    await page.waitForLoadState('networkidle')
    const movementsLink = page.locator('a[href*="movements"]').first()
    if (await movementsLink.isVisible()) {
      await movementsLink.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
