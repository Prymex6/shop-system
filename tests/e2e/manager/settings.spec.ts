import { test, expect } from '@playwright/test'

test.describe('Ustawienia sklepu', () => {
  test('E-UST.1.1 Strona ustawień się ładuje', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/settings/)
    await expect(page.locator('main, form, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-UST.1.2 Zmień nazwę sklepu → Wartość zapisana', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')

    const nameInput = page.locator('input[name="shop_name"]').first()
    await expect(nameInput).toBeVisible({ timeout: 5000 })
    await nameInput.fill('E2E Shop Test')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')
    // Verify value persisted after reload
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('input[name="shop_name"]').first()).toHaveValue('E2E Shop Test')
  })

  test('E-UST.1.3 Zakładka SEO jest dostępna i ładuje się', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const seoTab = page.locator('button, a, [role="tab"]').filter({ hasText: /^SEO$/i }).first()
    if (await seoTab.isVisible()) {
      await seoTab.click()
      await page.waitForLoadState('networkidle')
      // Sprawdzamy że sekcja SEO jest widoczna — tytuł sekcji i pole meta title
      await expect(page.getByText('Meta title', { exact: false }).first()).toBeVisible({ timeout: 5000 })
      await expect(
        page.locator('input[placeholder*="Elektronika online"], input[placeholder*="Sklep Xyz"]').first(),
      ).toBeVisible({ timeout: 5000 })
    }
  })

  test('E-UST.1.4 Zakładka Wysyłka jest dostępna i ładuje się', async ({ page }) => {
    await page.goto('/manager/shipping')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/shipping/)
    await expect(page.locator('main, h1, h2').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-UST.1.5 Zakładka Płatności jest dostępna', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const paymentsTab = page
      .locator('button, a, [role="tab"]')
      .filter({ hasText: /płatności|payments/i })
      .first()
    if (await paymentsTab.isVisible()) {
      await paymentsTab.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-UST.1.6 Zakładka Licencja jest dostępna', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const licenseTab = page
      .locator('button, a, [role="tab"]')
      .filter({ hasText: /licencja|license/i })
      .first()
    if (await licenseTab.isVisible()) {
      await licenseTab.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
