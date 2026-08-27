<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\AuditLog;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for manager staff management (CRUD).
 */
class StaffManagementTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_view_staff_list(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->get(route('tenant.manager.staff.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Staff/Index')
        );
    }

    public function test_manager_can_create_staff(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.staff.store'), [
                'name' => 'Nowy Pracownik',
                'email' => 'worker@shop.com',
                'password' => 'haslo1234',
                'password_confirmation' => 'haslo1234',
                'role' => 'fulfillment',
                'phone' => '111222333',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'worker@shop.com', 'role' => 'fulfillment']);
    }

    public function test_create_staff_validates_role(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.staff.store'), [
                'name' => 'Test',
                'email' => 'test@test.com',
                'password' => 'haslo1234',
                'role' => 'invalid_role',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    }

    public function test_create_staff_requires_unique_email(): void
    {
        User::create([
            'name' => 'Existing', 'email' => 'existing@test.com',
            'password' => bcrypt('secret'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.staff.store'), [
                'name' => 'Duplikat',
                'email' => 'existing@test.com',
                'password' => 'haslo1234',
                'role' => 'warehouse',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_manager_can_update_staff(): void
    {
        $staff = User::create([
            'name' => 'Old Name', 'email' => 'staff@test.com',
            'password' => bcrypt('secret'), 'role' => 'fulfillment', 'is_active' => true,
        ]);
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.staff.update', $staff), [
                'name' => 'New Name',
                'email' => 'staff@test.com',
                'role' => 'warehouse',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'New Name', 'role' => 'warehouse']);
    }

    public function test_manager_can_delete_staff(): void
    {
        $manager = $this->manager();
        $staff = User::create([
            'name' => 'To Delete', 'email' => 'delete@test.com',
            'password' => bcrypt('secret'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.staff.destroy', $staff));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_manager_cannot_delete_themselves(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.staff.destroy', $manager));

        // Should prevent self-deletion - either redirect with error or 403
        $this->assertDatabaseHas('users', ['id' => $manager->id]);
    }

    public function test_manager_cannot_demote_themselves(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.staff.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'role' => 'fulfillment',
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('users', ['id' => $manager->id, 'role' => 'manager']);
    }

    public function test_manager_cannot_deactivate_themselves(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.staff.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'role' => 'manager',
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('users', ['id' => $manager->id, 'is_active' => true]);
    }

    // Role changes previously only went to the plain server log, invisible
    // in the manager-facing Audit Log panel — the single most
    // privilege-sensitive action in the RBAC system left no trace in the
    // tool built to track it (AUDIT-FINDINGS.md Runda 19).

    public function test_staff_role_change_is_recorded_in_audit_log(): void
    {
        $manager = $this->manager();
        $staff = User::create([
            'name' => 'Worker', 'email' => 'worker@test.com',
            'password' => bcrypt('s'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.staff.update', $staff), [
                'name' => $staff->name, 'email' => $staff->email,
                'role' => 'manager', 'is_active' => true,
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.updated',
            'subject_type' => 'User',
            'subject_id' => $staff->id,
        ]);

        $log = AuditLog::where('action', 'staff.updated')->latest()->first();
        $this->assertEquals('fulfillment', $log->old_values['role']);
        $this->assertEquals('manager', $log->new_values['role']);
    }

    public function test_staff_creation_is_recorded_in_audit_log(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.manager.staff.store'), [
                'name' => 'New Staff', 'email' => 'new-staff@test.com',
                'password' => 'haslo1234', 'role' => 'warehouse',
            ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.created', 'subject_type' => 'User']);
    }

    public function test_staff_deletion_is_recorded_in_audit_log(): void
    {
        $manager = $this->manager();
        $staff = User::create([
            'name' => 'ToDelete', 'email' => 'todelete@test.com',
            'password' => bcrypt('s'), 'role' => 'fulfillment', 'is_active' => true,
        ]);

        $this->actingAs($manager, 'tenant')
            ->withoutTenantMiddleware()
            ->deleteJson(route('tenant.manager.staff.destroy', $staff));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.deleted',
            'subject_type' => 'User',
            'subject_id' => $staff->id,
        ]);
    }
}
