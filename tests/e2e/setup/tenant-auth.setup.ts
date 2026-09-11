import { test as setup } from '@playwright/test'
import { loginStaff, loginCustomer } from '../helpers/auth'

setup('authenticate as manager', async ({ page }) => {
  await loginStaff(page, 'manager')
  await page.context().storageState({ path: 'tests/e2e/.auth/manager.json' })
})

setup('authenticate as staff', async ({ page }) => {
  await loginStaff(page, 'staff')
  await page.context().storageState({ path: 'tests/e2e/.auth/staff.json' })
})

setup('authenticate as customer', async ({ page }) => {
  await loginCustomer(page, 'customer')
  await page.context().storageState({ path: 'tests/e2e/.auth/customer.json' })
})

setup('authenticate as customer2', async ({ page }) => {
  await loginCustomer(page, 'customer2')
  await page.context().storageState({ path: 'tests/e2e/.auth/customer2.json' })
})
