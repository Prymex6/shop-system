import { test, expect } from '@playwright/test'

test.describe('Product categories', () => {
  test('E-KAT.1.1 the categories are listed', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-KAT.1.2 a new category shows up in the list', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    // The category form is in a modal, behind the new category button
    const addBtn = page
      .locator('a, button')
      .filter({ hasText: /nowa kategoria|dodaj kategorię|add category/i })
      .first()
    await expect(addBtn).toBeVisible({ timeout: 5000 })
    await addBtn.click()
    // Modal kategorii to div.fixed.inset-0 (bez role="dialog")
    // The name field is the required one, which is how it is told from the rest
    const nameInput = page.locator('.fixed.inset-0 input[required], .fixed input[required]').first()
    await expect(nameInput).toBeVisible({ timeout: 5000 })
    await nameInput.fill('Elektronika E2E')
    // Submit inside the modal, which is the div.fixed holding the form
    await page.locator('.fixed form button[type="submit"]').first().click()
    // Wait for the modal to close, which is how a successful save shows
    await expect(page.locator('.fixed.inset-0').first()).not.toBeVisible({ timeout: 8000 })
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Elektronika E2E').first()).toBeVisible({ timeout: 5000 })
  })

  test('E-KAT.1.3 a deleted category leaves the list', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    // Categories are divs rather than table rows, so the row is found by its exact text
    const row = page.locator('div.flex.items-center').filter({ hasText: 'Elektronika E2E' }).first()
    await expect(row).toBeVisible({ timeout: 5000 })
    await row.getByRole('button', { name: 'Usuń' }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Elektronika E2E')).not.toBeVisible({ timeout: 5000 })
  })
})
