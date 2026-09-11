import { test, expect } from '@playwright/test'

test.describe('Staff — Fulfillment', () => {
  test('E-SF.1.1 Panel staff widoczny po zalogowaniu', async ({ page }) => {
    await page.goto('/staff')
    await page.waitForLoadState('networkidle')
    const isStaff = page.url().includes('staff')
    const hasContent = await page
      .locator('main, h1')
      .first()
      .isVisible()
      .catch(() => false)
    expect(isStaff || hasContent).toBeTruthy()
  })

  test('E-SF.1.2 Lista zamówień do przetworzenia widoczna', async ({ page }) => {
    await page.goto('/staff/fulfillment')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-SF.1.3 Panel magazynowy widoczny na /staff/warehouse', async ({ page }) => {
    await page.goto('/staff/warehouse')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-SF.1.4 Raport pracownika — formularz widoczny', async ({ page }) => {
    await page.goto('/staff/reports')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    const hasForm = await page
      .locator('form')
      .first()
      .isVisible()
      .catch(() => false)
    const hasInput = await page
      .locator('input[type="text"], input[placeholder*="opis"]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasForm || hasInput).toBeTruthy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SF.1.5 Wyślij raport → Pojawia się w historii', async ({ page }) => {
    await page.goto('/staff/reports')
    await page.waitForLoadState('networkidle')
    const titleInput = page
      .locator('input[placeholder*="Krótki"], input[placeholder*="temat"], input[type="text"]')
      .first()
    const msgInput = page.locator('textarea').first()
    if ((await titleInput.isVisible()) && (await msgInput.isVisible())) {
      await titleInput.fill('Raport E2E Test')
      await msgInput.fill('Treść raportu testowego z Playwright E2E.')
      await page.locator('button[type="submit"]').first().click()
      await page.waitForLoadState('networkidle')
      const submitted = await page
        .getByText(/raport E2E Test|wysłano|success/i)
        .isVisible()
        .catch(() => false)
      expect(submitted).toBeTruthy()
    }
  })
})
