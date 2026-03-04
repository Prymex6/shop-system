<?php

namespace App\Http\Controllers\Landlord;

use App\Events\SupportMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Landlord\SupportTicket;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TicketMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandlordSupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with('latestMessage')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(20)->withQueryString();

        // Attach tenant names
        $tenantIds = $tickets->pluck('tenant_id')->unique();
        $tenants = Tenant::whereIn('id', $tenantIds)->pluck('name', 'id');

        return Inertia::render('Landlord/Support/Index', [
            'tickets' => $tickets,
            'tenants' => $tenants,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load('messages');
        $tenant = Tenant::find($ticket->tenant_id);

        return Inertia::render('Landlord/Support/Show', [
            'ticket' => $ticket,
            'tenantName' => $tenant?->name ?? $ticket->tenant_id,
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update($validated);

        return back()->with('success', __('messages.ticket_status_updated'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'admin',
            'author_name' => auth()->user()->name ?? 'Admin',
            'message' => $validated['message'],
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        broadcast(new SupportMessageCreated(
            tenantId: $ticket->tenant_id,
            ticketId: $ticket->id,
            subject: $ticket->subject,
            authorType: 'admin',
            messagePreview: $validated['message'],
        ))->toOthers();

        return back()->with('success', __('messages.reply_sent'));
    }
}
