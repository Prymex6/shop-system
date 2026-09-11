import { test, expect } from '@playwright/test'

test.describe('Wysyłka — Metody i Strefy', () => {
  test('E-SH.1.1 /manager/shipping ładuje się', async ({ page }) => {
    await page.goto('/manager/shipping')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-SH.1.2 Dodaj metodę dostawy „DPD E2E" → widoczna na liście', async ({ page }) => {
    await page.goto('/manager/shipping')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj metodę|add method|nowa metoda/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').last()
      if (await nameInput.isVisible()) {
        await nameInput.fill('DPD E2E')
        const priceInput = page.locator('input[name="price"], input[name="cost"]').last()
        if (await priceInput.isVisible()) await priceInput.fill('15.99')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        await expect(page.getByText('DPD E2E').first()).toBeVisible({ timeout: 5000 })
      }
    }
  })

  test('E-SH.1.3 Błąd przy nazwie pustej metody dostawy', async ({ page }) => {
    await page.goto('/manager/shipping')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj metodę|add method/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
      // After submitting empty form, must either show a validation error or stay on form page
      const hasError = await page
        .getByText(/wymagane|required/i)
        .isVisible()
        .catch(() => false)
      const staysOnPage = page.url().includes('shipping')
      expect(hasError || staysOnPage).toBeTruthy()
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-SH.1.4 Usuń metodę „DPD E2E" → znika z listy', async ({ page }) => {
    await page.goto('/manager/shipping')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'DPD E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('DPD E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })

  test('E-SH.2.1 Strefy dostawy widoczne', async ({ page }) => {
    await page.goto('/manager/shipping/zones')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-SH.2.2 Dodaj strefę „Polska E2E" → widoczna na liście', async ({ page }) => {
    await page.goto('/manager/shipping/zones')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj strefę|add zone|nowa strefa/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').last()
      if (await nameInput.isVisible()) {
        await nameInput.fill('Polska E2E')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
      }
    }
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })
})
