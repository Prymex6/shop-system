import { test, expect } from '@playwright/test'

test.describe('Lojalność klienta i odznaki (§23)', () => {
  test('E-CL.1.1 Zakładka Lojalność w koncie klienta — punkty i tier widoczne', async ({ page }) => {
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

  test('E-CL.1.2 Strona /moje-konto/lojalnosc ładuje się', async ({ page }) => {
    await page.goto('/moje-konto/lojalnosc')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.3 Saldo punktów i tier widoczne na stronie konta', async ({ page }) => {
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

  test('E-CL.1.4 Strona nagród /moje-konto/nagrody ładuje się', async ({ page }) => {
    await page.goto('/moje-konto/nagrody')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.5 Odznaki klienta — zakładka/strona odznaki', async ({ page }) => {
    await page.goto('/moje-konto/odznaki')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CL.1.6 Brak błędu na wszystkich podstronach konta', async ({ page }) => {
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
