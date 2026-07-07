<?php

namespace App\Services;

use App\Models\Tenant\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit event.
     *
     * @param string $action Short action identifier (e.g. 'order.status_changed')
     * @param Model|null $subject The model being acted upon
     * @param array $old Old attribute values
     * @param array $new New attribute values
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = []
    ): void {
        try {
            // Determine acting user
            $userType = 'system';
            $userId = null;

            if ($user = Auth::guard('tenant')->user()) {
                $userType = 'staff';
                $userId = $user->id;
            } elseif ($customer = Auth::guard('customer')->user()) {
                $userType = 'customer';
                $userId = $customer->id;
            }

            AuditLog::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'old_values' => !empty($old) ? $old : null,
                'new_values' => !empty($new) ? $new : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the application
            Log::warning('AuditService: failed to log event', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
