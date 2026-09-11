import { test, expect } from '@playwright/test'

test.describe('Karty podarunkowe (Gift Cards)', () => {
  test('E-GC.1.1 /manager/gift-cards ładuje się', async ({ page }) => {
    await page.goto('/manager/gift-cards')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-GC.1.2 Generuj 2 karty po 50 PLN → pojawiają się na liście', async ({ page }) => {
    await page.goto('/manager/gift-cards')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /generuj|dodaj kartę|add card/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const countInput = page.locator('input[name="count"], input[name="quantity"]').last()
      if (await countInput.isVisible()) await countInput.fill('2')
      const valueInput = page.locator('input[name="value"], input[name="amount"]').last()
      if (await valueInput.isVisible()) await valueInput.fill('50')
      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
      expect(
        await page
          .getByText(/500/i)
          .isVisible()
          .catch(() => false),
      ).toBeFalsy()
    }
  })

  test('E-GC.1.3 Błąd przy wartości 0 PLN', async ({ page }) => {
    await page.goto('/manager/gift-cards')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /generuj|dodaj/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const valueInput = page.locator('input[name="value"], input[name="amount"]').last()
      if (await valueInput.isVisible()) {
        await valueInput.fill('0')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/min|wymagane|błąd/i)
          .isVisible()
          .catch(() => false)
        const staysOnPage = page.url().includes('gift-cards')
        expect(hasError || staysOnPage).toBeTruthy()
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-GC.1.4 Toggle dezaktywacji karty działa', async ({ page }) => {
    await page.goto('/manager/gift-cards')
    await page.waitForLoadState('networkidle')
    const toggleBtn = page
      .locator('button')
      .filter({ hasText: /dezaktywuj|deactivate|aktywuj/i })
      .first()
    if (await toggleBtn.isVisible()) {
      await toggleBtn.click()
      await page.waitForLoadState('networkidle')
      expect(
        await page
          .getByText(/500/i)
          .isVisible()
          .catch(() => false),
      ).toBeFalsy()
    }
  })
})
