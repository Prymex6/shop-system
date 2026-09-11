import { Page } from '@playwright/test'

export const CREDENTIALS = {
  superAdmin: { email: 'admin@shop.localhost', password: 'password' },
  manager: { email: 'manager@example.com', password: 'password' },
  staff: { email: 'staff@example.com', password: 'password' },
  customer: { email: 'klient@example.pl', password: 'password' },
  customer2: { email: 'maria@example.pl', password: 'password' },
} as const

export async function loginLandlord(page: Page): Promise<void> {
  await page.goto('/admin/login')
  await page.fill('input[type="email"]', CREDENTIALS.superAdmin.email)
  await page.fill('input[type="password"]', CREDENTIALS.superAdmin.password)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/admin/dashboard**')
}

export async function loginStaff(page: Page, role: 'manager' | 'staff' = 'manager'): Promise<void> {
  await page.goto('/login')
  await page.waitForLoadState('networkidle')
  await page.fill('input[type="email"]', CREDENTIALS[role].email)
  await page.fill('input[type="password"]', CREDENTIALS[role].password)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/(manager(\/|$)|staff(\/|$))/, { timeout: 15_000 })
}

export async function loginCustomer(page: Page, which: 'customer' | 'customer2' = 'customer'): Promise<void> {
  await page.goto('/konto/logowanie')
  await page.waitForLoadState('networkidle')
  await page.fill('input[type="email"]', CREDENTIALS[which].email)
  await page.fill('input[type="password"]', CREDENTIALS[which].password)
  await page.click('button[type="submit"]')
  await page.waitForLoadState('networkidle')
  await page.waitForFunction(() => !window.location.pathname.includes('logowanie'), { timeout: 10_000 })
}

export async function waitForInertia(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle')
}
