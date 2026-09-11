import { test as setup } from '@playwright/test'
import { loginLandlord } from '../helpers/auth'

const authFile = 'tests/e2e/.auth/super-admin.json'

setup('authenticate as super-admin', async ({ page }) => {
  await loginLandlord(page)
  await page.context().storageState({ path: authFile })
})
