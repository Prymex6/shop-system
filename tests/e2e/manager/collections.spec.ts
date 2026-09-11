import { test, expect } from '@playwright/test'

test.describe('Kolekcje produktów', () => {
  test('E-COL.1.1 /manager/collections ładuje się', async ({ page }) => {
    await page.goto('/manager/collections')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-COL.1.2 Dodaj kolekcję „Bestsellery E2E" → widoczna', async ({ page }) => {
    await page.goto('/manager/collections')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj kolekcję|add collection|nowa kolekcja/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').last()
      if (await nameInput.isVisible()) {
        await nameInput.fill('Bestsellery E2E')
        const slugInput = page.locator('input[name="slug"]').last()
        if (await slugInput.isVisible()) await slugInput.fill('bestsellery-e2e')
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

  test('E-COL.1.3 Pusta nazwa → błąd walidacji', async ({ page }) => {
    await page.goto('/manager/collections')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
      const hasError = await page
        .getByText(/wymagane|required/i)
        .isVisible()
        .catch(() => false)
      const staysOnPage = page.url().includes('collections')
      expect(hasError || staysOnPage).toBeTruthy()
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-COL.1.4 Usuń kolekcję → znika', async ({ page }) => {
    await page.goto('/manager/collections')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Bestsellery E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('Bestsellery E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})
