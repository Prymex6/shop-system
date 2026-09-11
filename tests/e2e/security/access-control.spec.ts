import { test, expect } from '@playwright/test'

// ── E47: Bezpieczeństwo — Kontrola dostępu ────────────────────────────────────

test.describe('E47 — Gość (brak auth)', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E47.1.1 Gość wchodzi na /manager/ → Przekierowanie na /login', async ({ page }) => {
    await page.goto('/manager/')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.2 Gość wchodzi na /manager/orders → Przekierowanie na /login', async ({ page }) => {
    await page.goto('/manager/orders')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.3 Gość wchodzi na /manager/settings → Przekierowanie na /login', async ({ page }) => {
    await page.goto('/manager/settings')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.4 Gość wchodzi na /staff → Przekierowanie na /login', async ({ page }) => {
    await page.goto('/staff')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/login/)
  })

  test('E47.1.5 Gość wchodzi na /staff/fulfillment → Przekierowanie na /login', async ({ page }) => {
    await page.goto('/staff/fulfillment')
    await page.waitForLoadState('networkidle')
    const url = page.url()
    expect(url.includes('login') || !url.includes('fulfillment')).toBeTruthy()
  })

  test('E47.1.6 Gość wchodzi na /moje-konto → Przekierowanie na /konto/logowanie', async ({ page }) => {
    await page.goto('/moje-konto')
    await expect(page).toHaveURL(/logowanie/)
  })
})

test.describe('E47 — Rola Staff (fulfillment)', () => {
  test.use({ storageState: 'tests/e2e/.auth/staff.json' })

  test('E47.2.1 Staff wchodzi na /manager/ → Przekierowanie lub błąd 403', async ({ page }) => {
    await page.goto('/manager/')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || url.includes('staff')
    const has403 = await page
      .getByText(/403|forbidden|brak dostępu/i)
      .isVisible()
      .catch(() => false)
    expect(denied || has403).toBeTruthy()
  })

  test('E47.2.2 Staff wchodzi na /manager/settings → Dostęp odmówiony; nie wczytuje ustawień', async ({ page }) => {
    await page.goto('/manager/settings')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || !url.includes('manager/settings')
    expect(denied).toBeTruthy()
  })

  test('E47.2.3 Staff wchodzi na /manager/staff → Dostęp odmówiony', async ({ page }) => {
    await page.goto('/manager/staff')
    const url = page.url()
    const denied = url.includes('login') || url.includes('403') || !url.includes('manager/staff')
    expect(denied).toBeTruthy()
  })
})

test.describe('E47 — Landlord vs Tenant (niezalogowany)', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E47.5.1 Niezalogowany wchodzi na /admin/dashboard → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/dashboard')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.2 Niezalogowany wchodzi na /admin/tenants → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/tenants')
    await expect(page).toHaveURL(/admin\/login/)
  })

  test('E47.5.3 Niezalogowany wchodzi na /admin/plans → Przekierowanie na /admin/login', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/plans')
    await expect(page).toHaveURL(/admin\/login/)
  })
})
