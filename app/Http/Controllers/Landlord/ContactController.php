<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Mail\ContactInquiryMail;
use App\Models\Landlord\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    /** Public: store inquiry from landing page contact form */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $inquiry = ContactInquiry::create($validated);

        // Notify super admin
        $adminEmail = config('mail.admin_address', 'admin@roveto.pl');
        Mail::to($adminEmail)->queue(new ContactInquiryMail($inquiry));

        return back()->with('contact_success', true);
    }

    /** Admin: list all inquiries */
    public function index(Request $request)
    {
        $inquiries = ContactInquiry::latest()
            ->paginate(25)
            ->withQueryString();

        // Mark unread count
        $unread = ContactInquiry::where('read', false)->count();

        return Inertia::render('Landlord/Contacts/Index', [
            'inquiries' => $inquiries,
            'unread' => $unread,
        ]);
    }

    /** Admin: mark as read */
    public function markRead(ContactInquiry $inquiry)
    {
        $inquiry->update(['read' => true]);

        return back();
    }

    /** Admin: delete */
    public function destroy(ContactInquiry $inquiry)
    {
        $inquiry->delete();

        return back()->with('success', __('messages.enquiry_deleted'));
    }
}
