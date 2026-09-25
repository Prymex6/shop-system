<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every page in this application is an Inertia page, and the Blade
        // shell they all render through calls @vite. Without this, that call
        // looks for public/build/manifest.json and throws when it is missing,
        // so the whole suite would depend on someone having run `npm run build`
        // first — true on a developer's machine, false on a fresh checkout.
        // The assertions are about what the server sends, not which asset the
        // bundler named, so the tags are not worth building.
        $this->withoutVite();
    }
}
