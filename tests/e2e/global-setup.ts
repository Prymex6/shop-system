import { execSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'

/**
 * Playwright global setup:
 *   - Ensures the .auth directory exists
 *   - Runs `php artisan e2e:setup` to create tenant + seed data
 */
async function globalSetup(): Promise<void> {
  const projectRoot = path.resolve(process.cwd())
  const authDir = path.join(projectRoot, 'tests/e2e/.auth')
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true })
  }

  console.log('\n[global-setup] Running php artisan e2e:setup …')
  try {
    const output = execSync('php artisan e2e:setup', {
      cwd: projectRoot,
      encoding: 'utf8',
      timeout: 120_000,
    })
    console.log(output)
  } catch (err: any) {
    console.error('[global-setup] e2e:setup failed:\n', err.stdout ?? err.message)
    throw err
  }

  console.log('[global-setup] Done.\n')
}

export default globalSetup
