<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RmaRequest;
use App\Services\AuditService;
use App\Services\RefundService;
use App\Services\RmaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RmaController extends Controller
{
    public function __construct(
        protected RmaService $rmaService,
        protected RefundService $refundService
    ) {}

    public function index(Request $request)
    {
        $rmas = RmaRequest::with('order')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('rma_number', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_email', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_name', 'like', '%' . $request->search . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Rma/Index', [
            'rmaList' => $rmas,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(RmaRequest $rma)
    {
        return Inertia::render('Tenant/Manager/Rma/Show', [
            'rma' => $rma->load('order.items'),
        ]);
    }

    public function update(Request $request, RmaRequest $rma)
    {
        // Previously validated against the full order total regardless of
        // how much prior RMAs on this order already refunded —
        // RefundService::request() silently clamps the saved amount to
        // min($requested, $refundable) anyway, but the form's own max let a
        // manager type e.g. 100 PLN and see it silently become 20 PLN with
        // no indication why.
        $refundable = max(0, (float) $rma->order->total - $this->refundService->alreadyRefunded($rma->order));

        $data = $request->validate([
            'action' => 'required|in:approve,reject,received,refunded',
            'reason' => 'nullable|string|max:1000',
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:' . $refundable],
        ]);

        match ($data['action']) {
            'approve' => $this->rmaService->approve($rma),
            'reject' => $this->rmaService->reject($rma, $data['reason'] ?? ''),
            'received' => $this->rmaService->markReceived($rma),
            'refunded' => $this->refundRma($rma, $data),
        };

        return back()->with('success', 'Status RMA zaktualizowany.');
    }

    /**
     * Turn an RMA into a real, processable Refund record (approved, ready for
     * a manager to trigger the actual gateway refund from the Refunds panel) —
     * marking an RMA "refunded" must never just be a status label with no
     * money actually moving.
     */
    private function refundRma(RmaRequest $rma, array $data): void
    {
        $refund = $this->refundService->request($rma->order, [
            'amount' => $data['refund_amount'] ?? $rma->order->total,
            'reason' => 'RMA ' . $rma->rma_number . ($data['reason'] ? ': ' . $data['reason'] : ''),
            'items' => $rma->items,
            'status' => 'approved',
        ]);

        $rma->update(['status' => 'refunded', 'refund_amount' => $refund->amount]);

        AuditService::log('rma.refunded', $rma, [], ['refund_id' => $refund->id, 'amount' => (string) $refund->amount]);
    }
}
