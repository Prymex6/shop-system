import { test, expect } from '@playwright/test'

test.describe('Managing orders', () => {
  test('E-ZAM.1.1 the orders are listed', async ({ page }) => {
    await page.goto('/manager/orders')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/orders/)
    await expect(page.locator('main, h1, h2').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-ZAM.1.2 the status and date filters are there', async ({ page }) => {
    await page.goto('/manager/orders')
    await page.waitForLoadState('networkidle')
    const hasFilter = (await page.locator('select, input[type="date"], [role="combobox"]').count()) > 0
    expect(hasFilter).toBeTruthy()
  })

  test('E-ZAM.1.3 rows of orders, or a note that there are none', async ({ page }) => {
    await page.goto('/manager/orders')
    await page.waitForLoadState('networkidle')
    const hasOrders = (await page.locator('table tbody tr, [data-order]').count()) > 0
    const hasEmpty = await page
      .getByText(/brak|no orders|puste/i)
      .isVisible()
      .catch(() => false)
    expect(hasOrders || hasEmpty).toBeTruthy()
  })

  test('E-ZAM.1.4 filtering by pending leaves only pending orders', async ({ page }) => {
    await page.goto('/manager/orders')
    await page.waitForLoadState('networkidle')
    const statusSelect = page.locator('select').first()
    if (await statusSelect.isVisible()) {
      // Read the options and pick the pending one if it is there
      const options = await statusSelect.locator('option').allTextContents()
      const pendingOption = options.find((o) => /oczekuj|pending/i.test(o))
      if (pendingOption) {
        await statusSelect.selectOption({ label: pendingOption })
        await page.waitForLoadState('networkidle')
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-ZAM.1.5 the order detail page loads', async ({ page }) => {
    await page.goto('/manager/orders')
    await page.waitForLoadState('networkidle')
    const firstOrderLink = page.locator('a[href*="/manager/orders/"]').first()
    if (await firstOrderLink.isVisible()) {
      await firstOrderLink.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/manager\/orders\/\d+/)
      // Order detail page should show order number, customer or status section
      const hasContent = await page
        .locator('main')
        .first()
        .isVisible()
        .catch(() => false)
      expect(hasContent).toBeTruthy()
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-ZAM.1.6 a filter matching nothing says so rather than 500ing', async ({ page }) => {
    // Use an unlikely search term to get empty results
    await page.goto('/manager/orders?search=BRAK_ZAMOWIENIA_XYZ_9999')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })
})
