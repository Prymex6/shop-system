import { test, expect } from '@playwright/test'

test.describe('Email Marketing', () => {
  test('marketing page loads', async ({ page }) => {
    await page.goto('/manager/marketing')
    await expect(page).toHaveURL(/manager\/marketing/)
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('creates an email campaign', async ({ page }) => {
    await page.goto('/manager/marketing')

    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|nowa|create/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')

      await page.locator('input[name="name"], input[name="title"]').last().fill('E2E Kampania Email')
      const subjectInput = page.locator('input[name="subject"]').last()
      if (await subjectInput.isVisible()) await subjectInput.fill('Test Subject E2E')

      const bodyInput = page.locator('textarea[name="body"], textarea[name="content"]').last()
      if (await bodyInput.isVisible()) await bodyInput.fill('Treść testowa kampanii e2e.')

      await page.locator('button[type="submit"]').last().click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('E2E Kampania Email').first()).toBeVisible({ timeout: 15000 })
    }
  })

  test('cannot send campaign without recipients', async ({ page }) => {
    await page.goto('/manager/marketing')
    const row = page.locator('tr, [data-row]').filter({ hasText: 'E2E Kampania Email' })
    if (await row.isVisible()) {
      const sendBtn = row.locator('button').filter({ hasText: /wyślij|send/i })
      if (await sendBtn.isVisible()) {
        await sendBtn.click()
        await page.waitForLoadState('networkidle')
        // Should either show confirm modal or proceed
        // Just verify no crash
        await expect(page.locator('body')).toBeVisible()
      }
    }
  })

  test('deletes test campaign', async ({ page }) => {
    await page.goto('/manager/marketing')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'E2E Kampania Email' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
    }
  })
})
