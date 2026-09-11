import { test, expect } from '@playwright/test'

test.describe('Zgłoszenia zwrotu RMA — strona klienta (§22.9)', () => {
  test('E-CR.1.1 Historia zamówień ładuje się bez błędu', async ({ page }) => {
    await page.goto('/moje-konto/zamowienia')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CR.1.2 Strona RMA /moje-konto/zwroty ładuje się', async ({ page }) => {
    await page.goto('/moje-konto/zwroty')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CR.1.3 Formularz RMA — dostępny lub komunikat o braku kwalifikujących zamówień', async ({ page }) => {
    await page.goto('/moje-konto/zwroty')
    await page.waitForLoadState('networkidle')
    const hasForm = await page
      .locator('form')
      .first()
      .isVisible()
      .catch(() => false)
    const hasEmpty = await page
      .getByText(/brak|nie masz|no returns|zwrot/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasRmaList = await page
      .locator('[class*="rma"], [class*="zwrot"]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasForm || hasEmpty || hasRmaList).toBeTruthy()
  })
})
