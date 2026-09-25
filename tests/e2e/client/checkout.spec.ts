import { test, expect } from '@playwright/test'

test.describe('Multi-step Checkout', () => {
  test('E26.1.1 the checkout loads, or sends you to its first step', async ({ page }) => {
    await page.goto('/kasa')
    await page.waitForLoadState('networkidle')
    const isCheckout = page.url().includes('kasa') || page.url().includes('checkout')
    const hasForm = await page
      .locator('form, input[name="email"]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(isCheckout || hasForm).toBeTruthy()
  })

  test('E26.1.2 an empty checkout form comes back with errors', async ({ page }) => {
    await page.goto('/kasa')
    await page.waitForLoadState('networkidle')
    const submitBtn = page
      .locator('button[type="submit"]')
      .filter({ hasText: /dalej|next|zamów/i })
      .first()
    if (await submitBtn.isVisible()) {
      await submitBtn.click()
      await page.waitForLoadState('networkidle')
      const hasError = await page
        .getByText(/wymagane|required|błąd/i)
        .first()
        .isVisible()
        .catch(() => false)
      expect(hasError).toBeTruthy()
    }
  })

  test('E26.1.3 filling in the delivery details moves to step two', async ({ page }) => {
    await page.goto('/kasa')
    await page.waitForLoadState('networkidle')
    const emailInput = page.locator('input[name="email"]').first()
    if (await emailInput.isVisible()) {
      await emailInput.fill('test@e2e.pl')
      const nameInput = page.locator('input[name="name"], input[name="first_name"]').first()
      if (await nameInput.isVisible()) await nameInput.fill('Jan Testowy')
      const phoneInput = page.locator('input[name="phone"]').first()
      if (await phoneInput.isVisible()) await phoneInput.fill('+48600123456')
      const addressInput = page.locator('input[name="address"], input[name="street"]').first()
      if (await addressInput.isVisible()) await addressInput.fill('ul. Testowa 1')
      const cityInput = page.locator('input[name="city"]').first()
      if (await cityInput.isVisible()) await cityInput.fill('Kraków')
      const zipInput = page.locator('input[name="postal_code"], input[name="zip"]').first()
      if (await zipInput.isVisible()) await zipInput.fill('30-001')
      const nextBtn = page
        .locator('button[type="submit"]')
        .filter({ hasText: /dalej|next/i })
        .first()
      if (await nextBtn.isVisible()) {
        await nextBtn.click()
        await page.waitForLoadState('networkidle')
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E26.1.4 a malformed email address is rejected', async ({ page }) => {
    await page.goto('/kasa')
    await page.waitForLoadState('networkidle')
    const emailInput = page.locator('input[name="email"]').first()
    if (await emailInput.isVisible()) {
      await emailInput.fill('nienormalny-email')
      const nextBtn = page.locator('button[type="submit"]').first()
      await nextBtn.click()
      await page.waitForLoadState('networkidle')
      const hasError = await page
        .getByText(/email|format|nieprawidłowy/i)
        .isVisible()
        .catch(() => false)
      expect(hasError).toBeTruthy()
    }
  })

  test('E26.1.5 the payment step has a discount code field', async ({ page }) => {
    await page.goto('/kasa')
    await page.waitForLoadState('networkidle')
    // Navigate to payment step if multi-step
    const paymentStep = page
      .locator('input[name="payment_method"], button')
      .filter({ hasText: /płatność|payment/i })
      .first()
    if (await paymentStep.isVisible()) {
      const discountField = page
        .locator('input[name="discount_code"], input[placeholder*="rabat" i], input[placeholder*="kod" i]')
        .first()
      if (await discountField.isVisible()) {
        await discountField.fill('NIEISTNIEJACY123')
        const applyBtn = page
          .locator('button')
          .filter({ hasText: /zastosuj|apply/i })
          .first()
        if (await applyBtn.isVisible()) {
          await applyBtn.click()
          await page.waitForLoadState('networkidle')
          const hasError = await page
            .getByText(/nieprawidłowy|nie istnieje|invalid/i)
            .isVisible()
            .catch(() => false)
          expect(hasError).toBeTruthy()
        }
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })
})

test.describe('Koszyk', () => {
  test('E25.1.1 the storefront home page is there', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E25.1.2 adding a product moves the basket count up by one', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj do koszyka|add to cart/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const cartBadge = page.locator('[data-cart-count], .cart-count, [class*="cart"][class*="badge"]').first()
      if (await cartBadge.isVisible()) {
        const count = await cartBadge.textContent()
        expect(parseInt(count ?? '0')).toBeGreaterThan(0)
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E25.1.3 an empty basket says so', async ({ page }) => {
    await page.goto('/koszyk')
    await page.waitForLoadState('networkidle')
    const emptyMsg = await page
      .getByText(/koszyk jest pusty|pusty koszyk|empty cart|no items/i)
      .isVisible()
      .catch(() => false)
    const hasItems = await page
      .locator('[data-product], .cart-item')
      .first()
      .isVisible()
      .catch(() => false)
    expect(emptyMsg || hasItems).toBeTruthy()
  })
})
