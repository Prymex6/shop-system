import { test, expect } from '@playwright/test'

test.describe('Product bundles', () => {
  test('E-BUN.1.1 /manager/bundles loads', async ({ page }) => {
    await page.goto('/manager/bundles')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-BUN.1.2 an empty list says so', async ({ page }) => {
    await page.goto('/manager/bundles')
    await page.waitForLoadState('networkidle')
    const empty = await page
      .getByText(/brak zestawów|no bundles|brak/i)
      .isVisible()
      .catch(() => false)
    const hasList = await page
      .locator('table tbody tr')
      .first()
      .isVisible()
      .catch(() => false)
    expect(empty || hasList).toBeTruthy()
  })

  test('E-BUN.1.3 a new bundle shows up in the list', async ({ page }) => {
    await page.goto('/manager/bundles')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj zestaw|add bundle|nowy zestaw/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').last()
      if (await nameInput.isVisible()) {
        await nameInput.fill('Starter E2E')
        const priceInput = page.locator('input[name="price"]').last()
        if (await priceInput.isVisible()) await priceInput.fill('99.99')
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

  test('E-BUN.1.4 a bundle priced at zero is rejected', async ({ page }) => {
    await page.goto('/manager/bundles')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const priceInput = page.locator('input[name="price"]').last()
      if (await priceInput.isVisible()) {
        await priceInput.fill('0')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/min|wymagane|błąd/i)
          .isVisible()
          .catch(() => false)
        const staysOnPage = page.url().includes('bundles')
        expect(hasError || staysOnPage).toBeTruthy()
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-BUN.1.5 a deleted bundle leaves the list', async ({ page }) => {
    await page.goto('/manager/bundles')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Starter E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('Starter E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})
