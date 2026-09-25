<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every Blade template has to compile.
 *
 * Nothing else here renders them. The transactional emails and the PDF
 * invoice are built by a queue worker, so a template that Blade cannot parse
 * does not fail a request, it fails a job — the order still goes through and
 * the customer simply never hears anything. The error pages are worse again:
 * one of those is only reached when something has already gone wrong.
 *
 * This does not check that a template says the right thing. It checks that
 * it says anything at all.
 */
class BladeTemplatesCompileTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function templates(): array
    {
        $root = dirname(__DIR__, 2) . '/resources/views';
        $found = [];

        $directory = new \RecursiveDirectoryIterator($root);
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $found[$relative] = [$file->getPathname()];
        }

        ksort($found);

        return $found;
    }

    #[DataProvider('templates')]
    public function test_the_template_compiles(string $path): void
    {
        $compiled = Blade::compileString((string) file_get_contents($path));

        // compileString hands back PHP, and a template that opened a call it
        // never closed produces PHP that does not parse. Checking the output
        // is the only way to see that from here.
        $this->assertNotSame('', trim($compiled));
        $this->assertNull($this->parseError($compiled), basename($path) . ' compiles to PHP that will not parse');
    }

    private function parseError(string $php): ?string
    {
        $file = tempnam(sys_get_temp_dir(), 'blade') . '.php';
        file_put_contents($file, $php);

        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
        @unlink($file);

        return $status === 0 ? null : implode("\n", $output);
    }
}
