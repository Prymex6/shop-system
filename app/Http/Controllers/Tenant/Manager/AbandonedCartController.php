<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AbandonedCart;
use Inertia\Inertia;

class AbandonedCartController extends Controller
{
    public function index()
    {
        $carts = AbandonedCart::whereNull('converted_at')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(fn ($c) => [
                'id' => $c->id,
                'email' => $c->email,
                'items_count' => count($c->cart_data['items'] ?? []),
                'total' => $c->cart_data['total'] ?? 0,
                'reminder_sent_at' => $c->reminder_sent_at?->toDateTimeString(),
                'created_at' => $c->created_at->toDateTimeString(),
            ]);

        $stats = [
            'total' => AbandonedCart::count(),
            'converted' => AbandonedCart::whereNotNull('converted_at')->count(),
            'reminder_sent' => AbandonedCart::whereNotNull('reminder_sent_at')->count(),
        ];

        return Inertia::render('Tenant/Manager/AbandonedCarts/Index', [
            'carts' => $carts,
            'stats' => $stats,
        ]);
    }

    public function destroy(AbandonedCart $abandonedCart)
    {
        $abandonedCart->delete();

        return back()->with('success', __('messages.entry_deleted'));
    }
}
