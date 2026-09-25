import { test, expect } from '@playwright/test'

// ── E47: security, access control ─────────────────────────────────────────────

test.describe('E47 - a guest, signed in as nobody', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E47.1.1 a guest asking for /manager/ gets /login', async ({ page }) => {
    await page.goto('/manager/')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.2 a guest asking for /manager/orders gets /login', async ({ page }) => {
    await page.goto('/manager/orders')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.3 a guest asking for /manager/settings gets /login', async ({ page }) => {
    await page.goto('/manager/settings')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.4 a guest asking for /staff gets /login', async ({ page }) => {
    await page.goto('/staff')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.5 a guest asking for /staff/fulfillment gets /login', async ({ page }) => {
    await page.goto('/staff/fulfillment')
    await page.waitForLoadState('networkidle')
    const url = page.url()
    expect(url.includes('login') || !url.includes('fulfillment')).toBeTruthy()
  })

  test('E47.1.6 a guest asking for /moje-konto gets the customer sign-in page', async ({ page }) => {
    await page.goto('/moje-konto')
    await expect(page).toHaveURL(/logowanie/)
  })
})

test.describe('E47 — Rola Staff (fulfillment)', () => {
  test.use({ storageState: 'tests/e2e/.auth/staff.json' })

  test('E47.2.1 staff asking for /manager/ are turned away', async ({ page }) => {
    await page.goto('/manager/')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || url.includes('staff')
    const has403 = await page
      .getByText(/403|forbidden|brak dostępu/i)
      .isVisible()
      .catch(() => false)
    expect(denied || has403).toBeTruthy()
  })

  test('E47.2.2 staff asking for /manager/settings do not get the settings', async ({ page }) => {
    await page.goto('/manager/settings')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || !url.includes('manager/settings')
    expect(denied).toBeTruthy()
  })

  test('E47.2.3 staff asking for /manager/staff are turned away', async ({ page }) => {
    await page.goto('/manager/staff')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || !url.includes('manager/staff')
    expect(denied).toBeTruthy()
  })
})

test.describe('E47 — Landlord vs Tenant (niezalogowany)', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E47.5.1 a guest asking for /admin/dashboard gets /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/dashboard')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.2 a guest asking for /admin/tenants gets /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/tenants')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.3 a guest asking for /admin/plans gets /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/plans')
    await expect(page).toHaveURL(/admin\/login/)
  })
})
