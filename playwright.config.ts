import { defineConfig, devices } from '@playwright/test'

/**
 * Shop SaaS – Playwright E2E Configuration
 *
 * Landlord panel: http://localhost:8000        (super-admin)
 * Tenant panel:   http://ecommerce.localhost:8000  (manager / staff / client)
 *
 * Prerequisites:
 *   1. Add to Windows hosts:  127.0.0.1  ecommerce.localhost
 *   2. Run:  php artisan e2e:setup
 *   3. Run:  php artisan serve  (or have XAMPP running on port 8000)
 */

export const LANDLORD_URL = process.env.LANDLORD_URL ?? 'http://localhost:8000'
export const TENANT_URL = process.env.TENANT_URL ?? 'http://ecommerce.localhost:8000'

export default defineConfig({
  testDir: './tests/e2e',
  outputDir: './tests/e2e/.output',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }],
    ['./tests/e2e/reporters/plan-reporter.ts'],
  ],
  use: {
    baseURL: TENANT_URL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
    waitForLoadState: 'networkidle',
  },
  projects: [
    {
      name: 'landlord',
      testMatch: '**/landlord/**/*.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: LANDLORD_URL,
        storageState: 'tests/e2e/.auth/super-admin.json',
      },
      dependencies: ['landlord-setup'],
    },
    {
      name: 'landlord-setup',
      testMatch: '**/setup/landlord-auth.setup.ts',
      use: { ...devices['Desktop Chrome'], baseURL: LANDLORD_URL },
    },
    {
      name: 'manager',
      testMatch: '**/manager/**/*.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: TENANT_URL,
        storageState: 'tests/e2e/.auth/manager.json',
      },
      dependencies: ['tenant-setup'],
    },
    {
      name: 'staff',
      testMatch: '**/staff/**/*.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: TENANT_URL,
        storageState: 'tests/e2e/.auth/staff.json',
      },
      dependencies: ['tenant-setup'],
    },
    {
      name: 'client',
      testMatch: '**/client/**/*.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: TENANT_URL,
        storageState: 'tests/e2e/.auth/customer.json',
      },
      dependencies: ['tenant-setup'],
    },
    {
      name: 'client-guest',
      testMatch: '**/client/guest/**/*.spec.ts',
      use: { ...devices['Desktop Chrome'], baseURL: TENANT_URL },
    },
    {
      name: 'security',
      testMatch: '**/security/**/*.spec.ts',
      use: { ...devices['Desktop Chrome'], baseURL: TENANT_URL },
      dependencies: ['tenant-setup', 'landlord-setup'],
    },
    {
      name: 'tenant-setup',
      testMatch: '**/setup/tenant-auth.setup.ts',
      use: { ...devices['Desktop Chrome'], baseURL: TENANT_URL },
    },
  ],
  globalSetup: './tests/e2e/global-setup.ts',
})
