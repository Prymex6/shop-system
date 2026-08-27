<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The two dictionaries have to stay in step with each other and with the code.
 *
 * Nothing in a build or a test run notices a translation key that was used and
 * never written, or written in one language and not the other: vue-i18n simply
 * renders the key itself, and the page still loads. It shows up as a stray
 * "manager.orders.index.title" sitting in the interface, usually after someone
 * has already seen it.
 */
class TranslationCatalogueTest extends TestCase
{
    private const LOCALES = ['pl', 'en'];

    /**
     * A t('…') call, and not the tail of closest('…') or getContext('…').
     *
     * The key must have a dot in it, which is what separates a translation
     * key from the single words those other calls take.
     */
    private const USAGE = '/(?<![\w$.])t\(\s*\'([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)\'/';

    private static function base(): string
    {
        return dirname(__DIR__, 2) . '/resources/js';
    }

    /**
     * @return array<string, string> flattened key => text
     */
    private static function dictionary(string $locale): array
    {
        $path = self::base() . "/locales/{$locale}.json";
        $tree = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $flat = [];
        $walk = function (array $node, string $prefix) use (&$walk, &$flat): void {
            foreach ($node as $key => $value) {
                $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
                if (is_array($value)) {
                    $walk($value, $full);
                } else {
                    $flat[$full] = $value;
                }
            }
        };
        $walk($tree, '');

        return $flat;
    }

    /**
     * @return list<string>
     */
    private static function keysUsedInComponents(): array
    {
        $used = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::base(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }
            preg_match_all(self::USAGE, (string) file_get_contents($file->getPathname()), $found);
            foreach ($found[1] as $key) {
                $used[$key] = true;
            }
        }

        return array_keys($used);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return array_combine(
            self::LOCALES,
            array_map(fn (string $locale) => [$locale], self::LOCALES),
        );
    }

    #[DataProvider('locales')]
    public function test_every_key_the_interface_asks_for_exists(string $locale): void
    {
        $missing = array_values(array_diff(self::keysUsedInComponents(), array_keys(self::dictionary($locale))));

        $this->assertSame([], $missing, "Keys used by a component but missing from {$locale}.json");
    }

    public function test_both_languages_carry_the_same_keys(): void
    {
        $polish = array_keys(self::dictionary('pl'));
        $english = array_keys(self::dictionary('en'));

        $this->assertSame(
            [],
            array_values(array_diff($polish, $english)),
            'Keys in pl.json with no English',
        );
        $this->assertSame(
            [],
            array_values(array_diff($english, $polish)),
            'Keys in en.json with no Polish',
        );
    }

    #[DataProvider('locales')]
    public function test_no_entry_is_left_empty(string $locale): void
    {
        $blank = array_keys(array_filter(
            self::dictionary($locale),
            fn ($text) => !is_string($text) || trim($text) === '',
        ));

        $this->assertSame([], $blank, "Empty entries in {$locale}.json");
    }

    public function test_the_dictionary_carries_nothing_the_interface_never_asks_for(): void
    {
        $unused = array_values(array_diff(array_keys(self::dictionary('pl')), self::keysUsedInComponents()));

        $this->assertSame([], $unused, 'Entries nothing renders');
    }
}
