<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class TenantModification extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_modifications';

    protected $fillable = [
        'name',
        'code',
        'description',
        'version',
        'author',
        'status',
        'tenant_ids',
        'rules',
    ];

    protected $casts = [
        'status' => 'boolean',
        'tenant_ids' => 'array',
        'rules' => 'array',
    ];

    /**
     * Check if this modification applies to a given tenant.
     */
    public function appliesToTenant(string $tenantId): bool
    {
        // null = applies to all tenants
        if ($this->tenant_ids === null) {
            return true;
        }

        return in_array($tenantId, $this->tenant_ids ?? []);
    }

    /**
     * Return list of patches (rules.patches array).
     */
    public function getPatches(): array
    {
        return $this->rules['patches'] ?? [];
    }
}
