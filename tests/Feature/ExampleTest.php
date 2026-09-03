<?php

namespace Tests\Feature;

use Tests\TenantTestCase;

class ExampleTest extends TenantTestCase
{
    /**
     * Verify the tenant menu route returns a successful response.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.shop'));

        $response->assertStatus(200);
    }
}
