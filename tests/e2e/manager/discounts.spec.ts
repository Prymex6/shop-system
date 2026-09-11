import { test, expect } from '@playwright/test'

test.describe('Discount Codes', () => {
  test('lists discount codes', async ({ page }) => {
    await page.goto('/manager/discounts')
    await expect(page).toHaveURL(/discounts/)
    await expect(page.getByText('WELCOME10')).toBeVisible()
    await expect(page.getByText('LATO20')).toBeVisible()
  })

  test('creates a percentage discount code', async ({ page }) => {
    await page.goto('/manager/discounts')

    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add|nowy/i })
      .first()
    await addBtn.click()
    await page.waitForLoadState('networkidle')

    await page.locator('input[name="code"]').last().fill('E2ETEST15')

    const typeSelect = page.locator('select[name="type"]')
    if (await typeSelect.isVisible()) {
      await typeSelect.selectOption('percentage')
    }

    await page.locator('input[name="value"]').last().fill('15')

    const minOrder = page.locator('input[name="min_order_value"]').last()
    if (await minOrder.isVisible()) await minOrder.fill('40')

    await page.locator('button[type="submit"]').last().click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('E2ETEST15')).toBeVisible({ timeout: 8000 })
  })

  test('creates a fixed discount code', async ({ page }) => {
    await page.goto('/manager/discounts')

    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add|nowy/i })
      .first()
    await addBtn.click()
    await page.waitForLoadState('networkidle')

    await page.locator('input[name="code"]').last().fill('E2EFIXED5')

    const typeSelect = page.locator('select[name="type"]')
    if (await typeSelect.isVisible()) {
      await typeSelect.selectOption('fixed')
    }

    await page.locator('input[name="value"]').last().fill('5')
    await page.locator('button[type="submit"]').last().click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('E2EFIXED5')).toBeVisible({ timeout: 8000 })
  })

  test('toggles discount code active state', async ({ page }) => {
    await page.goto('/manager/discounts')
    const row = page.locator('tr, [data-row]').filter({ hasText: 'E2ETEST15' })
    if (await row.isVisible()) {
      const toggleBtn = row.locator('button').filter({ hasText: /aktywuj|dezaktywuj|toggle|włącz|wyłącz/i })
      if (await toggleBtn.isVisible()) {
        await toggleBtn.click()
        await page.waitForLoadState('networkidle')
      }
    }
  })

  test('deletes E2E test discount codes', async ({ page }) => {
    await page.goto('/manager/discounts')
    page.on('dialog', (d) => d.accept())

    for (const code of ['E2ETEST15', 'E2EFIXED5']) {
      const row = page.locator('tr, [data-row]').filter({ hasText: code })
      if (await row.isVisible()) {
        await row
          .locator('button')
          .filter({ hasText: /usuń|delete/i })
          .click()
        await page.waitForLoadState('networkidle')
      }
    }
  })
})
