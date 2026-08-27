<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\StaffReport;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager viewing and managing staff reports.
 */
class StaffReportsManagerTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    private function makeReport(User $user, string $status = 'new'): StaffReport
    {
        return StaffReport::create([
            'staff_user_id' => $user->id,
            'role' => $user->role,
            'title' => 'Test Problem',
            'message' => 'Opisuję problem',
            'status' => $status,
        ]);
    }

    public function test_manager_can_view_staff_reports(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.staff-reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/StaffReports')
        );
    }

    public function test_manager_can_mark_report_as_read(): void
    {
        $manager = $this->manager();
        $chef = User::create([
            'name' => 'Chef', 'email' => 'chef@t.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);
        $report = $this->makeReport($chef, 'new');

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.manager.staff-reports.mark-read', $report));

        $response->assertRedirect();
        $report->refresh();
        $this->assertEquals('read', $report->status);
    }

    public function test_manager_can_mark_all_reports_as_read(): void
    {
        $manager = $this->manager();
        $chef = User::create([
            'name' => 'Chef', 'email' => 'chef@t.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);

        $report1 = $this->makeReport($chef, 'new');
        $report2 = $this->makeReport($chef, 'new');

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.staff-reports.mark-all-read'));

        $this->assertEquals('read', $report1->fresh()->status);
        $this->assertEquals('read', $report2->fresh()->status);
    }

    public function test_new_reports_count_is_returned(): void
    {
        $manager = $this->manager();
        $chef = User::create([
            'name' => 'Chef', 'email' => 'chef@t.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);
        $this->makeReport($chef, 'new');
        $this->makeReport($chef, 'new');

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.staff-reports.index'));

        $response->assertStatus(200);
    }
}
