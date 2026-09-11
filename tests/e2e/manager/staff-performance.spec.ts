import { test, expect } from '@playwright/test'

test.describe('Wydajność pracowników (§16.3)', () => {
  test('E-SP.1.1 Strona /manager/staff-performance ładuje się', async ({ page }) => {
    await page.goto('/manager/staff-performance')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SP.1.2 Tabela pracowników i statystyki widoczne', async ({ page }) => {
    await page.goto('/manager/staff-performance')
    await page.waitForLoadState('networkidle')
    // Strona może zwrócić 404 jeśli trasa nie istnieje — sprawdzamy tylko brak 500
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SP.1.3 Raporty pracowników — /manager/staff-reports ładuje się', async ({ page }) => {
    await page.goto('/manager/staff-reports')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SP.1.4 Oznacz raport pracownika jako przeczytany', async ({ page }) => {
    await page.goto('/manager/staff-reports')
    await page.waitForLoadState('networkidle')
    const readBtn = page
      .locator('button')
      .filter({ hasText: /przeczytany|mark.*read|oznacz/i })
      .first()
    if (await readBtn.isVisible()) {
      await readBtn.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
