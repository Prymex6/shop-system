<?php

namespace App\Events;

use App\Models\Tenant\StaffReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffReportCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StaffReport $report
    ) {
        $this->report->load('staffUser');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('staff-reports.' . tenant('id')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'staff-report.created';
    }

    public function broadcastWith(): array
    {
        return [
            'report' => [
                'id' => $this->report->id,
                'title' => $this->report->title,
                'message' => $this->report->message,
                'role' => $this->report->role,
                'status' => $this->report->status,
                'created_at' => $this->report->created_at,
                'staff_user' => $this->report->staffUser ? [
                    'id' => $this->report->staffUser->id,
                    'name' => $this->report->staffUser->name,
                ] : null,
            ],
        ];
    }
}
