<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Tenant\ChatConversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatManagerController extends Controller
{
    public function index()
    {
        $conversations = ChatConversation::with(['latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender', 'customer')->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Tenant/Manager/Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $messages = $conversation->messages()->orderBy('created_at')->get();

        // Mark customer messages as read
        $conversation->messages()
            ->where('sender', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Tenant/Manager/Chat/Show', [
            'conversation' => $conversation->load('customer'),
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, ChatConversation $conversation)
    {
        if ($conversation->status === 'closed') {
            return response()->json(
                ['message' => __('messages.conversation_closed')],
                422
            );
        }

        $data = $request->validate(['body' => 'required|string|max:2000']);

        $message = $conversation->messages()->create([
            'sender' => 'staff',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function close(ChatConversation $conversation)
    {
        $conversation->update(['status' => 'closed']);

        return back()->with('success', __('messages.conversation_closed_done'));
    }

    public function poll(ChatConversation $conversation)
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'sender', 'body', 'created_at']);

        return response()->json($messages);
    }
}
