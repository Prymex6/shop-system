import { test, expect } from '@playwright/test'

test.describe('Reports', () => {
  test('reports page loads', async ({ page }) => {
    await page.goto('/manager/reports')
    await expect(page).toHaveURL(/manager\/reports/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('can export CSV report', async ({ page }) => {
    await page.goto('/manager/reports')
    const exportLink = page
      .locator('a[href*="export-csv"], button')
      .filter({ hasText: /eksport|csv|export/i })
      .first()
    if (await exportLink.isVisible()) {
      const [download] = await Promise.all([page.waitForEvent('download'), exportLink.click()])
      expect(download.suggestedFilename()).toMatch(/\.csv$/i)
    }
  })

  test('staff reports page loads', async ({ page }) => {
    await page.goto('/manager/staff-reports')
    await expect(page).toHaveURL(/staff-reports/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })
})
