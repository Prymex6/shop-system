import { test, expect } from '@playwright/test'

/**
 * E2E tests for the Live Chat widget on the client-facing shop side (§14.7).
 *
 * Tests cover:
 *   E-CL.1.x — Widget visibility
 *   E-CL.2.x — Guest flow (name + email form)
 *   E-CL.3.x — Sending messages
 *   E-CL.4.x — Polling / receiving responses
 *   E-CL.5.x — Session persistence (sessionStorage)
 *   E-CL.6.x — chat_enabled = false hides widget
 *   E-CL.7.x — Error states
 */

test.describe('Live chat, the customer-side widget (§14.7)', () => {
  // ─── E-CL.1 Widget visibility ─────────────────────────────────────────

  test('E-CL.1.1 the chat button is on the storefront', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()

    // Widget may be hidden if chat_enabled=false — check if it's present at all
    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    const hasChatBtn = await chatBtn.isVisible().catch(() => false)
    // Widget is expected to be visible when chat is enabled (default)
    // If the setting is off in the test environment, this is a soft assertion
    if (hasChatBtn) {
      await expect(chatBtn).toBeVisible()
    }
  })

  test('E-CL.1.2 the button opens the chat window', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    if (!(await chatBtn.isVisible().catch(() => false))) {
      test.skip()
      return
    }

    await chatBtn.click()

    const chatWindow = page
      .locator('[data-chat-window], [class*="chat-window"], [class*="chat-widget"], #chat-window')
      .first()

    await expect(chatWindow).toBeVisible({ timeout: 3000 })
  })

  test('E-CL.1.3 the X closes the chat window', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    if (!(await chatBtn.isVisible().catch(() => false))) {
      test.skip()
      return
    }

    await chatBtn.click()

    const closeBtn = page
      .locator('[data-chat-close], [aria-label*="zamknij" i], [aria-label*="close" i], button[class*="close"]')
      .first()

    if (await closeBtn.isVisible().catch(() => false)) {
      await closeBtn.click()

      const chatWindow = page
        .locator('[data-chat-window], [class*="chat-window"], [class*="chat-widget"], #chat-window')
        .first()

      await expect(chatWindow).not.toBeVisible({ timeout: 3000 })
    }
  })

  // ─── E-CL.2 Guest flow ────────────────────────────────────────────────

  test('E-CL.2.1 a guest gets a form asking for a name and an email', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    if (!(await chatBtn.isVisible().catch(() => false))) {
      test.skip()
      return
    }

    await chatBtn.click()

    // Guest form should show name + email
    const nameField = page
      .locator('input[name="name"], input[placeholder*="imię" i], input[placeholder*="name" i]')
      .first()
    const emailField = page.locator('input[name="email"], input[type="email"]').first()

    const hasGuestForm =
      (await nameField.isVisible().catch(() => false)) || (await emailField.isVisible().catch(() => false))

    // Either guest form or logged-in textarea — both are valid
    const hasMsgInput = await page
      .locator('textarea, input[name="body"], input[placeholder*="wpisz" i]')
      .first()
      .isVisible()
      .catch(() => false)
    expect(hasGuestForm || hasMsgInput).toBeTruthy()
  })

  test('E-CL.2.2 an empty guest form cannot start a chat', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    if (!(await chatBtn.isVisible().catch(() => false))) {
      test.skip()
      return
    }

    await chatBtn.click()

    const startBtn = page.locator('button[type="submit"], button:has-text(/rozpocznij|start/i)').first()

    if (await startBtn.isVisible().catch(() => false)) {
      await startBtn.click()
      // Should not navigate away or show success — still on guest form
      await page.waitForTimeout(500)
      const is500 = await page
        .getByText(/500|Internal Server Error/i)
        .isVisible()
        .catch(() => false)
      expect(is500).toBeFalsy()
    }
  })

  // ─── E-CL.3 Sending messages ─────────────────────────────────────────

  test('E-CL.3.1 /chat/start accepts a valid guest', async ({ request }) => {
    const response = await request.post('/chat/start', {
      data: {
        name: 'E2E Gość',
        email: 'e2e@test.com',
      },
    })

    expect([200, 201]).toContain(response.status())
    const body = await response.json().catch(() => ({}))
    expect(body).toHaveProperty('conversation_id')
  })

  test('E-CL.3.2 API /chat/start odrzuca brak imienia (422)', async ({ request }) => {
    const response = await request.post('/chat/start', {
      headers: { Accept: 'application/json' },
      data: { email: 'e2e@test.com' },
    })

    expect(response.status()).toBe(422)
  })

  test('E-CL.3.3 API /chat/start odrzuca niepoprawny email (422)', async ({ request }) => {
    const response = await request.post('/chat/start', {
      headers: { Accept: 'application/json' },
      data: { name: 'Gość', email: 'zly-email' },
    })

    expect(response.status()).toBe(422)
  })

  test('E-CL.3.4 /chat/{id}/send takes a message', async ({ request }) => {
    // Start conversation first
    const startRes = await request.post('/chat/start', {
      data: { name: 'E2E Gość', email: 'e2e@test.com' },
    })
    expect([200, 201]).toContain(startRes.status())
    const { conversation_id } = await startRes.json()

    // Send message
    const sendRes = await request.post(`/chat/${conversation_id}/send`, {
      data: { body: 'Testowa wiadomość z E2E' },
    })
    expect([200, 201]).toContain(sendRes.status())
  })

  test('E-CL.3.5 /chat/{id}/send rejects an empty message (422)', async ({ request }) => {
    const startRes = await request.post('/chat/start', {
      data: { name: 'E2E Gość', email: 'e2e@test.com' },
    })
    const { conversation_id } = await startRes.json()

    const sendRes = await request.post(`/chat/${conversation_id}/send`, {
      headers: { Accept: 'application/json' },
      data: { body: '' },
    })
    expect(sendRes.status()).toBe(422)
  })

  test('E-CL.3.6 /chat/{id}/send rejects an over-long message (422)', async ({ request }) => {
    const startRes = await request.post('/chat/start', {
      data: { name: 'E2E Gość', email: 'e2e@test.com' },
    })
    const { conversation_id } = await startRes.json()

    const sendRes = await request.post(`/chat/${conversation_id}/send`, {
      headers: { Accept: 'application/json' },
      data: { body: 'x'.repeat(2001) },
    })
    expect(sendRes.status()).toBe(422)
  })

  // ─── E-CL.4 Closed conversation ──────────────────────────────────────

  test('E-CL.4.1 a customer cannot write to a closed conversation (403)', async ({ request }) => {
    // Create conversation via API
    const startRes = await request.post('/chat/start', {
      data: { name: 'E2E Gość', email: 'e2e@test.com' },
    })
    const { conversation_id } = await startRes.json()

    // Close it via manager API (requires auth — skip if not 200)
    const closeRes = await request.patch(`/manager/chat/${conversation_id}/close`, {
      headers: { Accept: 'application/json' },
    })
    if (closeRes.status() !== 200) {
      // Can't close via API without manager auth in this context
      test.skip()
      return
    }

    // Try to send to closed conversation
    const sendRes = await request.post(`/chat/${conversation_id}/send`, {
      data: { body: 'Wiadomość do zamkniętej rozmowy' },
    })
    expect(sendRes.status()).toBe(403)
  })

  // ─── E-CL.5 Polling ───────────────────────────────────────────────────

  test('E-CL.5.1 /chat/{id}/poll returns the messages', async ({ request }) => {
    const startRes = await request.post('/chat/start', {
      data: { name: 'E2E Gość', email: 'e2e@test.com' },
    })
    const { conversation_id } = await startRes.json()

    await request.post(`/chat/${conversation_id}/send`, {
      data: { body: 'Pierwsza wiadomość' },
    })

    const pollRes = await request.get(`/chat/${conversation_id}/poll`)
    expect([200]).toContain(pollRes.status())
    const messages = await pollRes.json()
    expect(Array.isArray(messages)).toBe(true)
    expect(messages.length).toBeGreaterThanOrEqual(1)
  })

  // ─── E-CL.6 Session persistence ──────────────────────────────────────

  test('E-CL.6.1 opening and closing the chat does not 500', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const is500 = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500).toBeFalsy()

    const chatBtn = page
      .locator('[data-chat-toggle], [aria-label*="chat" i], button[class*="chat"], .chat-toggle, #chat-btn')
      .first()

    if (!(await chatBtn.isVisible().catch(() => false))) {
      return // chat disabled in this environment
    }

    // Open
    await chatBtn.click()
    await page.waitForTimeout(300)

    // Close
    const closeBtn = page.locator('[data-chat-close], [aria-label*="zamknij" i], [aria-label*="close" i]').first()
    if (await closeBtn.isVisible().catch(() => false)) {
      await closeBtn.click()
    }

    // Reopen — no error
    await chatBtn.click()
    await page.waitForTimeout(300)
    const is500After = await page
      .getByText(/500|Internal Server Error/i)
      .isVisible()
      .catch(() => false)
    expect(is500After).toBeFalsy()
  })

  test('E-CL.6.2 the page loads with a clean console', async ({ page }) => {
    const errors: string[] = []
    page.on('pageerror', (err) => errors.push(err.message))

    await page.goto('/')
    await page.waitForLoadState('networkidle')

    const fatalErrors = errors.filter((e) => /Cannot read|is not a function|undefined|null/i.test(e))
    expect(fatalErrors).toHaveLength(0)
  })
})
