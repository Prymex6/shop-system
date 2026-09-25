import type { Reporter, TestCase, TestResult, FullConfig, Suite, FullResult } from '@playwright/test/reporter'
import * as fs from 'fs'
import * as path from 'path'

/**
 * Playwright reporter — automatycznie aktualizuje checkboxy w E2E_TEST_PLAN.md.
 *
 * Format linii w planie:
 *   - [ ] **E1.1.1** Opis testu → Oczekiwany wynik
 *
 * A test name has to start with its ID, for example:
 *   test('E1.1.1 Opis testu → Oczekiwany wynik', ...)
 *
 * Po przebiegu:
 *   [x] = zaliczony
 *   [!] = niezaliczony
 *   [~] = flaky (zaliczony po retry)
 *   [ ] = not run, or skipped
 */
class PlanReporter implements Reporter {
  private results = new Map<string, 'passed' | 'failed' | 'flaky'>()
  private planPath: string
  private startTime: Date = new Date()

  constructor() {
    this.planPath = path.resolve(process.cwd(), 'E2E_TEST_PLAN.md')
  }

  onBegin(_config: FullConfig, _suite: Suite): void {
    this.startTime = new Date()
    console.log(`\n📋  PlanReporter: będzie aktualizować ${this.planPath}\n`)
  }

  onTestEnd(test: TestCase, result: TestResult): void {
    const id = this.extractId(test.title)
    if (!id) return

    const current = this.results.get(id)

    if (result.status === 'passed') {
      this.results.set(id, result.retry > 0 ? 'flaky' : 'passed')
    } else if (result.status === 'failed' || result.status === 'timedOut') {
      if (current !== 'passed' && current !== 'flaky') {
        this.results.set(id, 'failed')
      }
    }
  }

  async onEnd(result: FullResult): Promise<void> {
    if (!fs.existsSync(this.planPath)) {
      console.warn(`\n⚠  PlanReporter: ${this.planPath} nie znaleziony — pomijam.\n`)
      return
    }

    let content = fs.readFileSync(this.planPath, 'utf8')

    // Update the date of the last run
    const dateStr = this.startTime.toLocaleString('pl-PL', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
    content = content.replace(
      /\*\*Data ostatniego uruchomienia:\*\* .*/,
      `**Data ostatniego uruchomienia:** ${dateStr}`,
    )

    // Update each checkbox from the test ID
    // Format linii: - [ ] **E1.1.1** Opis → Wynik
    content = content.replace(/^(- )\[(.)\] (\*\*E\d+\.\d+\.\d+\*\*)/gm, (_match, prefix, currentMark, boldId) => {
      // boldId = **E1.1.1**  →  extract E1.1.1
      const id = boldId.replace(/\*\*/g, '')
      const status = this.results.get(id)
      // A test that did not run this time keeps the marker it had
      if (status === undefined) {
        return `${prefix}[${currentMark}] ${boldId}`
      }
      const mark = status === 'passed' ? 'x' : status === 'flaky' ? '~' : status === 'failed' ? '!' : ' '
      return `${prefix}[${mark}] ${boldId}`
    })

    // Statystyki
    const passed = [...this.results.values()].filter((v) => v === 'passed').length
    const flaky = [...this.results.values()].filter((v) => v === 'flaky').length
    const failed = [...this.results.values()].filter((v) => v === 'failed').length
    const total = this.results.size

    const statsBlock = [
      '',
      '---',
      '',
      '## Ostatni przebieg',
      '',
      `| Status | Liczba |`,
      `|---|---|`,
      `| ✅ Zaliczone | ${passed} |`,
      `| 〰️ Flaky (po retry) | ${flaky} |`,
      `| ❌ Niezaliczone | ${failed} |`,
      `| ⚪ Pominięte / nie uruchomione | ${total - passed - flaky - failed} |`,
      `| **Łącznie uruchomione** | **${total}** |`,
      `| **Wynik ogólny** | **${result.status === 'passed' ? '✅ PASS' : '❌ FAIL'}** |`,
      '',
    ].join('\n')

    if (content.includes('## Ostatni przebieg')) {
      content = content.replace(/\n---\n\n## Ostatni przebieg[\s\S]*$/, statsBlock)
    } else {
      content = content.trimEnd() + '\n' + statsBlock
    }

    fs.writeFileSync(this.planPath, content, 'utf8')

    console.log(`\n📋  PlanReporter: E2E_TEST_PLAN.md zaktualizowany`)
    console.log(`    ✅ zaliczone: ${passed}  〰️ flaky: ${flaky}  ❌ niezaliczone: ${failed}  łącznie: ${total}\n`)
  }

  /** Pulls the test ID, E1.1.1 and the like, out of the test name. */
  private extractId(title: string): string | null {
    const match = title.match(/^(E\d+\.\d+\.\d+)/)
    return match ? match[1] : null
  }
}

export default PlanReporter
