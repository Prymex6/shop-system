<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private orders channel – only authenticated staff (managers, chefs, waiters, drivers).
// All tenants share a single Reverb app, so the channel name itself must be
// tenant-scoped — otherwise every tenant's staff would subscribe to the exact
// same channel and receive each other's order events regardless of this
// auth check passing (which it always would, since each tenant's staff are
// checked against their own domain-resolved tenant guard).
Broadcast::channel('orders.{tenantId}', function ($user, $tenantId) {
    return $user !== null && tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

// Support channel for tenant – receives admin replies
Broadcast::channel('support.{tenantId}', function ($user, $tenantId) {
    return $user !== null && tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

// Support admin channel – receives all new tickets and replies
Broadcast::channel('support-admin', function ($user) {
    return $user !== null;
}, ['guards' => ['super_admin']]);

// Staff reports channel – only managers can listen
Broadcast::channel('staff-reports.{tenantId}', function ($user, $tenantId) {
    return $user !== null && $user->role === 'manager' && tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

// Chat channel – only the owning tenant's manager can listen. Customers/guests
// never subscribe over WebSocket (ChatWidget.vue only polls), so no
// session-token-based guest auth path is needed here.
Broadcast::channel('chat.{tenantId}.{conversationId}', function ($user, $tenantId, $conversationId) {
    return $user !== null && $user->role === 'manager' && tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

// Tenant-wide chat notification channel – lets a manager see new chat
// activity from any page (ManagerLayout.vue), not just while the specific
// conversation's Show.vue is open.
Broadcast::channel('chat-manager.{tenantId}', function ($user, $tenantId) {
    return $user !== null && $user->role === 'manager' && tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);
