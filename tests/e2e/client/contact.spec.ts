import { test, expect } from '@playwright/test'

test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Contact Form', () => {
  test('contact page loads', async ({ page }) => {
    await page.goto('/kontakt')
    await expect(page.locator('form').first()).toBeVisible()
  })

  test('submits contact form', async ({ page }) => {
    await page.goto('/kontakt')

    await page.locator('input[name="name"]').fill('E2E Nadawca')
    await page.locator('input[type="email"]').fill('e2e.contact@example.com')

    const subjectInput = page.locator('input[name="subject"]')
    if (await subjectInput.isVisible()) await subjectInput.fill('Testowe zapytanie E2E')

    await page.locator('textarea[name="message"]').fill('Wiadomość testowa z Playwright E2E.')

    await page.locator('button[type="submit"]').click()
    await page.waitForLoadState('networkidle', { timeout: 30000 })

    // Should show success
    const success = await page
      .getByText(/wysłan|dziękujemy|sent|thank/i)
      .first()
      .isVisible({ timeout: 10000 })
      .catch(() => false)
    expect(success).toBeTruthy()
  })

  test('validates required fields', async ({ page }) => {
    await page.goto('/kontakt')
    await page.locator('button[type="submit"]').click()
    await page.waitForLoadState('networkidle')

    // Should stay on contact page with validation errors
    await expect(page).toHaveURL(/kontakt/)
  })
})
