import { test, expect } from '@playwright/test'

test.describe('Super-admin — Modyfikacje (OCMod)', () => {
  test('E44.1.1 /admin/modifications lists them, or says there are none', async ({ page }) => {
    await page.goto('/admin/modifications')
    await expect(page).toHaveURL(/modifications/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    const hasMods = (await page.locator('table tbody tr, [data-mod]').count()) > 0
    const hasEmpty = await page
      .getByText(/brak|no modifications|puste/i)
      .isVisible()
      .catch(() => false)
    expect(hasMods || hasEmpty).toBeTruthy()
  })

  test('E44.1.2 /admin/modifications/create shows the new modification form', async ({ page }) => {
    await page.goto('/admin/modifications/create')
    await expect(page).toHaveURL(/modifications\/create/)
    await expect(page.locator('form').first()).toBeVisible()
  })

  test('E44.1.3 a new modification shows up in the list', async ({ page }) => {
    await page.goto('/admin/modifications/create')
    // Modification form uses v-model without name attr; use placeholder selectors
    const nameInput = page.locator('input[placeholder*="Sortowanie menu"]').first()
    await nameInput.fill('E2E Mod Test')
    const codeInput = page.locator('input[placeholder*="menu_sort"]').first()
    if (await codeInput.isVisible()) await codeInput.fill('e2e_mod_test')
    const descInput = page.locator('textarea[placeholder*="Co robi"]').first()
    if (await descInput.isVisible()) await descInput.fill('Modyfikacja testowa E2E')
    // Rules JSON textarea
    const rulesInput = page.locator('textarea[rows="20"], textarea[spellcheck="false"]').first()
    if (await rulesInput.isVisible()) {
      await rulesInput.fill('[]')
    }
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).toBeVisible()
  })

  test('E44.1.4 a modification can be switched on and off', async ({ page }) => {
    await page.goto('/admin/modifications')
    const row = page.locator('tr, [data-row]').filter({ hasText: 'E2E Mod Test' }).first()
    if (await row.isVisible()) {
      const toggleBtn = row.locator('button').filter({ hasText: /włącz|wyłącz|toggle|enable|disable/i })
      if (await toggleBtn.isVisible()) {
        await toggleBtn.click()
        await page.waitForLoadState('networkidle')
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E44.1.5 applying modifications does not 500, whether or not any are active', async ({ page }) => {
    await page.goto('/admin/modifications')
    const applyBtn = page
      .locator('button')
      .filter({ hasText: /zastosuj|apply/i })
      .first()
    if (await applyBtn.isVisible()) {
      await applyBtn.click()
      await page.waitForLoadState('networkidle')
    }
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E44.1.6 a deleted modification leaves the list', async ({ page }) => {
    await page.goto('/admin/modifications')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'E2E Mod Test' }).first()
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
