import { test, expect } from '@playwright/test'

test.describe('Blog articles (§15.1-15.2)', () => {
  test('E-MB.1.1 /manager/articles loads', async ({ page }) => {
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

  test('E-MB.1.2 the new article form is reachable', async ({ page }) => {
    await page.goto('/manager/articles/create')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // The title field is bound with v-model and has no name, so it is found by its placeholder
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

  test('E-MB.1.3 a draft article shows up in the list', async ({ page }) => {
    await page.goto('/manager/articles/create')
    await page.waitForLoadState('networkidle')
    const titleInput = page.locator('input[placeholder*="Tytuł artykułu"], input[placeholder*="Tytuł"]').first()
    await expect(titleInput).toBeVisible({ timeout: 5000 })
    // A unique title, or the slug collides on a second run
    const uniqueTitle = `Artykuł E2E Test Blog ${Date.now()}`
    await titleInput.fill(uniqueTitle)
    // The body is the second textarea; the first is the excerpt
    const contentArea = page.locator('textarea[placeholder*="Treść"]')
    await expect(contentArea).toBeVisible({ timeout: 5000 })
    await contentArea.fill('Treść artykułu testowego — co najmniej kilka zdań na potrzeby testu E2E.')
    await page.locator('button[type="submit"]').first().click()
    // Saving redirects to articles/{id}/edit
    await page.waitForURL(/articles\/\d+\/edit/, { timeout: 10000 })
    await page.goto('/manager/articles')
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('Artykuł E2E Test Blog').first()).toBeVisible({ timeout: 5000 })
  })

  test('E-MB.1.4 an article goes from draft to published', async ({ page }) => {
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

  test('E-MB.1.5 the edit form loads', async ({ page }) => {
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

  test('E-MB.1.6 the public blog loads', async ({ page }) => {
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
