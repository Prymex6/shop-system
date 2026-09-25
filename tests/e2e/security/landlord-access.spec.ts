import { test, expect } from '@playwright/test'
import { LANDLORD_URL } from '../../../playwright.config'

// Supplementary landlord access control tests (covered by E47.5.x in access-control.spec.ts)

test.describe('Landlord access control, as a guest', () => {
  test.use({ storageState: { cookies: [], origins: [] }, baseURL: LANDLORD_URL })

  test('E47.5.1 a guest asking for /admin/dashboard gets /admin/login', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.2 a guest asking for /admin/tenants gets /admin/login', async ({ page }) => {
    await page.goto('/admin/tenants')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.3 a guest asking for /admin/plans gets /admin/login', async ({ page }) => {
    await page.goto('/admin/plans')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('a guest asking for /admin/support gets /admin/login', async ({ page }) => {
    await page.goto('/admin/support')
    await expect(page).toHaveURL(/admin\/login/)
  })
})

test.describe('Landlord: a super admin can reach every route', () => {
  test.use({ storageState: 'tests/e2e/.auth/super-admin.json', baseURL: LANDLORD_URL })

  test('a super admin reaches every landlord route without being sent to sign in', async ({ page }) => {
    for (const path of ['/admin/dashboard', '/admin/tenants', '/admin/plans', '/admin/support']) {
      await page.goto(path)
      await expect(page).not.toHaveURL(/admin\/login/, { timeout: 8000 })
    }
  })
})
