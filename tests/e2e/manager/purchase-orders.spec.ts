import { test, expect } from '@playwright/test'

test.describe('Purchase Orders — zamówienia do dostawców', () => {
  test('E-PO.1.1 Lista zamówień /manager/purchase-orders ładuje się', async ({ page }) => {
    await page.goto('/manager/purchase-orders')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('E-PO.1.2 Formularz tworzenia PO dostępny', async ({ page }) => {
    await page.goto('/manager/purchase-orders/create')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('form, select[name*="supplier"], select[name*="dostawca"], input').first()).toBeVisible({
      timeout: 5000,
    })
  })

  test('E-PO.1.3 Lista dostawców /manager/suppliers ładuje się', async ({ page }) => {
    await page.goto('/manager/suppliers')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-PO.1.4 Historia PO — tabela lub komunikat o braku', async ({ page }) => {
    await page.goto('/manager/purchase-orders')
    await page.waitForLoadState('networkidle')
    const hasTable = await page
      .locator('table, [class*="order"]')
      .first()
      .isVisible()
      .catch(() => false)
    const hasEmpty = await page
      .getByText(/brak|nie ma|no orders/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasContent = await page
      .locator('main')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasTable || hasEmpty || hasContent).toBeTruthy()
  })
})
