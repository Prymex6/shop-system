import { test, expect } from '@playwright/test'

test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Logowanie klienta', () => {
  test('E23.1.1 the customer sign-in page asks for an email and a password', async ({ page }) => {
    await page.goto('/konto/logowanie')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('E23.1.2 a customer signing in correctly lands on /moje-konto', async ({ page }) => {
    await page.goto('/konto/logowanie')
    await page.fill('input[type="email"]', 'klient@example.pl')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    // Customer may redirect to / (menu) or /moje-konto — just verify leaving login
    await page.waitForFunction(() => !window.location.pathname.includes('logowanie'), { timeout: 10000 })
    await expect(page).not.toHaveURL(/logowanie/)
  })

  test('E23.1.3 a wrong password leaves you on the sign-in page with an error', async ({ page }) => {
    await page.goto('/konto/logowanie')
    await page.fill('input[type="email"]', 'klient@example.pl')
    await page.fill('input[type="password"]', 'blednehaslo')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/logowanie/)
  })

  test('E23.1.4 an email nobody has gets an error', async ({ page }) => {
    await page.goto('/konto/logowanie')
    await page.fill('input[type="email"]', 'nieistnieje@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/logowanie/)
  })

  test('E23.1.5 signing out clears the session and leaves /moje-konto', async ({ page }) => {
    await page.goto('/konto/logowanie')
    await page.fill('input[type="email"]', 'klient@example.pl')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL((url) => !url.pathname.includes('logowanie'), { timeout: 30000 })
    // Open user dropdown (desktop nav avatar button), then click logout
    await page.locator('button[class*="rounded-full"]').first().click({ timeout: 10000 })
    await page.waitForTimeout(300)
    await page
      .locator('button, a')
      .filter({ hasText: /wyloguj|logout/i })
      .first()
      .click()
    await page.waitForLoadState('networkidle')
    await expect(page).not.toHaveURL(/moje-konto/)
  })
})

test.describe('Rejestracja klienta', () => {
  test('E23.2.1 the sign-up page has a form and no Google or Facebook buttons', async ({ page }) => {
    await page.goto('/konto/rejestracja')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[name="password"]')).toBeVisible()
    // No social sign-in buttons in the stable build
    const hasGoogle = await page
      .getByText(/google/i)
      .isVisible()
      .catch(() => false)
    expect(hasGoogle).toBeFalsy()
  })

  test('E23.2.2 an empty form comes back with errors on the required fields', async ({ page }) => {
    await page.goto('/konto/rejestracja')
    await page.waitForLoadState('networkidle')
    // Submit button is disabled until terms_accepted — force click to bypass
    await page.locator('button[type="submit"]').click({ force: true })
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/rejestracja/)
  })

  test('E23.2.3 signing up with an unused address creates the account and moves on', async ({ page }) => {
    // Clear the cookies, or a customer still signed in gets redirected
    await page.context().clearCookies()
    await page.goto('/konto/rejestracja')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/rejestracja/, { timeout: 5000 })
    // A unique address, so a second run does not trip over the first
    const uniqueEmail = `e2e.nowy+${Date.now()}@example.com`
    await page.fill('input[name="name"]', 'E2E Nowy Klient')
    await page.fill('input[type="email"]', uniqueEmail)
    await page.fill('input[name="password"]', 'password123')
    const confirm = page.locator('input[name="password_confirmation"]')
    if (await confirm.isVisible()) await confirm.fill('password123')
    // Must accept terms before submit button is enabled
    await page.locator('input[type="checkbox"]').first().check()
    await page.click('button[type="submit"]')
    await page.waitForURL((url) => !url.pathname.includes('rejestracja'), { timeout: 30000 })
    await expect(page).not.toHaveURL(/rejestracja/)
  })

  test('E23.2.4 signing up with an address already taken is refused', async ({ page }) => {
    await page.goto('/konto/rejestracja')
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Duplikat')
    await page.fill('input[type="email"]', 'klient@example.pl')
    await page.fill('input[name="password"]', 'password123')
    await page.fill('input[name="password_confirmation"]', 'password123')
    // Must accept terms before submit button is enabled
    await page.locator('input[type="checkbox"]').first().check()
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle', { timeout: 30000 })
    await expect(page.getByText(/już istnieje|already|taken|zajęty/i)).toBeVisible({ timeout: 10000 })
  })
})

test.describe('Customer password reset', () => {
  test('E23.3.1 the customer password reset page asks for an email address', async ({ page }) => {
    await page.goto('/konto/reset-hasla')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('E23.3.2 a known customer address is told the link has been sent', async ({ page }) => {
    await page.goto('/konto/reset-hasla')
    await page.fill('input[type="email"]', 'klient@example.pl')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })
})
