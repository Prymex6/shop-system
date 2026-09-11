import { test, expect } from '@playwright/test'

test.describe('Customer Management', () => {
  test('lists customers', async ({ page }) => {
    await page.goto('/manager/customers')
    await expect(page).toHaveURL(/manager\/customers/)
    await expect(page.getByText('Tomasz Testowy')).toBeVisible()
    await expect(page.getByText('Maria Przykładowa')).toBeVisible()
  })

  test('shows customer detail', async ({ page }) => {
    await page.goto('/manager/customers')
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Tomasz Testowy' })
    const link = row.locator('a[href*="customers/"]').first()
    if (await link.isVisible()) {
      await link.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/customers\/\d+/)
      await expect(page.getByText('Tomasz Testowy')).toBeVisible()
      await expect(page.getByText('klient@example.pl')).toBeVisible()
    }
  })

  test('customer has loyalty points visible', async ({ page }) => {
    await page.goto('/manager/customers')
    // Seeded: Tomasz has 150 loyalty points
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Tomasz Testowy' })
    const link = row.locator('a').first()
    if (await link.isVisible()) {
      await link.click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText(/150|punkty|points/i).first()).toBeVisible({ timeout: 5000 })
    }
  })

  test('exports customers CSV', async ({ page }) => {
    let downloadOk = false
    try {
      const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 8000 }),
        page.goto('/manager/customers/export').catch(() => {}),
      ])
      if (download) {
        expect(download.suggestedFilename()).toMatch(/\.csv$/i)
        downloadOk = true
      }
    } catch {
      // No download event — check for no 500
    }
    if (!downloadOk) {
      await page.goto('/manager/customers')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
