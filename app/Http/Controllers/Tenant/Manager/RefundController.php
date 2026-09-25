<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Refund;
use App\Services\AuditService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function __construct(private RefundService $refundService) {}

    public function index(Request $request)
    {
        $refunds = Refund::with(['order.customer'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Refunds/Index', [
            'refunds' => $refunds,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Refund $refund)
    {
        $refund->load(['order.items.product', 'order.customer']);

        return Inertia::render('Tenant/Manager/Refunds/Show', [
            'refund' => $refund,
        ]);
    }

    public function approve(Refund $refund)
    {
        $this->refundService->approve($refund);
        AuditService::log('refund.approved', $refund);

        return back()->with('success', __('messages.refund_approved'));
    }

    public function reject(Refund $refund, Request $request)
    {
        $this->refundService->reject($refund, $request->input('reason'));
        AuditService::log('refund.rejected', $refund);

        return back()->with('success', 'Zwrot odrzucony.');
    }

    public function process(Refund $refund)
    {
        if (!$refund->isProcessable()) {
            return back()->with('error', __('messages.refund_wrong_status'));
        }

        try {
            $this->refundService->process($refund);

            return back()->with('success', __('messages.refund_processed'));
        } catch (\Exception $e) {
            return back()->with('error', __('messages.refund_processing_failed', ['reason' => $e->getMessage()]));
        }
    }
}
