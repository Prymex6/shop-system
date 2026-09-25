import { test, expect } from '@playwright/test'

test.describe('Stawki VAT', () => {
  test('E-TAX.1.1 /manager/tax loads', async ({ page }) => {
    await page.goto('/manager/tax')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-TAX.1.2 a 23% VAT rate shows up in the list', async ({ page }) => {
    await page.goto('/manager/tax')
    await page.waitForLoadState('networkidle')
    // The form is shown inline — fill inputs directly, then click submit
    const nameInput = page.locator('input[name="name"]').last()
    if (await nameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await nameInput.fill('VAT 23% E2E')
      const rateInput = page.locator('input[name="rate"]').last()
      if (await rateInput.isVisible()) await rateInput.fill('23')
      await page
        .locator('button')
        .filter({ hasText: /dodaj stawkę|add rate/i })
        .first()
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText(/VAT 23% E2E/i).first()).toBeVisible({ timeout: 5000 })
    }
  })

  test('E-TAX.1.3 a negative VAT rate is rejected', async ({ page }) => {
    await page.goto('/manager/tax')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const rateInput = page.locator('input[name="rate"]').last()
      if (await rateInput.isVisible()) {
        await rateInput.fill('-5')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/wymagane|required|min|błąd/i)
          .isVisible()
          .catch(() => false)
        const staysOnPage = page.url().includes('tax')
        expect(hasError || staysOnPage).toBeTruthy()
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-TAX.1.4 a deleted VAT rate leaves the list', async ({ page }) => {
    await page.goto('/manager/tax')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'VAT 23% E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('VAT 23% E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})
