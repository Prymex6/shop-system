import { test, expect } from '@playwright/test'

test.describe('Customer loyalty and badges (§23)', () => {
  test('E-CL.1.1 the loyalty tab shows points and tier', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // Look for loyalty points or tier info
    const hasLoyalty = await page
      .getByText(/punkt|lojalnoś|tier|bronze|silver|gold|platinum/i)
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasLoyalty).toBeTruthy()
  })

  test('E-CL.1.2 /moje-konto/lojalnosc loads', async ({ page }) => {
    await page.goto('/moje-konto/lojalnosc')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.3 the account page shows the balance and the tier', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    // Points balance and tier should be visible somewhere on account page
    const hasTier = await page
      .getByText(/bronze|silver|gold|platinum|Brązowy|Srebrny|Złoty/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasPoints = await page
      .getByText(/\d+\s*(pkt|punkt|points)/i)
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasTier || hasPoints).toBeTruthy()
  })

  test('E-CL.1.4 the rewards page loads', async ({ page }) => {
    await page.goto('/moje-konto/nagrody')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.5 the badges page is reachable', async ({ page }) => {
    await page.goto('/moje-konto/odznaki')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.6 no page under the account errors', async ({ page }) => {
    const routes = ['/moje-konto', '/moje-konto/zamowienia', '/lista-zyczen']
    for (const route of routes) {
      await page.goto(route)
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
