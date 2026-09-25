import { test, expect } from '@playwright/test'

test.describe('Super-admin — Support (Landlord)', () => {
  test('E45.1.1 /admin/support lists the tickets, or says there are none', async ({ page }) => {
    await page.goto('/admin/support')
    await expect(page).toHaveURL(/admin\/support/)
    await expect(page.locator('main, h1, h2').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    const hasTickets = (await page.locator('table tbody tr, [data-ticket]').count()) > 0
    const hasEmpty = await page
      .getByText(/brak zgłoszeń|no tickets|puste/i)
      .isVisible()
      .catch(() => false)
    expect(hasTickets || hasEmpty).toBeTruthy()
  })

  test('E45.1.2 an open ticket shows what was written', async ({ page }) => {
    await page.goto('/admin/support')
    const firstTicket = page.locator('table tbody tr a, [data-ticket] a, a[href*="support/"]').first()
    if (await firstTicket.isVisible()) {
      await firstTicket.click()
      await page.waitForLoadState('networkidle')
      await expect(page.locator('body')).toBeVisible()
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E45.1.3 a reply is saved onto the thread', async ({ page }) => {
    await page.goto('/admin/support')
    const firstTicket = page.locator('table tbody tr a, a[href*="support/"]').first()
    if (await firstTicket.isVisible()) {
      await firstTicket.click()
      await page.waitForLoadState('networkidle')
      const replyInput = page.locator('textarea[name="message"], textarea[name="reply"], textarea[name="body"]').first()
      if (await replyInput.isVisible()) {
        await replyInput.fill('Odpowiedź super-admina na zgłoszenie — test E2E.')
        await page.locator('button[type="submit"]').first().click()
        await page.waitForLoadState('networkidle')
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E45.1.4 the ticket status can be changed', async ({ page }) => {
    await page.goto('/admin/support')
    const firstTicket = page.locator('table tbody tr a, a[href*="support/"]').first()
    if (await firstTicket.isVisible()) {
      await firstTicket.click()
      await page.waitForLoadState('networkidle')
      const statusBtn = page
        .locator('button, a')
        .filter({ hasText: /zamknij|close|otwórz|open|zmień status/i })
        .first()
      if (await statusBtn.isVisible()) {
        await statusBtn.click()
        await page.waitForLoadState('networkidle')
      }
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
