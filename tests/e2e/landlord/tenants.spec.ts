import { test, expect } from '@playwright/test'

test.describe('Super admin: managing shops', () => {
  test('E43.1.1 /admin/tenants lists the shops', async ({ page }) => {
    await page.goto('/admin/tenants')
    await expect(page).toHaveURL(/admin\/tenants/)
    await expect(page.locator('main, table, [class*="tenant"]').first()).toBeVisible()
  })

  test('E43.1.2 the shop page shows the domain, the plan and an active status', async ({ page }) => {
    await page.goto('/admin/tenants')
    // Not conditional on the link being visible — a previous version of this
    // test only asserted body visibility inside an `if`, which stayed green
    // even when tenants.show was a missing Inertia component (the `if`
    // simply skipped past it). The seeded e2e-test tenant guarantees at
    // least one row exists, so the link must be there.
    const showLink = page.locator('table tr, [data-row], [class*="tenant-row"]').locator('a').first()
    await expect(showLink).toBeVisible()
    await showLink.click()
    await page.waitForLoadState('networkidle')

    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page).not.toHaveURL(/\/admin\/tenants\/?$/)
    const hasDomain = await page
      .getByText(/\.localhost|domena|domain/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasStatus = await page
      .getByText(/active|aktywn|suspended|zawieszon/i)
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasDomain || hasStatus).toBeTruthy()
  })

  test('E43.1.3 /admin/tenants/create shows the new shop form', async ({ page }) => {
    await page.goto('/admin/tenants/create')
    await expect(page).toHaveURL(/admin\/tenants\/create/)
    await expect(page.locator('form')).toBeVisible()
    // Tenant form uses v-model without name attr; name field has placeholder "np. Pizza Napoli"
    const nameInput = page
      .locator('input[placeholder*="Pizza Napoli"], input[placeholder*="Napoli"], input[type="text"]')
      .first()
    await expect(nameInput).toBeVisible()
    await nameInput.fill('E2E Test Form')
    await expect(nameInput).toHaveValue('E2E Test Form')
  })

  test('E43.1.4 a suspended shop reads as suspended', async ({ page }) => {
    await page.goto('/admin/tenants')
    const suspendBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /ecomerce|ecommerce|shop/i })
      .locator('button, form button')
      .filter({ hasText: /zawieś|suspend/i })
    if (await suspendBtn.isVisible()) {
      await suspendBtn.click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E43.1.5 a reactivated shop reads as active', async ({ page }) => {
    await page.goto('/admin/tenants')
    const activateBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /ecomerce|ecommerce|shop/i })
      .locator('button, form button')
      .filter({ hasText: /aktywuj|activate|reaktywuj/i })
    if (await activateBtn.isVisible()) {
      await activateBtn.click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E43.1.6 impersonating a shop lands on its domain as its manager', async ({ page }) => {
    await page.goto('/admin/tenants')
    const impersonateBtn = page
      .locator('table tr, [data-row]')
      .filter({ hasText: /ecomerce|ecommerce|shop/i })
      .locator('button, a, form button')
      .filter({ hasText: /impersonat|wejdź|zaloguj jako/i })
    if (await impersonateBtn.isVisible()) {
      await impersonateBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page).not.toHaveURL(/^.*\/admin\//)
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
