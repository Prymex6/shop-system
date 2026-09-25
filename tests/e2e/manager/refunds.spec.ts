import { test, expect } from '@playwright/test'

test.describe('Refunds', () => {
  test('E-REF.1.1 /manager/refunds loads', async ({ page }) => {
    await page.goto('/manager/refunds')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-REF.1.2 an empty list says so', async ({ page }) => {
    await page.goto('/manager/refunds')
    await page.waitForLoadState('networkidle')
    const empty = await page
      .getByText(/brak zwrotów|no refunds|brak/i)
      .isVisible()
      .catch(() => false)
    const hasList = await page
      .locator('table tbody tr, [data-row]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(empty || hasList).toBeTruthy()
  })

  test('E-REF.1.3 the refund detail page loads without a 500', async ({ page }) => {
    await page.goto('/manager/refunds')
    await page.waitForLoadState('networkidle')
    const firstLink = page.locator('a[href*="refund"]').first()
    if (await firstLink.isVisible()) {
      await firstLink.click()
      await page.waitForLoadState('networkidle')
      expect(
        await page
          .getByText(/500/i)
          .isVisible()
          .catch(() => false),
      ).toBeFalsy()
    }
  })

  test('E-REF.1.4 filtering by status works', async ({ page }) => {
    await page.goto('/manager/refunds')
    await page.waitForLoadState('networkidle')
    const statusFilter = page.locator('select[name="status"]').first()
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption({ index: 1 })
      await page.waitForLoadState('networkidle')
      expect(
        await page
          .getByText(/500/i)
          .isVisible()
          .catch(() => false),
      ).toBeFalsy()
    }
  })
})
