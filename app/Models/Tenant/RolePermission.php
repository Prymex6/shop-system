<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = ['role', 'permission'];

    /**
     * Check if a role has a specific permission
     */
    public static function roleHas(string $role, string $permission): bool
    {
        return static::where('role', $role)->where('permission', $permission)->exists();
    }

    /**
     * Get all permissions for a role as an array
     */
    public static function permissionsFor(string $role): array
    {
        return static::where('role', $role)->pluck('permission')->toArray();
    }

    /**
     * Get all permissions grouped by role
     */
    public static function allGrouped(): array
    {
        $all = static::all();
        $grouped = [];
        foreach ($all as $item) {
            $grouped[$item->role][] = $item->permission;
        }

        return $grouped;
    }
}
