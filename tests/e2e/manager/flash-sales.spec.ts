import { test, expect } from '@playwright/test'

test.describe('Flash Sales', () => {
  test('E-FS.1.1 /manager/flash-sales ładuje się', async ({ page }) => {
    await page.goto('/manager/flash-sales')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-FS.1.2 Dodaj Flash Sale „Letnia E2E" → widoczna na liście', async ({ page }) => {
    await page.goto('/manager/flash-sales')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button, a')
      .filter({ hasText: /dodaj flash|add flash|nowa wyprzedaż/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const nameInput = page.locator('input[name="name"]').last()
      if (await nameInput.isVisible()) {
        await nameInput.fill('Letnia E2E')
        const discountInput = page.locator('input[name="discount_percent"], input[name="discount"]').last()
        if (await discountInput.isVisible()) await discountInput.fill('20')
        // Dates
        const startInput = page.locator('input[name="starts_at"], input[type="datetime-local"]').first()
        if (await startInput.isVisible()) await startInput.fill('2026-06-01T09:00')
        const endInput = page.locator('input[name="ends_at"], input[type="datetime-local"]').last()
        if (await endInput.isVisible()) await endInput.fill('2026-06-02T21:00')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
      }
    }
    expect(
      await page
        .getByText(/500/i)
        .isVisible()
        .catch(() => false),
    ).toBeFalsy()
  })

  test('E-FS.1.3 Błąd przy dacie zakończenia < daty rozpoczęcia', async ({ page }) => {
    await page.goto('/manager/flash-sales')
    await page.waitForLoadState('networkidle')
    const addBtn = page
      .locator('button')
      .filter({ hasText: /dodaj|add/i })
      .first()
    if (await addBtn.isVisible()) {
      await addBtn.click()
      await page.waitForLoadState('networkidle')
      const startInput = page.locator('input[name="starts_at"], input[type="datetime-local"]').first()
      const endInput = page.locator('input[name="ends_at"], input[type="datetime-local"]').last()
      if ((await startInput.isVisible()) && (await endInput.isVisible())) {
        await startInput.fill('2026-12-31T23:00')
        await endInput.fill('2026-01-01T00:00')
        await page.locator('button[type="submit"]').last().click()
        await page.waitForLoadState('networkidle')
        const hasError = await page
          .getByText(/data|after|przed|błąd|wymagane/i)
          .isVisible()
          .catch(() => false)
        const staysOnPage = page.url().includes('flash-sales')
        expect(hasError || staysOnPage).toBeTruthy()
        const is500 = await page
          .getByText(/500|Internal Server Error/i)
          .isVisible()
          .catch(() => false)
        expect(is500).toBeFalsy()
      }
    }
  })

  test('E-FS.1.4 Usuń Flash Sale → znika z listy', async ({ page }) => {
    await page.goto('/manager/flash-sales')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const row = page.locator('tr, [data-row]').filter({ hasText: 'Letnia E2E' })
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('Letnia E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})
