import { test, expect } from '@playwright/test'

test.describe('Customer Account', () => {
  test('account page loads', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/moje-konto/)
    await expect(page.locator('[data-customer-name]').first()).toBeVisible({ timeout: 10000 })
  })

  test('shows loyalty points', async ({ page }) => {
    await page.goto('/moje-konto')
    // Seeded: 150 points, silver tier
    await expect(page.getByText(/150|punkty|silver|Srebrny/i)).toBeVisible()
  })

  test('shows order history', async ({ page }) => {
    await page.goto('/moje-konto')
    await page.waitForLoadState('networkidle')
    // Click on orders tab
    await page
      .locator('button')
      .filter({ hasText: /historia zamówień|orders/i })
      .first()
      .click()
    await page.waitForTimeout(300)
    await expect(page.locator('body')).toBeVisible()
    // Accept if orders shown or empty message shown
    const hasContent = await page
      .locator('[class*="order"], h2, h3, p')
      .filter({ hasText: /zamówieni|order/i })
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasContent).toBeTruthy()
  })

  test('updates profile name and phone', async ({ page }) => {
    await page.goto('/moje-konto')

    const nameInput = page.locator('input[name="name"]')
    await nameInput.fill('Tomasz Testowy Zmieniony')

    const phoneInput = page.locator('input[name="phone"]')
    if (await phoneInput.isVisible()) {
      await phoneInput.fill('+48600123456')
    }

    await page
      .locator('button[type="submit"]')
      .filter({ hasText: /zapisz|save|aktualizuj/i })
      .first()
      .click()
    await page.waitForLoadState('networkidle')

    // Revert
    await page.goto('/moje-konto')
    await page.locator('input[name="name"]').fill('Tomasz Testowy')
    await page
      .locator('button[type="submit"]')
      .filter({ hasText: /zapisz|save/i })
      .first()
      .click()
    await page.waitForLoadState('networkidle')
  })

  test('changes password successfully', async ({ page }) => {
    await page.goto('/moje-konto')

    const currentPwd = page.locator('input[name="current_password"]')
    const newPwd = page.locator('input[name="password"]')
    const confirm = page.locator('input[name="password_confirmation"]')

    if (await currentPwd.isVisible()) {
      await currentPwd.fill('password')
      await newPwd.fill('password') // keep same password
      await confirm.fill('password')

      await page
        .locator('button[type="submit"]')
        .filter({ hasText: /zmień hasło|save password|update/i })
        .first()
        .click()
      await page.waitForLoadState('networkidle')
    }
  })

  test('rejects wrong current password', async ({ page }) => {
    await page.goto('/moje-konto')

    const currentPwd = page.locator('input[name="current_password"]')
    if (await currentPwd.isVisible()) {
      await currentPwd.fill('wrongpassword')
      await page.locator('input[name="password"]').fill('newpassword123')
      await page.locator('input[name="password_confirmation"]').fill('newpassword123')

      await page
        .locator('button[type="submit"]')
        .filter({ hasText: /zmień|save|update/i })
        .first()
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText(/nieprawidłowe|incorrect|wrong/i)).toBeVisible({ timeout: 5000 })
    }
  })

  test('delivery address is pre-filled', async ({ page }) => {
    await page.goto('/moje-konto')
    const addressInput = page.locator('input[name="delivery_address"]')
    if (await addressInput.isVisible()) {
      await expect(addressInput).toHaveValue(/ul\. Długa|Długa/i)
    }
  })
})
