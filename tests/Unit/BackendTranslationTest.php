<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The same guarantee as TranslationCatalogueTest, for the half of the
 * interface the server writes.
 *
 * Laravel does not complain about a line it cannot find. __('messages.foo')
 * returns the string "messages.foo", which is then flashed to the session and
 * rendered as a success banner. It reads as a mistake to whoever is looking at
 * it and as nothing at all to the test suite, which is why two of these files
 * had drifted apart before this test existed: pl had no auth.php, and the two
 * validation files were missing eight rules on one side and thirty-six
 * attribute names on the other.
 */
class BackendTranslationTest extends TestCase
{
    private const LOCALES = ['pl', 'en'];

    /**
     * __('file.key') or trans('file.key'), where the file is one of ours.
     *
     * Keys are addressed with at least one dot; the part before the first one
     * names the file in lang/{locale}. Anything reached through a variable is
     * invisible here, which is the price of reading the source rather than
     * running it.
     */
    private const USAGE = '/(?:__|trans)\(\s*[\'"]([a-z_]+\.[a-zA-Z0-9_.\-]+)[\'"]/';

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return array<string, string> flattened "file.key" => text
     */
    private static function catalogue(string $locale): array
    {
        $flat = [];

        foreach (glob(self::root() . "/lang/{$locale}/*.php") ?: [] as $path) {
            $file = basename($path, '.php');
            $walk = function (array $node, string $prefix) use (&$walk, &$flat): void {
                foreach ($node as $key => $value) {
                    $full = "{$prefix}.{$key}";
                    if (is_array($value)) {
                        $walk($value, $full);
                    } else {
                        $flat[$full] = (string) $value;
                    }
                }
            };
            $walk(require $path, $file);
        }

        return $flat;
    }

    /**
     * @return array<string, list<string>> key => the files asking for it
     */
    private static function usages(): array
    {
        $found = [];

        foreach (['app', 'resources/views', 'database/seeders', 'routes'] as $folder) {
            $directory = new \RecursiveDirectoryIterator(self::root() . '/' . $folder);
            foreach (new \RecursiveIteratorIterator($directory) as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all(self::USAGE, (string) file_get_contents($file->getPathname()), $matches);
                foreach ($matches[1] as $key) {
                    $found[$key][] = $file->getFilename();
                }
            }
        }

        return $found;
    }

    /**
     * @return list<array{string}>
     */
    public static function locales(): array
    {
        return array_map(fn (string $locale) => [$locale], self::LOCALES);
    }

    #[DataProvider('locales')]
    public function test_every_line_the_server_asks_for_exists(string $locale): void
    {
        $catalogue = self::catalogue($locale);

        // Validation rules and attribute names are addressed by Laravel itself
        // rather than by a __() call, and pagination has no call site at all.
        $addressed = array_filter(
            self::usages(),
            fn (string $key) => !str_starts_with($key, 'validation.') && !str_starts_with($key, 'pagination.'),
            ARRAY_FILTER_USE_KEY
        );

        $missing = array_diff_key($addressed, $catalogue);

        $this->assertSame([], array_keys($missing), sprintf(
            "lang/%s is missing %d line(s) the code asks for:\n%s",
            $locale,
            count($missing),
            implode("\n", array_map(
                fn (string $key) => "  {$key}  (" . implode(', ', array_unique($missing[$key])) . ')',
                array_keys($missing)
            ))
        ));
    }

    public function test_both_languages_carry_the_same_lines(): void
    {
        [$pl, $en] = [self::catalogue('pl'), self::catalogue('en')];

        $this->assertSame([], array_keys(array_diff_key($pl, $en)), 'present in pl, absent in en');
        $this->assertSame([], array_keys(array_diff_key($en, $pl)), 'present in en, absent in pl');
    }

    public function test_both_languages_carry_the_same_files(): void
    {
        $names = fn (string $locale) => array_map(
            fn (string $path) => basename($path),
            glob(self::root() . "/lang/{$locale}/*.php") ?: []
        );

        $this->assertSame($names('pl'), $names('en'));
    }

    #[DataProvider('locales')]
    public function test_no_line_is_left_empty(string $locale): void
    {
        $empty = array_keys(array_filter(
            self::catalogue($locale),
            fn (string $text) => trim($text) === ''
        ));

        $this->assertSame([], $empty, "empty lines in lang/{$locale}");
    }

    /**
     * A key written twice is a line that does not do what it looks like it
     * does: PHP keeps the last one and drops the first without a word.
     * messages.php carried such a pair, two different sentences under
     * 'minimum_order_value', and the one nearer the top had been dead for as
     * long as both existed.
     */
    #[DataProvider('locales')]
    public function test_no_key_is_written_twice(string $locale): void
    {
        $duplicated = [];

        foreach (glob(self::root() . "/lang/{$locale}/*.php") ?: [] as $path) {
            preg_match_all("/^ {4}'([a-z0-9_]+)' =>/m", (string) file_get_contents($path), $matches);
            foreach (array_count_values($matches[1]) as $key => $times) {
                if ($times > 1) {
                    $duplicated[] = basename($path) . ": {$key} ({$times}x)";
                }
            }
        }

        $this->assertSame([], $duplicated, "keys defined more than once in lang/{$locale}");
    }

    /**
     * Placeholders are what a line is for; one that loses them silently drops
     * an order number or a price out of the middle of a sentence.
     */
    public function test_a_line_keeps_its_placeholders_in_both_languages(): void
    {
        [$pl, $en] = [self::catalogue('pl'), self::catalogue('en')];
        $placeholders = fn (string $text) => (preg_match_all('/:([a-z_]+)/', $text, $m) ? array_unique($m[1]) : []);

        $mismatched = [];
        foreach (array_intersect_key($pl, $en) as $key => $polish) {
            $a = $placeholders($polish);
            $b = $placeholders($en[$key]);
            sort($a);
            sort($b);
            if ($a !== $b) {
                $mismatched[] = sprintf('%s  pl(%s) vs en(%s)', $key, implode(',', $a), implode(',', $b));
            }
        }

        $this->assertSame([], $mismatched, "lines whose placeholders differ:\n" . implode("\n", $mismatched));
    }
}
