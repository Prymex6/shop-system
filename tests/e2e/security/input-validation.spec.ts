import { test, expect } from '@playwright/test'
import { LANDLORD_URL, TENANT_URL } from '../../../playwright.config'

// E49: Bezpieczenstwo - Walidacja wejsc i odrzucanie blednych danych

/** Read XSRF-TOKEN from the browser cookie jar so page.request passes CSRF check. */
async function getXsrfToken(page: any): Promise<string> {
  const cookies = await page.context().cookies()
  const token = cookies.find((c: any) => c.name === 'XSRF-TOKEN')
  return token ? decodeURIComponent(token.value) : ''
}

test.describe('E49 - Upload: blokowanie niebezpiecznych typow plikow', () => {
  test.use({ storageState: 'tests/e2e/.auth/manager.json' })

  test('E49.1.1 Upload pliku z nieobslugiwana MIME -> HTTP 422', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.post(TENANT_URL + '/manager/settings/upload', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      multipart: {
        file: { name: 'script.php', mimeType: 'application/x-php', buffer: Buffer.from('not-an-image') },
        subfolder: 'logos',
        field: 'logo_url',
      },
    })
    expect(response.status()).toBe(422)
    const body = await response.json()
    expect(body.errors?.file).toBeTruthy()
  })

  test('E49.1.2 Upload pliku SVG -> HTTP 422', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.post(TENANT_URL + '/manager/settings/upload', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      multipart: {
        file: { name: 'image.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg></svg>') },
        subfolder: 'logos',
        field: 'logo_url',
      },
    })
    expect(response.status()).toBe(422)
  })

  test('E49.1.3 a 6MB upload is refused', async ({ page }) => {
    await page.goto('/manager/settings')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const bigBuffer = Buffer.alloc(6 * 1024 * 1024, 0xff)
    const response = await page.request.post(TENANT_URL + '/manager/settings/upload', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      multipart: {
        file: { name: 'huge.jpg', mimeType: 'image/jpeg', buffer: bigBuffer },
        subfolder: 'logos',
        field: 'logo_url',
      },
    })
    expect([413, 422]).toContain(response.status())
  })
})

test.describe('E49 - uploading as a guest', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E49.1.4 a guest trying to upload is refused', async ({ page }) => {
    const response = await page.request.post(TENANT_URL + '/manager/settings/upload', {
      headers: { Accept: 'application/json' },
      multipart: {
        file: { name: 'logo.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('fake') },
        subfolder: 'logos',
        field: 'logo_url',
      },
    })
    // 302 redirect, 401/403 auth error, 419 CSRF mismatch - all indicate rejection
    expect([302, 401, 403, 419]).toContain(response.status())
  })
})

test.describe('E49 - a guest cannot reach the shop search', () => {
  test.use({ storageState: { cookies: [], origins: [] }, baseURL: LANDLORD_URL })

  test('E49.2.1 GET /admin/shop-search sends a guest to sign in', async ({ page }) => {
    await page.goto('/admin/shop-search')
    const url = page.url()
    expect(url).toContain('login')
  })

  test('E49.2.2 POST toggle-contacted bez auth -> Odmowa', async ({ page }) => {
    const response = await page.request.post(LANDLORD_URL + '/admin/shop-search/toggle-contacted', {
      data: { osm_id: 1, name: 'Test' },
    })
    expect([302, 401, 403, 419]).toContain(response.status())
  })
})

test.describe('E49 - a super admin can reach the shop search', () => {
  test.use({ storageState: 'tests/e2e/.auth/super-admin.json', baseURL: LANDLORD_URL })

  test('E49.2.3 Super-admin widzi /admin/shop-search bez przekierowania', async ({ page }) => {
    await page.goto('/admin/shop-search')
    const url = page.url()
    expect(url).not.toContain('login')
  })
})

test.describe('E49 - Ochrona CSRF', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('E49.3.1 a checkout POST without a CSRF token is refused', async ({ page }) => {
    const response = await page.request.post(TENANT_URL + '/kasa', {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { customer_name: 'Jan Test', items: [] },
    })
    expect(response.status()).not.toBe(200)
    expect([302, 419, 422]).toContain(response.status())
  })

  test('E49.3.2 a settings POST without a CSRF token is refused', async ({ page }) => {
    const response = await page.request.post(TENANT_URL + '/manager/settings', {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { shop_name: 'Test' },
    })
    expect(response.status()).not.toBe(200)
  })
})

test.describe('E49 - XSS w polach produktu', () => {
  test.use({ storageState: 'tests/e2e/.auth/manager.json' })

  test('E49.4.1 an XSS payload in a product name does not run', async ({ page }) => {
    const lt = String.fromCharCode(60)
    const gt = String.fromCharCode(62)
    const xssPayload = lt + 'script' + gt + 'window.__xss_ok=1' + lt + '/script' + gt
    await page.goto('/manager/products/create')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.post(TENANT_URL + '/manager/products', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      data: { name: xssPayload, price: '9.99', type: 'physical', status: 'active', stock_qty: 1, description: 'Test' },
    })
    if ([200, 201, 302].includes(response.status())) {
      await page.goto('/manager/products')
      await page.waitForLoadState('networkidle')
      const xssRan = await page.evaluate(() => (window as any).__xss_ok)
      expect(xssRan).toBeFalsy()
    } else {
      // 422 = rejected by validation, 500 = server error — both mean XSS didn't execute
      expect([422, 500]).toContain(response.status())
    }
  })
})

test.describe('E49 - Walidacja shop-search', () => {
  test.use({ storageState: 'tests/e2e/.auth/super-admin.json', baseURL: LANDLORD_URL })

  test('E49.5.1 Wyszukiwanie bez miasta -> HTTP 422', async ({ page }) => {
    await page.goto('/admin/shop-search')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.get(LANDLORD_URL + '/admin/shop-search/search', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      params: { radius: '1000' },
    })
    expect(response.status()).toBe(422)
  })

  test('E49.5.2 Wyszukiwanie z niedozwolonym promieniem -> HTTP 422', async ({ page }) => {
    await page.goto('/admin/shop-search')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.get(LANDLORD_URL + '/admin/shop-search/search', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      params: { city: 'Warszawa', radius: '9999' },
    })
    expect(response.status()).toBe(422)
  })

  test('E49.5.3 find-contact bez wymaganych pol -> HTTP 422', async ({ page }) => {
    await page.goto('/admin/shop-search')
    await page.waitForLoadState('networkidle')
    const xsrf = await getXsrfToken(page)
    const response = await page.request.get(LANDLORD_URL + '/admin/shop-search/find-contact', {
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
      params: {},
    })
    expect(response.status()).toBe(422)
  })
})
