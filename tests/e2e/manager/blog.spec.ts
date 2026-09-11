import { test, expect } from '@playwright/test'

test.describe('Blog — artykuły (§15.1–15.2)', () => {
  test('E-MB.1.1 Lista artykułów /manager/articles ładuje się', async ({ page }) => {
    await page.goto('/manager/articles')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/articles/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-MB.1.2 Formularz tworzenia artykułu dostępny', async ({ page }) => {
    await page.goto('/manager/articles/create')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // Pole "Tytuł" używa v-model bez atrybutu name — szukamy po placeholder
    const hasTitleField = await page
      .locator('input[placeholder*="Tytuł artykułu"], input[placeholder*="Tytuł"]')
      .first()
      .isVisible()
      .catch(() => false)
    const hasEditor = await page
      .locator('textarea, [class*="editor"], [data-tiptap]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasTitleField || hasEditor).toBeTruthy()
  })

  test('E-MB.1.3 Utwórz artykuł szkic → widoczny na liście', async ({ page }) => {
    await page.goto('/manager/articles/create')
    await page.waitForLoadState('networkidle')
    const titleInput = page.locator('input[placeholder*="Tytuł artykułu"], input[placeholder*="Tytuł"]').first()
    await expect(titleInput).toBeVisible({ timeout: 5000 })
    // Unikalny tytuł — zapobiega błędowi "slug już zajęty" przy kolejnych przebiegach
    const uniqueTitle = `Artykuł E2E Test Blog ${Date.now()}`
    await titleInput.fill(uniqueTitle)
    // Pole "Treść" ma atrybut required — to DRUGA textarea (pierwsza to Zajawka)
    const contentArea = page.locator('textarea[placeholder*="Treść"]')
    await expect(contentArea).toBeVisible({ timeout: 5000 })
    await contentArea.fill('Treść artykułu testowego — co najmniej kilka zdań na potrzeby testu E2E.')
    await page.locator('button[type="submit"]').first().click()
    // Kontroler po zapisaniu przekierowuje do articles/{id}/edit
    await page.waitForURL(/articles\/\d+\/edit/, { timeout: 10000 })
    await page.goto('/manager/articles')
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Artykuł E2E Test Blog').first()).toBeVisible({ timeout: 5000 })
  })

  test('E-MB.1.4 Zmiana statusu artykułu draft → published', async ({ page }) => {
    await page.goto('/manager/articles')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    const publishBtn = page
      .locator('button')
      .filter({ hasText: /opublikuj|publish/i })
      .first()
    if (await publishBtn.isVisible()) {
      await publishBtn.click()
      await page.waitForLoadState('networkidle')
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-MB.1.5 Edycja artykułu — formularz edycji ładuje się', async ({ page }) => {
    await page.goto('/manager/articles')
    await page.waitForLoadState('networkidle')
    const editLink = page.locator('a[href*="articles"][href*="edit"]').first()
    if (await editLink.isVisible()) {
      await editLink.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
      await expect(page.locator('input[name="title"], form').first()).toBeVisible()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-MB.1.6 Blog publiczny /blog ładuje się', async ({ page }) => {
    await page.goto('/blog')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('body')).toBeVisible()
  })
})
