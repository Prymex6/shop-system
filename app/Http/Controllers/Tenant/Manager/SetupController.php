<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupController extends Controller
{
    public function index()
    {
        // Redirect if setup already completed
        if (Setting::get('setup_completed', false)) {
            return redirect()->route('tenant.manager.dashboard');
        }

        return Inertia::render('Tenant/Manager/Setup', [
            'settings' => [
                'shop_name' => Setting::get('shop_name', ''),
                'shop_phone' => Setting::get('shop_phone', ''),
                'shop_email' => Setting::get('shop_email', ''),
                'shop_address' => Setting::get('shop_address', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $step = $request->input('step');

        match ($step) {
            'shop' => $this->saveShop($request),
            'hours' => $this->saveHours($request),
            'complete' => $this->complete($request),
            default => null,
        };

        if ($step === 'complete') {
            Setting::set('setup_completed', true);

            return redirect()->route('tenant.manager.dashboard')
                ->with('success', __('messages.install_finished', ['app' => config('app.name')]));
        }

        return back()->with('success', __('messages.saved'));
    }

    private function saveHours(Request $request): void
    {
        $validated = $request->validate([
            'opening_hours' => ['required', 'array'],
        ]);

        Setting::set('opening_hours', json_encode($validated['opening_hours']), 'json');
    }

    private function saveShop(Request $request): void
    {
        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_phone' => ['nullable', 'string', 'max:30'],
            'shop_email' => ['nullable', 'email', 'max:255'],
            'shop_address' => ['nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('shop_phone', $validated)) {
            $validated['shop_phone'] = PhoneHelper::normalize($validated['shop_phone']) ?? '';
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }
    }

    private function complete(Request $request): void
    {
        // Optionally create first category/product
        $categoryName = $request->input('category_name');
        if ($categoryName) {
            $category = Category::create([
                'name' => $categoryName,
                'sort_order' => 1,
            ]);

            $productName = $request->input('product_name');
            $productPrice = $request->input('product_price');
            if ($productName && $productPrice) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $productName,
                    'price' => $productPrice,
                    'is_available' => true,
                ]);
            }
        }
    }
}
