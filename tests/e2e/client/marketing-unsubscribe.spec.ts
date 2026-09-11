import { test, expect } from '@playwright/test'

test.use({ storageState: { cookies: [], origins: [] } })

/**
 * Marketing unsubscribe – public GET /marketing/unsubscribe?token=...
 * HMAC-signed token. We test the route responds gracefully to invalid tokens.
 */
test.describe('Marketing Unsubscribe', () => {
  test('unsubscribe with missing token returns error, not 500', async ({ page }) => {
    const response = await page.goto('/marketing/unsubscribe')
    const status = response?.status() ?? 0
    expect(status).not.toBe(500)
    // Should show error page or redirect to menu
    await expect(page.locator('body')).toBeVisible()
  })

  test('unsubscribe with invalid token shows error message', async ({ page }) => {
    await page.goto('/marketing/unsubscribe?token=invalid-token-xyz')
    await page.waitForLoadState('networkidle')
    const hasError = await page
      .getByText(/nieprawidłowy|invalid|wygasły|błąd/i)
      .isVisible()
      .catch(() => false)
    const isHome = page.url().endsWith('/')
    expect(hasError || isHome).toBeTruthy()
  })
})
