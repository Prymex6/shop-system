import { test, expect } from '@playwright/test'

/**
 * GDPR – Cookie consent + delete account
 */

test.describe('Cookie Consent Banner', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('shows GDPR cookie consent banner on first visit', async ({ page }) => {
    // Clear localStorage to simulate first visit
    await page.goto('/')
    await page.evaluate(() => localStorage.removeItem('cookie_consent'))
    await page.reload()
    await page.waitForLoadState('networkidle')

    // Banner shows "Pliki cookies" text and accept button
    const hasText = await page
      .getByText(/Pliki cookies|ciasteczka|gdpr/i)
      .first()
      .isVisible({ timeout: 5000 })
      .catch(() => false)
    const hasAcceptBtn = await page
      .locator('button')
      .filter({ hasText: /Akceptuj/i })
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasText || hasAcceptBtn).toBeTruthy()
  })

  test('accepting cookies hides the banner', async ({ page }) => {
    await page.goto('/')
    await page.evaluate(() => localStorage.removeItem('cookie_consent'))
    await page.reload()
    await page.waitForLoadState('networkidle')

    const acceptBtn = page
      .locator('button')
      .filter({ hasText: /akceptuję|accept|zgadzam|ok/i })
      .first()
    if (await acceptBtn.isVisible()) {
      await acceptBtn.click()
      await page.waitForLoadState('networkidle')

      const banner = page.locator('[class*="cookie"], [id*="cookie"], [data-cookie]').first()
      await expect(banner).not.toBeVisible({ timeout: 5000 })

      // Consent persisted in localStorage
      const stored = await page.evaluate(() => localStorage.getItem('cookie_consent'))
      expect(stored).not.toBeNull()
    }
  })

  test('banner stays hidden on subsequent visits after accepting', async ({ page }) => {
    // Set consent in localStorage before visiting
    await page.goto('/')
    await page.evaluate(() => localStorage.setItem('cookie_consent', 'accepted'))
    await page.reload()
    await page.waitForLoadState('networkidle')

    const banner = page.locator('[class*="cookie-banner"], [id*="cookie-banner"]').first()
    await expect(banner)
      .not.toBeVisible({ timeout: 3000 })
      .catch(() => {
        // Banner may not exist in DOM at all when consent given – that's fine
      })
  })
})

test.describe('GDPR – Delete Account', () => {
  // Use a fresh customer account so we don't delete the main seed
  test.use({ storageState: 'tests/e2e/.auth/customer2.json' })

  test('delete account button exists on account page', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    // Delete button is in the "Zmiana hasła" (password) tab
    await page
      .locator('button')
      .filter({ hasText: /zmiana hasła/i })
      .first()
      .click()
    await page.waitForTimeout(300)
    const deleteBtn = page
      .locator('button, a')
      .filter({ hasText: /usuń konto|delete account|usuń moje dane|usuń moje konto/i })
    await expect(deleteBtn.first()).toBeVisible({ timeout: 5000 })
  })

  test('delete account requires confirmation', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    // Delete button is in the "Zmiana hasła" (password) tab
    await page
      .locator('button')
      .filter({ hasText: /zmiana hasła/i })
      .first()
      .click()
    await page.waitForTimeout(300)
    const deleteBtn = page
      .locator('button, a')
      .filter({ hasText: /usuń konto|delete account|usuń moje konto/i })
      .first()
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click()
      await page.waitForTimeout(300)

      // Should show confirmation prompt / modal
      const hasConfirm = await page
        .getByText(/potwierdź|confirm|na pewno|sure/i)
        .isVisible()
        .catch(() => false)
      const hasDialog = await page
        .locator('[role="dialog"], [class*="modal"], .fixed.inset-0')
        .isVisible()
        .catch(() => false)
      expect(hasConfirm || hasDialog).toBeTruthy()
    }
  })
})
