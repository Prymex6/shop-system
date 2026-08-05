/**
 * Compile every message in every locale, and say which ones will not.
 *
 * vue-i18n compiles a message the first time something renders it, not when
 * the page loads, so a message it cannot parse throws in front of whoever
 * opened that tab rather than in a build. Its syntax claims more characters
 * than is obvious: { } are interpolation, | separates plural forms, and @
 * begins a link to another message. An example stylesheet pasted into a
 * placeholder is enough to break one.
 *
 *   node tools/check-translations.mjs
 */
import { readFileSync, readdirSync } from 'node:fs'
import { baseCompile } from '@intlify/message-compiler'

const DIRECTORY = 'resources/js/locales'

const flatten = (node, prefix = '') =>
  Object.entries(node).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key
    return typeof value === 'object' && value !== null ? flatten(value, path) : [[path, value]]
  })

let failures = 0

for (const file of readdirSync(DIRECTORY).filter((name) => name.endsWith('.json'))) {
  const locale = file.replace(/\.json$/, '')
  const messages = flatten(JSON.parse(readFileSync(`${DIRECTORY}/${file}`, 'utf8')))

  for (const [key, message] of messages) {
    try {
      const { errors } = baseCompile(String(message), {
        onError: (error) => {
          throw error
        },
      })
      if (errors?.length) throw errors[0]
    } catch (error) {
      failures += 1
      console.error(`${locale}.json  ${key}`)
      console.error(`  ${String(message).slice(0, 100)}`)
      console.error(`  ${error.message}\n`)
    }
  }

  if (!failures) console.log(`${locale}: ${messages.length} messages, all compile`)
}

if (failures) {
  console.error(`${failures} message(s) will throw when rendered.`)
  process.exit(1)
}
