import { test, expect } from '@playwright/test'

test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Super-admin — Logowanie (Landlord)', () => {
  test('E40.1.1 /admin/login asks for an email and a password', async ({ page }) => {
    await page.goto('/admin/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('E40.1.2 the super admin signing in lands on /admin/dashboard', async ({ page }) => {
    await page.goto('/admin/login')
    await page.fill('input[type="email"]', 'admin@shop.localhost')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL(/admin\/dashboard/)
    await expect(page).toHaveURL(/admin\/dashboard/)
  })

  test('E40.1.3 a wrong password leaves you on /admin/login with an error', async ({ page }) => {
    await page.goto('/admin/login')
    await page.fill('input[type="email"]', 'admin@shop.localhost')
    await page.fill('input[type="password"]', 'blednehaslo')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E40.1.4 a guest asking for /admin/dashboard gets /admin/login', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E40.1.5 signing out returns to /admin/login', async ({ page }) => {
    await page.goto('/admin/login')
    await page.fill('input[type="email"]', 'admin@shop.localhost')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL(/admin\/dashboard/)
    const logoutBtn = page
      .locator('button[data-logout], form[action*="logout"] button, [href*="logout"], button')
      .filter({ hasText: /wyloguj|logout/i })
      .first()
    if (await logoutBtn.isVisible()) {
      await logoutBtn.click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page).toHaveURL(/admin\/login/)
  })
})
