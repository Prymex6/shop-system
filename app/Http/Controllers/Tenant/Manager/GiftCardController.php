<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\GiftCard;
use App\Services\GiftCardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GiftCardController extends Controller
{
    public function __construct(protected GiftCardService $giftCardService) {}

    public function index(Request $request)
    {
        $query = GiftCard::with('customer')
            ->when($request->search, fn ($q) => $q->where('code', 'like', '%' . $request->search . '%'))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/GiftCards/Index', [
            'giftCards' => $query,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'value' => ['required', 'numeric', 'min:1', 'max:99999'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $expiresAt = $data['expires_at'] ? Carbon::parse($data['expires_at']) : null;

        $cards = $this->giftCardService->generate(
            (int) $data['count'],
            (float) $data['value'],
            $expiresAt
        );

        return back()->with('success', __('messages.gift_cards_generated', ['count' => $cards->count()]));
    }

    public function show(GiftCard $giftCard)
    {
        $giftCard->load('customer');

        return Inertia::render('Tenant/Manager/GiftCards/Show', [
            'giftCard' => $giftCard,
        ]);
    }

    public function destroy(GiftCard $giftCard)
    {
        $giftCard->delete();

        return redirect()->route('tenant.manager.gift-cards.index')->with('success', __('messages.gift_card_deleted'));
    }

    public function toggle(GiftCard $giftCard)
    {
        $giftCard->update(['is_active' => !$giftCard->is_active]);

        return back()->with('success', __(
            $giftCard->is_active ? 'messages.gift_card_activated' : 'messages.gift_card_deactivated'
        ));
    }
}
