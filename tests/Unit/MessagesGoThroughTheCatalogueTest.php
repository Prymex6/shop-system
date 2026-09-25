<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A message shown to somebody has to come from the catalogue.
 *
 * This is the check that survives, because it does not try to work out what
 * language anything is in. Three separate passes over this codebase went
 * looking for Polish and missed things: one searched for accented letters,
 * which "Plik nie istnieje na serwerze" does not have; the next searched for
 * Polish words, which "Zapisano." only has one of. Both walked straight past
 * messages that were plainly Polish to a reader.
 *
 * So this looks at where a message reaches a person instead — a flash
 * message, a validation failure, an abort, a mail subject — and asks only
 * whether the text is a literal or a __() call. A literal in any language
 * cannot follow the shop, which is the actual defect.
 */
class MessagesGoThroughTheCatalogueTest extends TestCase
{
    /**
     * Each entry is a place the text ends up in front of somebody.
     *
     * @var array<string, string>
     */
    private const CALL_SITES = [
        'flash message' => "/->with\(\s*'(?:success|error|warning|info|status)'\s*,\s*(['\"])(.+?)\\1/",
        'validation error' => "/->withErrors\(\s*\[\s*'[^']+'\s*=>\s*(['\"])(.+?)\\1/",
        'abort' => "/abort\(\s*\d+\s*,\s*(['\"])(.+?)\\1/",
        'validation rule closure' => "/\\\$fail\(\s*(['\"])(.+?)\\1/",
        'mail subject' => "/->subject\(\s*(['\"])(.+?)\\1/",
    ];

    /**
     * Text short enough or mechanical enough not to be a sentence.
     */
    private const NOT_A_MESSAGE = '/^[a-z0-9_.\-\/:]+$|^\W*$|^[A-Z_]+$/';

    public function test_no_message_is_written_as_a_literal(): void
    {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        foreach (['app', 'routes', 'bootstrap'] as $folder) {
            $directory = new \RecursiveDirectoryIterator($root . '/' . $folder);
            foreach (new \RecursiveIteratorIterator($directory) as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $lines = file($file->getPathname()) ?: [];
                foreach ($lines as $number => $line) {
                    foreach (self::CALL_SITES as $kind => $pattern) {
                        preg_match_all($pattern, $line, $matches, PREG_SET_ORDER);
                        foreach ($matches as $match) {
                            $text = $match[2];
                            if (mb_strlen($text) < 8 || preg_match(self::NOT_A_MESSAGE, $text)) {
                                continue;
                            }
                            $offenders[] = sprintf(
                                '%s:%d  [%s]  %s',
                                $file->getFilename(),
                                $number + 1,
                                $kind,
                                mb_substr($text, 0, 60)
                            );
                        }
                    }
                }
            }
        }

        sort($offenders);

        $this->assertSame([], $offenders, sprintf(
            "%d message(s) are written into the code rather than taken from lang/:\n%s",
            count($offenders),
            implode("\n", $offenders)
        ));
    }
}
