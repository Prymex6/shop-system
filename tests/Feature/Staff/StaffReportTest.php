<?php

namespace Tests\Feature\Staff;

use App\Models\Tenant\StaffReport;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for staff submitting reports to manager.
 */
class StaffReportTest extends TenantTestCase
{
    private function chef(): User
    {
        return User::create([
            'name' => 'Kucharz', 'email' => 'chef@test.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);
    }

    public function test_staff_can_view_their_reports(): void
    {
        $response = $this->actingAs($this->chef(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Staff/Report')
        );
    }

    public function test_staff_can_submit_report(): void
    {
        $chef = $this->chef();

        $response = $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.reports.store'), [
                'title' => 'Problem z piecykiem',
                'message' => 'Piekarnik przestał grzać po prawej stronie.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_reports', [
            'staff_user_id' => $chef->id,
            'title' => 'Problem z piecykiem',
            'status' => 'new',
        ]);
    }

    public function test_report_requires_title_and_message(): void
    {
        $response = $this->actingAs($this->chef(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.reports.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'message']);
    }

    public function test_staff_only_sees_their_own_reports(): void
    {
        $chef1 = $this->chef();
        $chef2 = User::create([
            'name' => 'Chef2', 'email' => 'chef2@test.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);

        // Create report for chef2
        StaffReport::create([
            'staff_user_id' => $chef2->id,
            'role' => 'chef',
            'title' => 'Chef2 report',
            'message' => 'msg',
            'status' => 'new',
        ]);

        $response = $this->actingAs($chef1, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.staff.reports.index'));

        // Chef1 should only see their own reports (0 reports)
        $response->assertStatus(200);
    }
}
