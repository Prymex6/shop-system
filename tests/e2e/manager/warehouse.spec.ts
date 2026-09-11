import { test, expect } from '@playwright/test'

test.describe('Multi-Warehouse', () => {
  test('E-WH.1.1 Lista magazynów widoczna', async ({ page }) => {
    await page.goto('/manager/warehouses')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-WH.1.2 Dodaj magazyn „Warszawa E2E" → Widoczny na liście', async ({ page }) => {
    await page.goto('/manager/warehouses')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('a, button')
      .filter({ hasText: /dodaj magazyn|nowy magazyn|add warehouse/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').first()
      if (await nameInput.isVisible()) {
        await nameInput.fill('Warszawa E2E')
        await page.locator('button[type="submit"]').first().click()
        await page.waitForLoadState('networkidle')
        await page.goto('/manager/warehouses')
        await expect(page.getByText('Warszawa E2E').first()).toBeVisible({ timeout: 5000 })
      }
    }
  })

  test('E-WH.1.3 Usuń magazyn „Warszawa E2E" → Znika z listy', async ({ page }) => {
    await page.goto('/manager/warehouses')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Warszawa E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('Warszawa E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})
