<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Events\ChatActivityForManager;
use App\Events\ChatMessageSent;
use App\Http\Concerns\ValidatesHoneypot;
use App\Http\Controllers\Controller;
use App\Models\Tenant\ChatConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    use ValidatesHoneypot;

    public function start(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        if ($this->isHoneypotFilled($request)) {
            // Pretend success with a conversation_id that resolves to nothing —
            // subsequent send/poll calls 404, but the bot doesn't learn to skip the field.
            return response()->json(['conversation_id' => 0]);
        }

        $token = Str::random(64);

        $conv = ChatConversation::create([
            'access_token' => $token,
            'guest_name' => $data['name'],
            'guest_email' => $data['email'],
            'customer_id' => auth('customer')->id(),
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        // Kept server-side in the visitor's session, never echoed back in a URL,
        // so it doesn't end up in browser history or a proxy/CDN access log.
        $request->session()->put('chat_token_' . $conv->id, $token);

        broadcast(new ChatActivityForManager($conv, 'Nowa rozmowa'));

        return response()->json(['conversation_id' => $conv->id]);
    }

    private function authorize_(Request $request, ChatConversation $conversation): void
    {
        $ownsViaSession = hash_equals(
            (string) $request->session()->get('chat_token_' . $conversation->id, ''),
            (string) $conversation->access_token
        );
        $ownsViaCustomer = auth('customer')->check() && auth('customer')->id() === $conversation->customer_id;

        abort_unless($ownsViaSession || $ownsViaCustomer, 403, __('messages.conversation_no_access'));
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $this->authorize_($request, $conversation);

        if ($conversation->status === 'closed') {
            return response()->json(
                ['message' => __('messages.conversation_ended')],
                403
            );
        }

        $data = $request->validate(['body' => 'required|string|max:2000']);

        $message = $conversation->messages()->create([
            'sender' => 'customer',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new ChatMessageSent($message))->toOthers();
        broadcast(new ChatActivityForManager($conversation, $data['body']));

        return response()->json($message);
    }

    public function poll(Request $request, ChatConversation $conversation)
    {
        $this->authorize_($request, $conversation);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'sender', 'body', 'created_at']);

        // Mark staff messages as read
        $conversation->messages()
            ->where('sender', 'staff')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }
}
