/**
 * Take the screenshots the README uses, against a running shop.
 *
 *   php artisan serve --port=8010
 *   node tools/screenshots.mjs
 *
 * The shop is bilingual and this one is set to Polish. The README is in
 * English, so the language is switched before anything is photographed and the
 * pictures match the text around them.
 *
 * Shots land in .github/images at twice the pixel density, so they stay sharp
 * on the displays most people read GitHub on.
 */
import { chromium } from '@playwright/test'
import { mkdir } from 'node:fs/promises'

const HOST = process.env.SHOP_HOST ?? 'test.shop-system.localhost'
const PORT = process.env.SHOP_PORT ?? '8010'
const SITE = `http://${HOST}:${PORT}`
const EMAIL = process.env.SHOP_EMAIL ?? 'manager@storelo.demo'
const PASSWORD = process.env.SHOP_PASSWORD ?? 'storelo-demo-2026'
const OUT = process.env.SHOT_DIR ?? '.github/images'

const settle = async (page) => {
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(900)
}

const visit = async (page, path) => {
  await page.goto(`${SITE}${path}`, { waitUntil: 'domcontentloaded' })
  await settle(page)
}

const shoot = async (page, file, { fullPage = false } = {}) => {
  await settle(page)
  await page.screenshot({ path: `${OUT}/${file}`, fullPage })
  console.log('saved', file)
}

const switchToEnglish = async (page) => {
  await page.request.post(`${SITE}/locale`, {
    form: { locale: 'en' },
    headers: {
      'X-XSRF-TOKEN': decodeURIComponent(
        (await page.context().cookies()).find((c) => c.name === 'XSRF-TOKEN')?.value ?? '',
      ),
    },
  })
}

const run = async () => {
  await mkdir(OUT, { recursive: true })

  const browser = await chromium.launch()
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    locale: 'en-GB',
  })
  const page = await context.newPage()

  // The locale lives in the session, so one page load has to happen before it
  // can be set.
  await visit(page, '/')
  await switchToEnglish(page)

  // The cookie banner covers a third of the first screen until it is answered.
  await page
    .getByRole('button', { name: /accept all/i })
    .first()
    .click()
    .catch(() => {})
  await page.waitForTimeout(600)

  await visit(page, '/')
  await shoot(page, 'storefront.png')

  const product = page.locator('a[href*="/produkt/"]').first()
  const href = await product.getAttribute('href').catch(() => null)
  if (href) {
    await page.goto(href, { waitUntil: 'domcontentloaded' })
    await page
      .getByRole('button', { name: /accept all/i })
      .first()
      .click()
      .catch(() => {})
    await page.waitForTimeout(600)
    await shoot(page, 'product.png')
  }

  await visit(page, '/login')
  await page.fill('input[type="email"]', EMAIL)
  await page.fill('input[type="password"]', PASSWORD)
  await page.click('button[type="submit"]')
  await page.waitForTimeout(2500)

  // Staff sign-in is rate limited to five attempts an hour, so a run that
  // ends up back on the login form would otherwise photograph it five times
  // over and say nothing about why.
  if (page.url().includes('/login')) {
    throw new Error('Sign-in failed. Run `php artisan cache:clear` to reset the rate limiter and try again.')
  }

  await visit(page, '/manager')
  await shoot(page, 'manager-dashboard.png')

  await visit(page, '/manager/orders')
  await shoot(page, 'manager-orders.png')

  const order = page.locator('a[href*="/manager/orders/"]').first()
  const orderHref = await order.getAttribute('href').catch(() => null)
  if (orderHref) {
    await page.goto(orderHref, { waitUntil: 'domcontentloaded' })
    await shoot(page, 'manager-order.png')
  }

  await visit(page, '/manager/shipping')
  await shoot(page, 'manager-shipping.png')

  await browser.close()
}

run().catch((error) => {
  console.error(error)
  process.exit(1)
})
