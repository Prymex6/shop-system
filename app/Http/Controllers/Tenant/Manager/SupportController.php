<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Events\SupportMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Landlord\SupportTicket;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index()
    {
        $tenantId = tenant('id');

        tenancy()->end();
        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->with('latestMessage')
            ->latest()
            ->get();
        tenancy()->initialize(Tenant::find($tenantId));

        return Inertia::render('Tenant/Manager/Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $tenantId = tenant('id');
        $authorName = auth()->user()->name ?? 'Manager';

        tenancy()->end();

        $ticket = SupportTicket::create([
            'tenant_id' => $tenantId,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'unread_by_admin' => true,
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'tenant',
            'author_name' => $authorName,
            'message' => $validated['message'],
        ]);

        tenancy()->initialize(Tenant::find($tenantId));

        try {
            broadcast(new SupportMessageCreated(
                tenantId: $tenantId,
                ticketId: $ticket->id,
                subject: $validated['subject'],
                authorType: 'tenant',
                messagePreview: $validated['message'],
            ))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return redirect()->route('tenant.manager.support.show', $ticket->id)
            ->with('success', __('messages.ticket_sent'));
    }

    public function show($ticketId)
    {
        $tenantId = tenant('id');

        tenancy()->end();
        $ticket = SupportTicket::where('tenant_id', $tenantId)
            ->with('messages')
            ->findOrFail($ticketId);
        tenancy()->initialize(Tenant::find($tenantId));

        return Inertia::render('Tenant/Manager/Support/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, $ticketId)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $tenantId = tenant('id');
        $authorName = auth()->user()->name ?? 'Manager';

        tenancy()->end();

        $ticket = SupportTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);

        $ticketUpdate = ['unread_by_admin' => true];
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticketUpdate['status'] = 'open';
        }
        $ticket->update($ticketUpdate);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'tenant',
            'author_name' => $authorName,
            'message' => $validated['message'],
        ]);

        tenancy()->initialize(Tenant::find($tenantId));

        try {
            broadcast(new SupportMessageCreated(
                tenantId: $tenantId,
                ticketId: $ticket->id,
                subject: $ticket->subject,
                authorType: 'tenant',
                messagePreview: $validated['message'],
            ))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return back()->with('success', __('messages.reply_sent'));
    }

    public function close($ticketId)
    {
        $tenantId = tenant('id');

        tenancy()->end();
        $ticket = SupportTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);
        $ticket->update(['status' => 'closed']);
        tenancy()->initialize(Tenant::find($tenantId));

        return back()->with('success', __('messages.ticket_closed'));
    }
}
