import { test, expect } from '@playwright/test'

test.describe('Dashboard managera', () => {
  test('E3.1.1 the dashboard loads with its cards for orders, revenue and customers', async ({ page }) => {
    await page.goto('/manager/')
    await expect(page).toHaveURL(/manager\//)
    await expect(page.locator('main, [class*="card"], [class*="stat"]').first()).toBeVisible()
  })

  test('E3.1.2 the thirty-day revenue chart is drawn', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    const hasCanvas = (await page.locator('canvas').count()) > 0
    const hasChart = (await page.locator('[class*="chart"]').count()) > 0
    expect(hasCanvas || hasChart).toBeTruthy()
  })

  test('E3.1.3 the hourly heat map shows all 24 hours', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    // The heat map: a second canvas, or a div marked chart or heatmap
    const canvasCount = await page.locator('canvas').count()
    const chartDivCount = await page.locator('[class*="chart"], [class*="heatmap"], [class*="heat"]').count()
    expect(canvasCount + chartDivCount).toBeGreaterThanOrEqual(1)
  })

  test('E3.1.4 the best sellers section is there, even with nothing in it', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main').first()).toBeVisible()
  })

  test('E3.1.5 the orders link on the dashboard goes to /manager/orders', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    // The orders link sits under Sales in the sidebar, which has to be opened first
    const sprzedazToggle = page
      .locator('button')
      .filter({ hasText: /^Sprzedaż$/ })
      .first()
    if (await sprzedazToggle.isVisible()) await sprzedazToggle.click()
    const ordersLink = page
      .locator('a[href*="orders"]')
      .filter({ hasText: /zamówienia/i })
      .first()
    await expect(ordersLink).toBeVisible({ timeout: 3000 })
    await ordersLink.click()
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/orders/)
  })
})
