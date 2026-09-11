import { test, expect } from '@playwright/test'

test.describe('Kategorie produktów', () => {
  test('E-KAT.1.1 Lista kategorii widoczna', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-KAT.1.2 Dodaj kategorię „Elektronika E2E" → Widoczna na liście', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    // Formularz kategorii jest w modalu — kliknij "+ Nowa kategoria"
    const addBtn = page
      .locator('a, button')
      .filter({ hasText: /nowa kategoria|dodaj kategorię|add category/i })
      .first()
    await expect(addBtn).toBeVisible({ timeout: 5000 })
    await addBtn.click()
    // Modal kategorii to div.fixed.inset-0 (bez role="dialog")
    // Pole "Nazwa" ma atrybut required — to odróżnia je od innych inputów
    const nameInput = page.locator('.fixed.inset-0 input[required], .fixed input[required]').first()
    await expect(nameInput).toBeVisible({ timeout: 5000 })
    await nameInput.fill('Elektronika E2E')
    // Klikamy submit wewnątrz modalu (div.fixed zawiera formularz)
    await page.locator('.fixed form button[type="submit"]').first().click()
    // Czekamy na zamknięcie modalu (sign że zapis się powiódł)
    await expect(page.locator('.fixed.inset-0').first()).not.toBeVisible({ timeout: 8000 })
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Elektronika E2E').first()).toBeVisible({ timeout: 5000 })
  })

  test('E-KAT.1.3 Usuń kategorię → Znika z listy', async ({ page }) => {
    await page.goto('/manager/categories')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    // Kategorie są w div.flex.items-center (nie tr) — szukamy wiersza z dokładnym tekstem
    const row = page.locator('div.flex.items-center').filter({ hasText: 'Elektronika E2E' }).first()
    await expect(row).toBeVisible({ timeout: 5000 })
    await row.getByRole('button', { name: 'Usuń' }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Elektronika E2E')).not.toBeVisible({ timeout: 5000 })
  })
})
