import { test, expect } from '@playwright/test'
import { LANDLORD_URL } from '../../../playwright.config'

// Supplementary landlord access control tests (covered by E47.5.x in access-control.spec.ts)

test.describe('Landlord — kontrola dostępu (gość)', () => {
  test.use({ storageState: { cookies: [], origins: [] }, baseURL: LANDLORD_URL })

  test('E47.5.1 Niezalogowany wchodzi na /admin/dashboard → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.2 Niezalogowany wchodzi na /admin/tenants → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('/admin/tenants')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.3 Niezalogowany wchodzi na /admin/plans → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('/admin/plans')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('Niezalogowany wchodzi na /admin/support → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('/admin/support')
    await expect(page).toHaveURL(/admin\/login/)
  })
})

test.describe('Landlord — super-admin ma dostęp do wszystkich tras', () => {
  test.use({ storageState: 'tests/e2e/.auth/super-admin.json', baseURL: LANDLORD_URL })

  test('Super-admin może wchodzić na wszystkie trasy landlord bez przekierowania na login', async ({ page }) => {
    for (const path of ['/admin/dashboard', '/admin/tenants', '/admin/plans', '/admin/support']) {
      await page.goto(path)
      await expect(page).not.toHaveURL(/admin\/login/, { timeout: 8000 })
    }
  })
})
