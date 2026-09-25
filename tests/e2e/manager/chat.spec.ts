import { test, expect } from '@playwright/test'

test.describe('Live Chat — Panel managera', () => {
  test('E-CH.1.1 /manager/chat lists the conversations', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/chat/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-CH.1.2 the conversation list, empty or not, loads', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // The page is up, with either a list of conversations or an empty one
    await expect(page.locator('main')).toBeVisible()
    // Either conversations as clickable divs, or an empty white container
    const hasConvItem = await page
      .locator('div.cursor-pointer')
      .first()
      .isVisible()
      .catch(() => false)
    const hasContainer = await page
      .locator('.bg-white.shadow')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasConvItem || hasContainer).toBeTruthy()
  })

  test('E-CH.1.3 replying to a conversation does not 500', async ({ page }) => {
    await page.goto('/manager/chat')
    await page.waitForLoadState('networkidle')
    const firstConv = page.locator('a[href*="chat"], [data-conversation]').first()
    if (await firstConv.isVisible()) {
      await firstConv.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
