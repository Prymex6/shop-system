<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    protected $fillable = ['name', 'slug'];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id')->orderBy('sort_order');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($attr) {
            if (empty($attr->slug)) {
                $attr->slug = Str::slug($attr->name);
            }
        });
    }
}
