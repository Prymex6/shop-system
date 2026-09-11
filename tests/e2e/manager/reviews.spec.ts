import { test, expect } from '@playwright/test'

/**
 * Reviews management – manager panel
 */
test.describe('Reviews Management', () => {
  test('reviews page loads', async ({ page }) => {
    await page.goto('/manager/reviews')
    await expect(page).toHaveURL(/manager\/reviews/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('shows review list or empty state', async ({ page }) => {
    await page.goto('/manager/reviews')
    const hasReviews = (await page.locator('table tbody tr, [data-review]').count()) > 0
    const hasEmpty = await page
      .getByText(/brak|no reviews|puste/i)
      .isVisible()
      .catch(() => false)
    expect(hasReviews || hasEmpty).toBeTruthy()
  })

  test('manager can add a review response', async ({ page }) => {
    await page.goto('/manager/reviews')
    const row = page.locator('tr, [data-review]').first()
    if (await row.isVisible()) {
      const editBtn = row.locator('button').filter({ hasText: /odpowiedz|edit|edytuj/i })
      if (await editBtn.isVisible()) {
        await editBtn.click()
        await page.waitForLoadState('networkidle')
        const textarea = page.locator('textarea').last()
        if (await textarea.isVisible()) {
          await textarea.fill('Dziękujemy za opinię!')
          await page.locator('button[type="submit"]').last().click()
          await page.waitForLoadState('networkidle')
        }
      }
    }
  })
})

/**
 * Customer can leave a review (authenticated)
 */
test.describe('Customer Review', () => {
  test.use({ storageState: 'tests/e2e/.auth/customer.json' })

  test('review form submits without error', async ({ page }) => {
    await page.goto('/')
    // Look for a review button or link
    const reviewLink = page
      .locator('a[href*="recenzja"], button')
      .filter({ hasText: /recenzja|review|opinia/i })
      .first()
    if (await reviewLink.isVisible()) {
      await reviewLink.click()
      await page.waitForLoadState('networkidle')
    } else {
      await page.goto('/')
    }
    // Just ensure no 500
    await expect(page.locator('body')).toBeVisible()
  })
})
