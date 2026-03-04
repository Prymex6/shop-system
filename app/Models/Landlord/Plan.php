<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'slug',
        'price',
        'max_orders_per_month',
        'max_products',
        'max_staff',
        'max_storage_mb',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * `features` is a freeform JSON flag list (PlanController's "Funkcje"
     * form just lets a landlord type arbitrary strings) — the concrete keys
     * a plan is actually seeded with (online_payments, digital_products,
     * loyalty_program, sms_notifications, email_campaigns, custom_css,
     * analytics, multi_warehouse, abandoned_cart, thermal_printer — see
     * LandlordSeeder) are the only ones any tenant-side code checks.
     */
    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }
}
