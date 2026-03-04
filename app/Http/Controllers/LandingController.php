<?php

namespace App\Http\Controllers;

use App\Models\Landlord\Plan;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'price', 'features']);

        return Inertia::render('Landing', [
            'plans' => $plans,
        ]);
    }
}
