<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'status', 'blocks', 'meta_title', 'meta_description'];

    protected $casts = [
        'blocks' => 'array',
    ];
}
