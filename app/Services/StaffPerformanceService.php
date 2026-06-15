<?php

namespace App\Services;

use App\Models\Tenant\AuditLog;
use App\Models\Tenant\StaffReport;
use App\Models\Tenant\User;
use Carbon\Carbon;

class StaffPerformanceService
{
    public function getMetrics(User $staff, Carbon $from, Carbon $to): array
    {
        // Orders processed = audit logs where user changed order status
        $ordersProcessed = AuditLog::where('user_type', 'staff')
            ->where('user_id', $staff->id)
            ->where('subject_type', 'order')
            ->where('action', 'like', '%status%')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Orders fulfilled = orders sent or delivered attributed to this user
        $ordersFulfilled = AuditLog::where('user_type', 'staff')
            ->where('user_id', $staff->id)
            ->where('subject_type', 'order')
            ->where(function ($q) {
                $q->whereJsonContains('new_values->status', 'shipped')
                    ->orWhereJsonContains('new_values->status', 'delivered')
                    ->orWhereJsonContains('new_values->fulfillment_status', 'shipped')
                    ->orWhereJsonContains('new_values->fulfillment_status', 'delivered');
            })
            ->whereBetween('created_at', [$from, $to])
            ->distinct('subject_id')
            ->count();

        // Reports submitted
        $reportsSubmitted = StaffReport::where('user_id', $staff->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Average processing time: from 'confirmed' to 'shipped' (in hours)
        // We approximate using audit_logs timestamps
        $avgProcessingTime = null;
        $confirmedLogs = AuditLog::where('user_type', 'staff')
            ->where('subject_type', 'order')
            ->whereJsonContains('new_values->status', 'confirmed')
            ->whereBetween('created_at', [$from, $to])
            ->pluck('created_at', 'subject_id');

        if ($confirmedLogs->isNotEmpty()) {
            $shippedLogs = AuditLog::where('user_type', 'staff')
                ->where('user_id', $staff->id)
                ->where('subject_type', 'order')
                ->whereIn('subject_id', $confirmedLogs->keys())
                ->whereJsonContains('new_values->status', 'shipped')
                ->pluck('created_at', 'subject_id');

            if ($shippedLogs->isNotEmpty()) {
                $totalHours = 0;
                $count = 0;
                foreach ($shippedLogs as $orderId => $shippedAt) {
                    if (isset($confirmedLogs[$orderId])) {
                        $totalHours += $confirmedLogs[$orderId]->diffInHours($shippedAt);
                        $count++;
                    }
                }
                $avgProcessingTime = $count > 0 ? round($totalHours / $count, 1) : null;
            }
        }

        return [
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'orders_processed' => $ordersProcessed,
            'orders_fulfilled' => $ordersFulfilled,
            'reports_submitted' => $reportsSubmitted,
            'avg_processing_hours' => $avgProcessingTime,
        ];
    }
}
