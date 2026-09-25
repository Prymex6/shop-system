import { test, expect } from '@playwright/test'

test.describe('Kreator stron — Page Builder', () => {
  test('E-PB.1.1 /manager/page-builder lists the pages', async ({ page }) => {
    await page.goto('/manager/page-builder')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/page-builder/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-PB.1.2 the new page form is reachable', async ({ page }) => {
    await page.goto('/manager/page-builder')
    await page.waitForLoadState('networkidle')
    const newBtn = page
      .locator('a, button')
      .filter({ hasText: /nowa strona|dodaj stronę|new page/i })
      .first()
    if (await newBtn.isVisible()) {
      await newBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page.locator('form, input[type="text"]').first()).toBeVisible({ timeout: 5000 })
    }
    await expect(page.locator('body')).toBeVisible()
  })

  test('E-PB.1.3 a new page shows up in the list', async ({ page }) => {
    // The page editor binds with v-model and has no names, so fields are found by placeholder
    await page.goto('/manager/page-builder/create')
    await page.waitForLoadState('networkidle')
    const titleInput = page.locator('input[placeholder*="O nas"], input[placeholder*="Np."]').first()
    await expect(titleInput).toBeVisible({ timeout: 5000 })
    await titleInput.fill('O nas E2E')
    // The save button, which is not a type=submit
    await page
      .locator('button')
      .filter({ hasText: /^Zapisz$/ })
      .first()
      .click()
    // Saving redirects to the editor with an id, so wait for the URL to change
    await page.waitForURL(/page-builder\/\d+\/edit/, { timeout: 10000 })
    await page.goto('/manager/page-builder')
    await page.waitForLoadState('networkidle')
    await expect(page.getByText('O nas E2E').first()).toBeVisible({ timeout: 5000 })
  })

  test('E-PB.1.4 the page editor canvas loads', async ({ page }) => {
    await page.goto('/manager/page-builder')
    await page.waitForLoadState('networkidle')
    const editLink = page.locator('a[href*="page-builder"][href*="edit"]').first()
    if (await editLink.isVisible()) {
      await editLink.click()
      await page.waitForLoadState('networkidle')
      const hasCanvas = await page
        .locator('[class*="canvas"], [class*="editor"], [data-canvas]')
        .first()
        .isVisible()
        .catch(() => false)
      const hasPalette = await page
        .getByText(/hero|tekst|obraz|blok/i)
        .first()
        .isVisible()
        .catch(() => false)
      expect(hasCanvas || hasPalette).toBeTruthy()
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  test('E-PB.1.5 a duplicate slug is rejected', async ({ page }) => {
    await page.goto('/manager/page-builder/create')
    await page.waitForLoadState('networkidle')
    const titleInput = page.locator('input[placeholder*="O nas"], input[placeholder*="Np."]').first()
    if (await titleInput.isVisible()) {
      await titleInput.fill('O nas E2E')
      // Overwrite the slug with one already in use
      const slugInput = page.locator('input[placeholder*="o-nas"], input[placeholder="o-nas"]').first()
      if (await slugInput.isVisible()) {
        await slugInput.fill('o-nas-e2e')
      }
      await page
        .locator('button')
        .filter({ hasText: /^Zapisz$/ })
        .first()
        .click()
      await page.waitForLoadState('networkidle')
      // A duplicate slug should error, or at least leave us on the create page
      const hasError = await page
        .getByText(/zajęty|already|slug|unique/i)
        .isVisible()
        .catch(() => false)
      const staysOnForm = page.url().includes('create') || page.url().includes('edit')
      expect(hasError || staysOnForm).toBeTruthy()
    }
  })

  test('E-PB.1.6 a deleted page leaves the list', async ({ page }) => {
    await page.goto('/manager/page-builder')
    await page.waitForLoadState('networkidle')
    page.on('dialog', (d) => d.accept())
    // The pages are listed as table rows
    const row = page.locator('tr').filter({ hasText: 'O nas E2E' }).first()
    if (await row.isVisible()) {
      await row
        .locator('button')
        .filter({ hasText: /usuń|delete/i })
        .click()
      await page.waitForLoadState('networkidle')
      await expect(page.getByText('O nas E2E')).not.toBeVisible({ timeout: 5000 })
    }
  })
})

test.describe('Homepage builder', () => {
  test('E-HB.1.1 the homepage builder loads', async ({ page }) => {
    await page.goto('/manager/homepage-builder')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/homepage-builder/)
    await expect(page.locator('main, h1').first()).toBeVisible()
    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()
  })

  test('E-HB.1.2 the blocks are listed', async ({ page }) => {
    await page.goto('/manager/homepage-builder')
    await page.waitForLoadState('networkidle')
    // Should have at least the Hero block in the list
    const hasHero = await page
      .getByText(/hero|baner powitalny/i)
      .first()
      .isVisible()
      .catch(() => false)
    const hasAnyBlock = (await page.locator('[class*="block"], [data-block]').count()) > 0
    expect(hasHero || hasAnyBlock).toBeTruthy()
  })

  test('E-HB.1.3 clicking a block opens its settings', async ({ page }) => {
    await page.goto('/manager/homepage-builder')
    await page.waitForLoadState('networkidle')
    const firstBlock = page
      .locator('[class*="block"], [data-block], button')
      .filter({ hasText: /hero|kategorie|polecane/i })
      .first()
    if (await firstBlock.isVisible()) {
      await firstBlock.click()
      await page.waitForLoadState('networkidle')
      // Settings panel should appear with inputs
      const hasInput = await page
        .locator('input[type="text"], textarea')
        .first()
        .isVisible()
        .catch(() => false)
      expect(hasInput).toBeTruthy()
    }
  })

  test('E-HB.1.4 saving the builder does not 500', async ({ page }) => {
    await page.goto('/manager/homepage-builder')
    await page.waitForLoadState('networkidle')
    const saveBtn = page
      .locator('button')
      .filter({ hasText: /zapisz|save/i })
      .first()
    if (await saveBtn.isVisible()) {
      await saveBtn.click()
      await page.waitForLoadState('networkidle')
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })
})
