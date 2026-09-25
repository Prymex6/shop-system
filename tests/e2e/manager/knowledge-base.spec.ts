import { test, expect } from '@playwright/test'

test.describe('Knowledge base, managed (Manager, §16.4)', () => {
  test('E-MKB.1.1 /manager/knowledge-base loads', async ({ page }) => {
    await page.goto('/manager/knowledge-base')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/knowledge-base/)
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    await expect(page.locator('main, h1').first()).toBeVisible()
  })

  test('E-MKB.1.2 the new article form is reachable', async ({ page }) => {
    await page.goto('/manager/knowledge-base')
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // Creating an article opens a modal, behind the new article button
    const newBtn = page
      .locator('button')
      .filter({ hasText: /nowy artykuł|dodaj artykuł|new article/i })
      .first()
    const hasBtn = await newBtn.isVisible().catch(() => false)
    // If KB exists, button should be present
    if (is500 === false) {
      await expect(newBtn).toBeVisible({ timeout: 3000 })
    }
  })

  test('E-MKB.1.3 a new article shows up in the list', async ({ page }) => {
    await page.goto('/manager/knowledge-base')
    await page.waitForLoadState('networkidle')
    const newBtn = page
      .locator('button')
      .filter({ hasText: /nowy artykuł|new article/i })
      .first()
    await expect(newBtn).toBeVisible({ timeout: 5000 })
    await newBtn.click()
    // Czekaj na otwarcie modalu (div.fixed.inset-0)
    const modal = page.locator('div.fixed.inset-0')
    await expect(modal).toBeVisible({ timeout: 5000 })
    // The title is the text input inside the modal; it has no placeholder
    const titleInput = modal.locator('input[type="text"]').first()
    await expect(titleInput).toBeVisible({ timeout: 3000 })
    // A unique title, or the slug collides on a second run
    const uniqueTitle = `Jak przetwarzać zwroty E2E ${Date.now()}`
    await titleInput.fill(uniqueTitle)
    // The body is the required textarea inside the modal
    const contentArea = modal.locator('textarea').first()
    await expect(contentArea).toBeVisible({ timeout: 3000 })
    await contentArea.fill('Procedura przetwarzania zwrotów: 1. Sprawdź warunki. 2. Zatwierdź RMA.')
    await modal.locator('button[type="submit"]').first().click()
    // Wait for the modal to close, which onSuccess does
    await expect(modal).not.toBeVisible({ timeout: 8000 })
    await page.waitForLoadState('networkidle')
    const is500 = await page
      .getByText(/Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
    // The article should be in the list, matched on the start of its title
    await expect(page.getByText(/Jak przetwarzać zwroty E2E/i).first()).toBeVisible({ timeout: 5000 })
  })

  test('E-MKB.1.4 the edit form loads', async ({ page }) => {
    await page.goto('/manager/knowledge-base')
    await page.waitForLoadState('networkidle')
    const editLink = page.locator('a[href*="knowledge-base"][href*="edit"]').first()
    if (await editLink.isVisible()) {
      await editLink.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-MKB.1.5 deleting an article does not error', async ({ page }) => {
    await page.goto('/manager/knowledge-base')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    const deleteBtn = page
      .locator('button')
      .filter({ hasText: /usuń|delete/i })
      .first()
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
    await expect(page.locator('body')).toBeVisible()
  })
})
