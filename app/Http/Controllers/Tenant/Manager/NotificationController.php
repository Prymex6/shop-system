<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private function manager()
    {
        return Auth::guard('tenant')->user();
    }

    public function index()
    {
        $notifications = $this->manager()
            ->unreadNotifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'info',
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        // Return array directly (store expects res.data to be array, or res.data.data)
        return response()->json($notifications);
    }

    public function markRead(string $id)
    {
        $notification = $this->manager()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead()
    {
        $this->manager()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
