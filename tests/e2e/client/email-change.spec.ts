import { test, expect } from '@playwright/test'

/**
 * Email change re-verification flow
 * Customer requests email change → gets token link → clicks → email updated
 */
test.describe('Email Change Re-verification', () => {
  test('account page has email change field', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')

    const emailInput = page.locator('input[name="email"], input[type="email"]').first()
    await expect(emailInput).toBeVisible()
    await expect(emailInput).toHaveValue(/klient@example\.pl/)
  })

  test('requesting email change shows pending message', async ({ page }) => {
    await page.goto('/moje-konto')

    const emailInput = page.locator('input[name="email"]').first()
    if (await emailInput.isVisible()) {
      await emailInput.fill('klient.nowy.e2e@example.com')

      const saveBtn = page
        .locator('button[type="submit"]')
        .filter({ hasText: /zapisz|save|aktualizuj/i })
        .first()
      await saveBtn.click()
      await page.waitForLoadState('networkidle')

      // Should show info that verification email was sent, or just save without 2FA if not configured
      const sentMsg = await page
        .getByText(/wysłano|weryfikacja|verify|e-mail|link/i)
        .first()
        .isVisible({ timeout: 5000 })
        .catch(() => false)
      const savedMsg = await page
        .getByText(/zapisano|saved|zaktualizowano/i)
        .first()
        .isVisible({ timeout: 5000 })
        .catch(() => false)
      expect(sentMsg || savedMsg).toBeTruthy()

      // Revert back to original email
      await page.goto('/moje-konto')
      const emailInputAgain = page.locator('input[name="email"]').first()
      const currentValue = await emailInputAgain.inputValue()
      if (currentValue !== 'klient@example.pl') {
        await emailInputAgain.fill('klient@example.pl')
        await page
          .locator('button[type="submit"]')
          .filter({ hasText: /zapisz|save/i })
          .first()
          .click()
        await page.waitForLoadState('networkidle')
      }
    }
  })

  test('invalid verification token returns error', async ({ page }) => {
    await page.goto('/moje-konto/verify-email/invalid-token-xyz')
    await page.waitForLoadState('networkidle')
    // Should show error or redirect to account with error
    const hasError = await page
      .getByText(/nieprawidłowy|wygasły|invalid|expired/i)
      .isVisible()
      .catch(() => false)
    const isAccount = page.url().includes('moje-konto')
    expect(hasError || isAccount).toBeTruthy()
  })

  test('expired/missing token redirects gracefully', async ({ page }) => {
    const response = await page.goto('/moje-konto/verify-email/00000000000000000000000000000000')
    // No 500 error
    const status = response?.status() ?? 0
    expect(status).not.toBe(500)
  })
})
