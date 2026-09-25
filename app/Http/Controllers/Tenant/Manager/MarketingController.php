<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\CampaignMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailCampaign;
use App\Models\Tenant\NewsletterSubscriber;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MarketingController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::latest()->paginate(15);
        $customersCount = Customer::whereNotNull('email')->count();
        $newsletterCount = NewsletterSubscriber::whereNull('unsubscribed_at')->count();

        return Inertia::render('Tenant/Manager/Marketing/Index', [
            'campaigns' => $campaigns,
            'customersCount' => $customersCount,
            'newsletterCount' => $newsletterCount,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'target' => 'required|in:all,active,inactive,newsletter',
        ]);

        $campaign = EmailCampaign::create($validated);
        Log::info('Marketing: kampania utworzona', ['campaign_id' => $campaign->id, 'name' => $campaign->name, 'target' => $campaign->target, 'manager_id' => auth('tenant')->id()]);

        return back()->with('success', __('messages.campaign_saved_draft'));
    }

    public function update(Request $request, EmailCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['error' => __('messages.campaign_cannot_edit_sent')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'target' => 'required|in:all,active,inactive,newsletter',
        ]);

        $campaign->update($validated);
        Log::info('Marketing: kampania zaktualizowana', ['campaign_id' => $campaign->id, 'name' => $campaign->name, 'manager_id' => auth('tenant')->id()]);

        return back()->with('success', 'Kampania zaktualizowana.');
    }

    public function send(EmailCampaign $campaign)
    {
        // Plan feature "email_campaigns" (LandlordSeeder — off on Starter/
        // Basic) was never checked: a plan not paying for bulk marketing
        // emails could still blast its whole customer list. A lookup
        // failure never blocks sending a campaign the manager already built.
        try {
            $plan = tenancy()->tenant?->plan;
            if ($plan && !$plan->hasFeature('email_campaigns')) {
                return back()->withErrors(['error' => __('messages.campaign_not_in_plan')]);
            }
        } catch (\Exception $e) {
            Log::warning('Plan email_campaigns feature check failed, allowing send: ' . $e->getMessage());
        }

        // A blast to the entire customer list costs real money to send and can
        // get the sending domain blacklisted — cap how much can go out per day
        // before even locking the campaign, regardless of how many drafts exist.
        $dailyCap = (int) Setting::get('marketing_daily_send_cap', 2000);
        $sentToday = (int) EmailCampaign::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->sum('recipients_count');

        $recipientEstimate = $campaign->target === 'newsletter'
            ? NewsletterSubscriber::whereNull('unsubscribed_at')->count()
            : Customer::whereNotNull('email')->where('marketing_opt_out', false)->count();

        if ($dailyCap > 0 && $sentToday + $recipientEstimate > $dailyCap) {
            return back()->withErrors(['error' => __('messages.campaign_daily_cap', ['cap' => $dailyCap])]);
        }

        // Atomic guard: only transition from draft→sending once; prevents double-send
        $locked = EmailCampaign::where('id', $campaign->id)
            ->where('status', 'draft')
            ->update(['status' => 'sending']);

        if (!$locked) {
            return back()->withErrors(['error' => __('messages.campaign_already_sending')]);
        }

        $count = 0;

        // "newsletter" targets NewsletterSubscriber rows directly — storefront
        // signups (NewsletterController::subscribe()) used to land in this
        // table and never be read by anything else in the app; every other
        // target still only ever queried Customer.
        if ($campaign->target === 'newsletter') {
            NewsletterSubscriber::whereNull('unsubscribed_at')
                ->chunk(200, function ($subscribers) use ($campaign, &$count) {
                    foreach ($subscribers as $subscriber) {
                        try {
                            $unsubscribeUrl = route('tenant.marketing.unsubscribe', [
                                'email' => $subscriber->email,
                                'token' => $subscriber->unsubscribeToken(),
                            ]);
                            Mail::to($subscriber->email)->queue(new CampaignMail($campaign, $subscriber->email, null, $unsubscribeUrl));
                            $count++;
                        } catch (\Exception $e) {
                            Log::warning('Campaign email failed for ' . $subscriber->email . ': ' . $e->getMessage());
                        }
                    }
                });
        } else {
            $query = Customer::whereNotNull('email')->where('marketing_opt_out', false);

            if ($campaign->target === 'active') {
                $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
            } elseif ($campaign->target === 'inactive') {
                $query->whereDoesntHave('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
            }

            $query->chunk(200, function ($customers) use ($campaign, &$count) {
                foreach ($customers as $customer) {
                    try {
                        $unsubscribeUrl = route('tenant.marketing.unsubscribe', [
                            'email' => $customer->email,
                            'token' => $customer->unsubscribeToken(),
                        ]);
                        Mail::to($customer->email)->queue(new CampaignMail($campaign, $customer->email, $customer->name, $unsubscribeUrl));
                        $count++;
                    } catch (\Exception $e) {
                        Log::warning('Campaign email failed for ' . $customer->email . ': ' . $e->getMessage());
                    }
                }
            });
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $count,
        ]);

        Log::info('Marketing: campaign sent', [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'target' => $campaign->target,
            'recipients' => $count,
            'manager_id' => auth('tenant')->id(),
        ]);

        return back()->with('success', __('messages.campaign_sent_to', ['count' => $count]));
    }

    public function destroy(EmailCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['error' => __('messages.campaign_cannot_delete_sent')]);
        }
        $campaign->delete();

        return back()->with('success', __('messages.campaign_deleted'));
    }

    /**
     * Public unsubscribe endpoint – no auth required.
     * URL: GET /marketing/unsubscribe?email=...&token=...
     */
    public function unsubscribe(Request $request)
    {
        $email = $request->query('email', '');
        $token = $request->query('token', '');

        $customer = Customer::where('email', $email)->first();

        if ($customer && hash_equals($customer->unsubscribeToken(), $token)) {
            $customer->update(['marketing_opt_out' => true]);
            Log::info('Marketing: wypisanie z listy', ['customer_id' => $customer->id, 'email' => $customer->email]);

            return response('<h2>' . __('messages.unsubscribed_heading') . '</h2><p>' . __('messages.unsubscribed_body') . '</p>', 200)
                ->header('Content-Type', 'text/html');
        }

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber && hash_equals($subscriber->unsubscribeToken(), $token)) {
            $subscriber->update(['unsubscribed_at' => now()]);
            Log::info('Marketing: wypisanie z newslettera', ['subscriber_id' => $subscriber->id, 'email' => $subscriber->email]);

            return response('<h2>' . __('messages.unsubscribed_heading') . '</h2><p>' . __('messages.unsubscribed_body') . '</p>', 200)
                ->header('Content-Type', 'text/html');
        }

        return response('<h2>' . __('messages.unsubscribe_link_invalid') . '</h2>', 400)
            ->header('Content-Type', 'text/html');
    }
}
