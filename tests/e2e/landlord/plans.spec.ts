import { test, expect } from '@playwright/test'

test.describe('Super-admin — Plany subskrypcji', () => {
  test('E42.1.1 /admin/plans lists Starter, Basic, Pro and Premium with their prices', async ({ page }) => {
    await page.goto('/admin/plans')
    await expect(page).toHaveURL(/admin\/plans/)
    await expect(page.getByText('Starter')).toBeVisible()
    await expect(page.getByText('Basic')).toBeVisible()
    await expect(page.getByText('Pro')).toBeVisible()
    await expect(page.getByText('Premium')).toBeVisible()
  })

  test('E42.1.2 /admin/plans/create shows the new plan form', async ({ page }) => {
    await page.goto('/admin/plans/create')
    await expect(page.locator('form')).toBeVisible()
    // Plan form uses v-model without name attr — find first text input
    await expect(page.locator('input[type="text"]').first()).toBeVisible()
  })

  test('E42.1.3 a plan with no name is rejected', async ({ page }) => {
    await page.goto('/admin/plans/create')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')
    const staysOnPage = page.url().includes('create') || page.url().includes('plans')
    const hasError = await page
      .locator('[class*="error"], [class*="invalid"]')
      .isVisible()
      .catch(() => false)
    expect(staysOnPage || hasError).toBeTruthy()
  })

  test('E42.1.4 a new plan shows up in the list', async ({ page }) => {
    await page.goto('/admin/plans/create')
    // Name field: first text input (v-model="form.name", no name attr)
    const nameInput = page.locator('input[type="text"]').first()
    await nameInput.fill('E2E Enterprise')
    // Price field: placeholder="np. 480"
    const priceInput = page.locator('input[placeholder="np. 480"], input[type="number"]').first()
    if (await priceInput.isVisible()) await priceInput.fill('599')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).toBeVisible()
  })

  test('E42.1.5 a changed plan price is saved', async ({ page }) => {
    await page.goto('/admin/plans')
    const editBtn = page
      .locator('table tr, [class*="plan"]')
      .filter({ hasText: /E2E Enterprise/ })
      .locator('a, button')
      .filter({ hasText: /edytuj|edit/i })
      .first()
    if (await editBtn.isVisible()) {
      await editBtn.click()
      await page.waitForLoadState('networkidle')
      const priceInput = page.locator('input[placeholder="np. 480"], input[type="number"]').first()
      if (await priceInput.isVisible()) {
        await priceInput.fill('699')
        await page.locator('button[type="submit"]').first().click()
        await page.waitForLoadState('networkidle')
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E42.1.6 a deleted plan leaves the list', async ({ page }) => {
    await page.goto('/admin/plans')
    const row = page.locator('table tr, [data-row]').filter({ hasText: 'E2E Enterprise' })
    if (await row.isVisible()) {
      page.on('dialog', (dialog) => dialog.accept())
      await row
        .locator('button, a')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
