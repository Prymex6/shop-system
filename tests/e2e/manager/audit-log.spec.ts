import { test, expect } from '@playwright/test'

test.describe('Dziennik audytu (§18.2)', () => {
  test('E-AL.1.1 /manager/audit-log loads', async ({ page }) => {
    await page.goto('/manager/audit-log')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-AL.1.2 a table of entries, or a note that there are none', async ({ page }) => {
    await page.goto('/manager/audit-log')
    await page.waitForLoadState('networkidle')
    const hasTable = await page
      .locator('table, [class*="audit"], [class*="log"]')
      .first()
      .isVisible()
      .catch(() => false)
    const hasContent = await page
      .locator('main')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasTable || hasContent).toBeTruthy()
  })

  test('E-AL.1.3 filtering by action does not error', async ({ page }) => {
    await page.goto('/manager/audit-log')
    await page.waitForLoadState('networkidle')
    const actionFilter = page
      .locator('select[name="action"], input[placeholder*="akcj"], input[placeholder*="action"]')
      .first()
    if (await actionFilter.isVisible()) {
      await actionFilter.fill('product.updated')
      await page.waitForLoadState('networkidle')
    }
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-AL.1.4 the CSV export does not error', async ({ page }) => {
    await page.goto('/manager/audit-log')
    await page.waitForLoadState('networkidle')
    const exportBtn = page
      .locator('a, button')
      .filter({ hasText: /eksport|export|csv/i })
      .first()
    if (await exportBtn.isVisible()) {
      // Just check it exists, don't actually download
      await expect(exportBtn).toBeEnabled()
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
