<?php

namespace Tests\Feature;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Tests\TenantTestCase;

/**
 * No Mailable implements ShouldQueue and there's no Horizon, so a failed
 * queued job (order confirmation email, digital download link, shipping
 * SMS, ...) previously vanished into failed_jobs with zero signal to
 * anyone. AppServiceProvider::boot() now registers Queue::failing() to
 * Log::critical() every failure — this fires the same event Laravel's
 * queue worker fires on a failed job and asserts the listener logs it.
 */
class QueueFailureLoggingTest extends TenantTestCase
{
    public function test_failed_job_is_logged_as_critical(): void
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('Queue job failed', \Mockery::on(function ($context) {
                return $context['connection'] === 'database'
                    && $context['job'] === 'App\\Jobs\\SomeQueuedJob'
                    && $context['exception'] === 'Something went wrong';
            }));

        // Also needs payload() — stancl/tenancy's QueueTenancyBootstrapper
        // has its own global JobFailed listener that reads it.
        $job = new class
        {
            public function resolveName()
            {
                return 'App\\Jobs\\SomeQueuedJob';
            }

            public function payload()
            {
                return [];
            }
        };

        event(new JobFailed('database', $job, new \Exception('Something went wrong')));
    }
}
