import type { Reporter, TestCase, TestResult, FullConfig, Suite, FullResult } from '@playwright/test/reporter'
import * as fs from 'fs'
import * as path from 'path'

/**
 * Playwright reporter — automatycznie aktualizuje checkboxy w E2E_TEST_PLAN.md.
 *
 * Format linii w planie:
 *   - [ ] **E1.1.1** Opis testu → Oczekiwany wynik
 *
 * Nazwy testów w spec files muszą zaczynać się od ID, np.:
 *   test('E1.1.1 Opis testu → Oczekiwany wynik', ...)
 *
 * Po przebiegu:
 *   [x] = zaliczony
 *   [!] = niezaliczony
 *   [~] = flaky (zaliczony po retry)
 *   [ ] = nie uruchomiony / pominięty
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

    // Aktualizuj datę ostatniego uruchomienia
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

    // Aktualizuj każdy checkbox na podstawie ID testu
    // Format linii: - [ ] **E1.1.1** Opis → Wynik
    content = content.replace(/^(- )\[(.)\] (\*\*E\d+\.\d+\.\d+\*\*)/gm, (_match, prefix, currentMark, boldId) => {
      // boldId = **E1.1.1**  →  extract E1.1.1
      const id = boldId.replace(/\*\*/g, '')
      const status = this.results.get(id)
      // Jeśli test nie był uruchomiony w tym przebiegu, zachowaj istniejący marker
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

  /** Wyciąga ID testu (np. E1.1.1) z tytułu testu */
  private extractId(title: string): string | null {
    const match = title.match(/^(E\d+\.\d+\.\d+)/)
    return match ? match[1] : null
  }
}

export default PlanReporter
