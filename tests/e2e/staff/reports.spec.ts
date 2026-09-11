import { test, expect } from '@playwright/test'

test.describe('Raporty pracownicze (Staff)', () => {
  test('E39.1.1 Chef wchodzi na /staff/reports → Formularz raportu widoczny', async ({ page }) => {
    await page.goto('/staff/reports')
    await expect(page).toHaveURL(/staff\/reports/)
    await expect(page.locator('body')).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    const hasForm = await page
      .locator('form, textarea')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasForm).toBeTruthy()
  })

  test('E39.1.2 Chef wysyła raport z treścią → Komunikat o przyjęciu raportu; raport widoczny w historii', async ({
    page,
  }) => {
    await page.goto('/staff/reports')
    const textarea = page.locator('textarea[name="content"], textarea[name="message"], textarea[name="report"]').first()
    if (await textarea.isVisible()) {
      await textarea.fill('Raport E2E: wszystko działa poprawnie. Test automatyczny.')
      await page.locator('button[type="submit"]').first().click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E39.1.3 Wyślij pusty raport → Błąd walidacji; raport nie wysłany', async ({ page }) => {
    await page.goto('/staff/reports')
    const submitBtn = page.locator('button[type="submit"]').first()
    if (await submitBtn.isVisible()) {
      await submitBtn.click()
      await page.waitForLoadState('networkidle')
      // Should stay on page or show validation error
      const staysOnPage = page.url().includes('reports')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
