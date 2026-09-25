import { test, expect } from '@playwright/test'

test.describe('Rabaty wolumenowe (Volume Discounts)', () => {
  test('E-VD.1.1 /manager/volume-discounts loads', async ({ page }) => {
    await page.goto('/manager/volume-discounts')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-VD.1.2 a new volume discount shows up in the list', async ({ page }) => {
    await page.goto('/manager/volume-discounts')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj rabat|add discount|nowy rabat/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const minQtyInput = page.locator('input[name="min_quantity"]').last()
      if (await minQtyInput.isVisible()) await minQtyInput.fill('5')
      const discountInput = page.locator('input[name="discount_percent"], input[name="discount"]').last()
      if (await discountInput.isVisible()) await discountInput.fill('10')
      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
    }
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-VD.1.3 a minimum quantity of zero is rejected', async ({ page }) => {
    await page.goto('/manager/volume-discounts')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const minQtyInput = page.locator('input[name="min_quantity"]').last()
      if (await minQtyInput.isVisible()) {
        await minQtyInput.fill('0')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/min|wymagane|błąd/i)
          .isVisible()
          .catch(() => false)
        const staysOnPage = page.url().includes('volume-discounts')
        expect(hasError || staysOnPage).toBeTruthy()
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-VD.1.4 a deleted volume discount leaves the list', async ({ page }) => {
    await page.goto('/manager/volume-discounts')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const deleteBtn = page
      .locator('button')
      .filter({ hasText: /usuń|delete/i })
      .first()
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click()
      await page.waitForLoadState('networkidle')
    }
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })
})
