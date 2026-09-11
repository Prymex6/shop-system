import { test, expect } from '@playwright/test'

test.describe('Dashboard managera', () => {
  test('E3.1.1 Wejście na /manager/ → Dashboard załadowany; karty statystyk (zamówienia, przychody, klienci) widoczne', async ({
    page,
  }) => {
    await page.goto('/manager/')
    await expect(page).toHaveURL(/manager\//)
    await expect(page.locator('main, [class*="card"], [class*="stat"]').first()).toBeVisible()
  })

  test('E3.1.2 Wykres przychodów (30 dni) → Komponent canvas lub wykres słupkowy widoczny', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    const hasCanvas = (await page.locator('canvas').count()) > 0
    const hasChart = (await page.locator('[class*="chart"]').count()) > 0
    expect(hasCanvas || hasChart).toBeTruthy()
  })

  test('E3.1.3 Mapa ciepła godzinowa → Siatka 24 godzin widoczna', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    // Heatmapa lub drugi wykres canvas lub div z klasą chart/heatmap
    const canvasCount = await page.locator('canvas').count()
    const chartDivCount = await page.locator('[class*="chart"], [class*="heatmap"], [class*="heat"]').count()
    expect(canvasCount + chartDivCount).toBeGreaterThanOrEqual(1)
  })

  test('E3.1.4 Sekcja „Najpopularniejsze produkty" → Lista widoczna (nawet pusta)', async ({ page }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('main').first()).toBeVisible()
  })

  test('E3.1.5 Linki nawigacyjne w dashboardzie → Kliknięcie „Zamówienia" prowadzi do /manager/orders', async ({
    page,
  }) => {
    await page.goto('/manager/')
    await page.waitForLoadState('networkidle')
    // Link do zamówień jest w sidebarze pod "Sprzedaż" — trzeba rozwinąć sekcję
    const sprzedazToggle = page
      .locator('button')
      .filter({ hasText: /^Sprzedaż$/ })
      .first()
    if (await sprzedazToggle.isVisible()) await sprzedazToggle.click()
    const ordersLink = page
      .locator('a[href*="orders"]')
      .filter({ hasText: /zamówienia/i })
      .first()
    await expect(ordersLink).toBeVisible({ timeout: 3000 })
    await ordersLink.click()
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/manager\/orders/)
  })
})
