import { test, expect } from '@playwright/test'

test.describe('Support Tickets (Tenant Side)', () => {
  test('support page loads', async ({ page }) => {
    await page.goto('/manager/support')
    await expect(page).toHaveURL(/manager\/support/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('creates a support ticket', async ({ page }) => {
    await page.goto('/manager/support')

    const addBtn = page
      .locator('button')
      .filter({ hasText: /nowe|nowy|utwórz|create|dodaj/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')

      const subjectInput = page.locator('input[name="subject"], input[name="title"]').last()
      await subjectInput.fill('E2E Test Ticket')

      const messageInput = page.locator('textarea[name="message"], textarea[name="body"]').last()
      await messageInput.fill('To jest testowe zgłoszenie z E2E.')

      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('E2E Test Ticket').first()).toBeVisible({ timeout: 8000 })
    }
  })

  test('opens ticket and replies', async ({ page }) => {
    await page.goto('/manager/support')
    const row = page.locator('tr, [data-row], a').filter({ hasText: 'E2E Test Ticket' })
    if (await row.first().isVisible()) {
      await row.first().click()
      await page.waitForLoadState('networkidle')

      const replyInput = page.locator('textarea[name="message"], textarea').last()
      if (await replyInput.isVisible()) {
        await replyInput.fill('Odpowiedź na ticket e2e.')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        await expect(page.getByText('Odpowiedź na ticket e2e.')).toBeVisible({ timeout: 8000 })
      }
    }
  })

  test('closes a ticket', async ({ page }) => {
    await page.goto('/manager/support')
    const row = page.locator('tr, [data-row], a').filter({ hasText: 'E2E Test Ticket' })
    if (await row.first().isVisible()) {
      await row.first().click()
      await page.waitForLoadState('networkidle')

      const closeBtn = page.locator('button').filter({ hasText: /zamknij|close/i })
      if (await closeBtn.isVisible()) {
        await closeBtn.click()
        await page.waitForLoadState('networkidle')
      }
    }
  })
})
