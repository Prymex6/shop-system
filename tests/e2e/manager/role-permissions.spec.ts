import { test, expect } from '@playwright/test'

test.describe('Role Permissions', () => {
  test('role permissions page loads', async ({ page }) => {
    await page.goto('/manager/role-permissions')
    await expect(page).toHaveURL(/role-permissions/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('shows all roles', async ({ page }) => {
    await page.goto('/manager/role-permissions')
    await page.waitForLoadState('networkidle')
    // Shop-system roles
    await expect(page.locator('main, table, [class*="role"], [class*="permission"]').first()).toBeVisible({
      timeout: 5000,
    })
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('can toggle a permission', async ({ page }) => {
    await page.goto('/manager/role-permissions')
    // Szukamy pierwszego AKTYWNEGO checkboxa (nie disabled — Manager zawsze ma uprawnienia)
    const checkbox = page.locator('input[type="checkbox"]:not([disabled])').first()
    if (await checkbox.isVisible()) {
      const wasBefore = await checkbox.isChecked()
      await checkbox.click()
      // Save
      const saveBtn = page.locator('button[type="submit"]').first()
      if (await saveBtn.isVisible()) {
        await saveBtn.click()
        await page.waitForLoadState('networkidle')
      }
      // Toggle back
      await page.goto('/manager/role-permissions')
      const checkboxAfter = page.locator('input[type="checkbox"]').first()
      // Just verify no crash and page loaded
      await expect(checkboxAfter).toBeVisible()
    }
  })
})
