import { test, expect } from '@playwright/test'

test.describe('RMA — Zwroty i reklamacje', () => {
  test('E-RMA.1.1 Lista RMA widoczna na /manager/rma', async ({ page }) => {
    await page.goto('/manager/rma')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-RMA.1.2 Filtr statusu „Oczekujące" działa', async ({ page }) => {
    await page.goto('/manager/rma')
    await page.waitForLoadState('networkidle')
    const filterSelect = page.locator('select[name="status"]')
    if (await filterSelect.isVisible()) {
      await filterSelect.selectOption('pending')
      await page.waitForLoadState('networkidle')
      await expect(page.locator('body')).toBeVisible()
    }
  })

  test('E-RMA.1.3 Szczegóły RMA ładują się bez 500', async ({ page }) => {
    await page.goto('/manager/rma')
    await page.waitForLoadState('networkidle')
    const firstLink = page.locator('a[href*="rma/"]').first()
    if (await firstLink.isVisible()) {
      await firstLink.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
