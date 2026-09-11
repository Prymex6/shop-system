import { test, expect } from '@playwright/test'

/**
 * PDF Invoice – printable HTML rendered at /zamówienia/{orderNumber}/faktura
 * Uses customer storageState (klient@example.pl)
 */
test.describe('Order Invoice', () => {
  test('invoice route responds (200 or 404 for non-existent order)', async ({ page }) => {
    const response = await page.goto('/zamówienia/ORD-000000-0001/faktura')
    const status = response?.status() ?? 0
    // 200 if order exists, 404 if not – both are valid responses (no 500)
    expect([200, 302, 404]).toContain(status)
  })

  test('invoice page renders printable HTML when order exists', async ({ page }) => {
    // Navigate to account to find a real order number if any orders exist
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')

    // Look for an invoice link in order history
    const invoiceLink = page.locator('a[href*="faktura"]').first()
    if (await invoiceLink.isVisible()) {
      await invoiceLink.click()
      await page.waitForLoadState('networkidle')

      // Invoice is a printable HTML page with order data
      await expect(page.getByText(/faktura|invoice/i)).toBeVisible({ timeout: 8000 })
      await expect(page.locator('body')).toBeVisible()
    }
  })
})

/**
 * Manager Invoice access
 */
test.describe('Manager Invoice', () => {
  test.use({ storageState: 'tests/e2e/.auth/manager.json' })

  test('manager invoice route responds without 500', async ({ page }) => {
    const response = await page.goto('/manager/orders/ORD-000000-0001/faktura')
    const status = response?.status() ?? 0
    expect([200, 302, 403, 404]).toContain(status)
  })
})
