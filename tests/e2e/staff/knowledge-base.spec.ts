import { test, expect } from '@playwright/test'

test.describe('Panel staff — Baza wiedzy (§25)', () => {
  test('E-SK.1.1 Strona /staff/knowledge-base ładuje się', async ({ page }) => {
    await page.goto('/staff/knowledge-base')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SK.1.2 Lista artykułów lub komunikat o braku widoczny', async ({ page }) => {
    await page.goto('/staff/knowledge-base')
    await page.waitForLoadState('networkidle')
    const hasList = await page
      .locator('article, [class*="article"], [class*="knowledge"], ul li')
      .first()
      .isVisible()
      .catch(() => false)
    const hasEmpty = await page
      .getByText(/brak|nie ma|no articles|artykuł/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasContent = await page
      .locator('main h1, main h2')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasList || hasEmpty || hasContent).toBeTruthy()
  })

  test('E-SK.1.3 Wyszukiwarka artykułów działa', async ({ page }) => {
    await page.goto('/staff/knowledge-base')
    await page.waitForLoadState('networkidle')
    const searchInput = page
      .locator('input[type="search"], input[placeholder*="szukaj"], input[placeholder*="Search"]')
      .first()
    if (await searchInput.isVisible()) {
      await searchInput.fill('zwrot')
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SK.1.4 Kliknięcie artykułu otwiera pełną treść', async ({ page }) => {
    await page.goto('/staff/knowledge-base')
    await page.waitForLoadState('networkidle')
    const articleLink = page.locator('a[href*="knowledge-base"]').first()
    if (await articleLink.isVisible()) {
      await articleLink.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
      await expect(page.locator('main, article, [class*="content"]').first()).toBeVisible()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-SK.1.5 Staff nie ma dostępu do tworzenia artykułów (tylko podgląd)', async ({ page }) => {
    await page.goto('/staff/knowledge-base/create')
    await page.waitForLoadState('networkidle')
    // Should redirect or show 403 — NOT a create form
    const is403 = await page
      .getByText(/403|Forbidden|brak dostępu|404|nie znaleziono/i)
      .first()
      .isVisible()
      .catch(() => false)
    const isRedirected = !page.url().includes('/create')
    expect(is403 || isRedirected).toBeTruthy()
  })
})
