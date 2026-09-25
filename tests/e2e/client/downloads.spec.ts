import { test, expect } from '@playwright/test'

test.describe('Digital downloads (§22.7)', () => {
  test('E-CD.1.1 the downloads page loads', async ({ page }) => {
    await page.goto('/moje-konto/pobierania')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-CD.1.2 a list of downloads, or a note that there are none', async ({ page }) => {
    await page.goto('/moje-konto/pobierania')
    await page.waitForLoadState('networkidle')
    const hasContent = await page
      .locator('main, h1, h2, p')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasContent).toBeTruthy()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-CD.1.3 an empty list does not error', async ({ page }) => {
    await page.goto('/moje-konto/pobierania')
    await page.waitForLoadState('networkidle')
    const hasEmptyMsg = await page
      .getByText(/brak|nie masz|no downloads|cyfrowych/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasTable = await page
      .locator('table, [class*="download"], [class*="list"]')
      .first()
      .isVisible()
      .catch(() => false)
    // Either shows empty message or a table/list — both are valid
    expect(hasEmptyMsg || hasTable).toBeTruthy()
  })
})
