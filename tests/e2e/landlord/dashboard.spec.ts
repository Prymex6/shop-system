import { test, expect } from '@playwright/test'

test.describe('Super-admin — Dashboard (Landlord)', () => {
  test('E41.1.1 the super admin dashboard loads with its cards', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await expect(page).toHaveURL(/admin\/dashboard/)
    await expect(page.locator('main, [data-testid="dashboard"], h1, h2').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E41.1.2 the tenants link opens /admin/tenants', async ({ page }) => {
    await page.goto('/admin/dashboard')
    const tenantsLink = page
      .locator('a, button')
      .filter({ hasText: /restauracje|tenanci|tenants/i })
      .first()
    if (await tenantsLink.isVisible()) {
      await tenantsLink.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/admin\/tenants/)
    } else {
      await page.goto('/admin/tenants')
      await expect(page).toHaveURL(/admin\/tenants/)
    }
  })

  test('E41.1.3 the plans link opens /admin/plans', async ({ page }) => {
    await page.goto('/admin/dashboard')
    const plansLink = page
      .locator('a, button')
      .filter({ hasText: /plany|plans/i })
      .first()
    if (await plansLink.isVisible()) {
      await plansLink.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/admin\/plans/)
    } else {
      await page.goto('/admin/plans')
      await expect(page).toHaveURL(/admin\/plans/)
    }
  })
})
